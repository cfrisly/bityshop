<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Vanilo\Category\Models\TaxonProxy;
use Vanilo\MasterProduct\Models\MasterProductProxy;
use Vanilo\Product\Models\ProductProxy;
use Vanilo\Properties\Models\PropertyValueProxy;

/**
 * Buscador de la tienda (barra de búsqueda).
 *
 * - Cada palabra tiene que aparecer en el producto, en cualquier orden
 *   ("dell laptop" = "laptop dell").
 * - Busca en nombre, SKU, descripción, marca y categorías.
 * - Ordena por relevancia: primero lo que coincide en el nombre.
 * - Mayúsculas y tildes no importan ("audifonos" encuentra "Audífonos").
 * - Si no hay resultados, sugiere la palabra más parecida ("macbok" → "macbook").
 */
class Buscador
{
    public const MAX_LARGO = 100;

    /** Palabras que no ayudan a buscar (se ignoran si hay otras) */
    private const VACIAS = ['de', 'del', 'la', 'las', 'el', 'los', 'lo', 'y', 'e', 'o', 'u', 'en', 'con', 'sin', 'para',
        'por', 'un', 'una', 'unos', 'unas', 'a', 'al', 'que', 'mas', 'the', 'for', 'and', 'with', 'of'];

    /** Limpia lo que escribió el cliente: espacios de más y largo máximo */
    public static function normalizar(?string $q): string
    {
        $q = trim(preg_replace('/\s+/u', ' ', (string) $q));

        return mb_substr($q, 0, self::MAX_LARGO);
    }

    /** Palabras a buscar (sin repetidas ni palabras vacías) */
    public static function palabras(string $q): array
    {
        $todas = collect(explode(' ', mb_strtolower($q)))
            ->map(fn ($p) => trim($p, " \t.,;:¡!¿?\"'()"))
            ->filter(fn ($p) => $p !== '')
            ->unique()
            ->values();

        $utiles = $todas->reject(fn ($p) => in_array(self::plano($p), self::VACIAS, true) || mb_strlen($p) < 2);

        return ($utiles->isNotEmpty() ? $utiles : $todas)->take(8)->values()->all();
    }

    /** Productos (y productos con variantes) que coinciden, ordenados por relevancia */
    public static function buscar(string $q, int $limite = 60): Collection
    {
        $palabras = self::palabras($q);
        if (empty($palabras)) {
            return collect();
        }

        // Si solo escribió palabras vacías ("de", "a"...), se busca únicamente en el nombre
        $soloNombre = collect($palabras)->every(fn ($p) => in_array(self::plano($p), self::VACIAS, true) || mb_strlen($p) < 2);

        $relaciones = ['media', 'taxons', 'propertyValues.property'];
        $productos = self::filtrar(ProductProxy::actives()->with($relaciones), $palabras, true, $soloNombre)->limit(200)->get();
        $maestros = class_exists(MasterProductProxy::class)
            ? self::filtrar(MasterProductProxy::actives()->with($relaciones), $palabras, false, $soloNombre)->limit(200)->get()
            : collect();

        $frase = self::plano($q);

        return $productos->concat($maestros)
            ->map(fn ($p) => ['p' => $p, 'puntos' => self::puntos($p, $palabras, $frase)])
            ->sort(fn ($a, $b) => [$b['puntos'], (int) $b['p']->units_sold, $a['p']->name] <=> [$a['puntos'], (int) $a['p']->units_sold, $b['p']->name])
            ->pluck('p')
            ->take($limite)
            ->values();
    }

    /** "¿Quisiste decir...?": cambia cada palabra por la más parecida del catálogo */
    public static function sugerencia(string $q): ?string
    {
        $vocabulario = self::vocabulario();
        if (empty($vocabulario)) {
            return null;
        }

        $cambio = false;
        $sugeridas = [];
        foreach (self::palabras($q) as $palabra) {
            $plana = self::plano($palabra);
            if (mb_strlen($plana) < 3 || isset($vocabulario[$plana])) {
                $sugeridas[] = $palabra;
                continue;
            }
            $mejor = null;
            $distanciaMejor = mb_strlen($plana) <= 5 ? 1 : 2; // tolerancia: 1 letra en palabras cortas, 2 en largas
            foreach ($vocabulario as $candidata => $original) {
                if (abs(strlen($candidata) - strlen($plana)) > $distanciaMejor) {
                    continue;
                }
                $d = levenshtein($plana, $candidata);
                if ($d <= $distanciaMejor) {
                    $distanciaMejor = $d;
                    $mejor = $original;
                }
            }
            $sugeridas[] = $mejor ?? $palabra;
            $cambio = $cambio || $mejor !== null;
        }

        return $cambio ? implode(' ', $sugeridas) : null;
    }

    /** Carga de una vez las imágenes, marcas y categorías que usa la cuadrícula de productos */
    public static function precargar(Collection $productos): Collection
    {
        $productos->groupBy(fn ($p) => get_class($p))
            ->each(fn ($grupo) => (new \Illuminate\Database\Eloquent\Collection($grupo->all()))->loadMissing(['media', 'propertyValues.property']));

        return $productos;
    }

    private static function filtrar($query, array $palabras, bool $conSku, bool $soloNombre = false)
    {
        foreach ($palabras as $palabra) {
            $like = '%' . self::escaparLike($palabra) . '%';
            if ($soloNombre) {
                $query->where('name', 'like', $like);
                continue;
            }
            $query->where(function ($w) use ($like, $conSku) {
                $w->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->when($conSku, fn ($w) => $w->orWhere('sku', 'like', $like))
                    ->orWhereHas('taxons', fn ($t) => $t->where('taxons.name', 'like', $like)->where('taxons.is_active', true))
                    ->orWhereHas('propertyValues', fn ($v) => $v->where('property_values.title', 'like', $like));
            });
        }

        return $query;
    }

    /** Relevancia: la frase completa en el nombre vale más; la descripción vale poco */
    private static function puntos($producto, array $palabras, string $frase): int
    {
        $nombre = self::plano($producto->name);
        $marca = self::plano((string) $producto->propertyValues->first(fn ($v) => $v->property?->slug === 'marca')?->title);
        $categorias = self::plano($producto->taxons->pluck('name')->implode(' '));
        $sku = self::plano((string) ($producto->sku ?? ''));
        $descripcion = self::plano((string) $producto->description);

        $puntos = str_contains($nombre, $frase) ? 100 : 0;
        foreach ($palabras as $i => $palabra) {
            $p = self::plano($palabra);
            if ($sku !== '' && $sku === $p) {
                $puntos += 80;
            } elseif (preg_match('/\b' . preg_quote($p, '/') . '/u', $nombre)) {
                $puntos += $i === 0 && str_starts_with($nombre, $p) ? 30 : 20;
            } elseif (str_contains($nombre, $p)) {
                $puntos += 12;
            } elseif (str_contains($marca, $p) || str_contains($categorias, $p)) {
                $puntos += 8;
            } elseif (str_contains($sku, $p)) {
                $puntos += 6;
            } elseif (str_contains($descripcion, $p)) {
                $puntos += 2;
            }
        }

        return $puntos;
    }

    /** Palabras del catálogo para las sugerencias (se recalcula cada 10 minutos) */
    private static function vocabulario(): array
    {
        return Cache::remember('buscador.vocabulario', now()->addMinutes(10), function () {
            $textos = ProductProxy::actives()->pluck('name')
                ->concat(TaxonProxy::query()->where('is_active', true)->pluck('name'))
                ->concat(PropertyValueProxy::query()->pluck('title'));

            $vocabulario = [];
            foreach ($textos as $texto) {
                foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower((string) $texto)) as $palabra) {
                    if (mb_strlen($palabra) >= 4 && !preg_match('/^\d+$/', $palabra)) {
                        $vocabulario[self::plano($palabra)] = $palabra;
                    }
                }
            }

            return $vocabulario;
        });
    }

    /** Minúsculas y sin tildes, para comparar */
    private static function plano(string $texto): string
    {
        return mb_strtolower(Str::ascii($texto));
    }

    /** Evita que %, _ y \ escritos por el cliente funcionen como comodines */
    private static function escaparLike(string $texto): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
    }
}
