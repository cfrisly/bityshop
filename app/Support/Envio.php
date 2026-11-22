<?php

namespace App\Support;

use App\Http\Controllers\CartController;
use Illuminate\Support\Collection;
use Konekt\Address\Models\AddressProxy;
use Konekt\Address\Models\ProvinceProxy;
use Konekt\Address\Query\Zones;
use Vanilo\Shipment\Models\ShippingMethodProxy;

/**
 * Tarifas de envío configuradas en el admin:
 *   Transportistas (/admin/carrier) + Zonas (/admin/zone) + Métodos de envío (/admin/shipping-method)
 * Una zona agrupa departamentos; cada método de envío une un courier con una zona y su tarifa.
 */
class Envio
{
    /** Departamentos de Guatemala ordenados por nombre */
    public static function departamentos(string $country = 'GT'): Collection
    {
        return ProvinceProxy::where('country_id', $country)->orderBy('name')->get(['id', 'name']);
    }

    /** Métodos de envío activos que llegan al país/departamento indicado */
    public static function metodosPara(?string $country, ?int $provinceId): Collection
    {
        if (!$country) {
            return collect();
        }

        $destino = AddressProxy::make(['country_id' => $country, 'province_id' => $provinceId]);
        $zonas = Zones::withShippingScope()->theAddressBelongsTo($destino);
        if ($zonas->isEmpty()) {
            return collect();
        }

        return ShippingMethodProxy::availableOnesForZones(...$zonas->all())->load('carrier');
    }

    /**
     * Precio y fechas de un método para un total de productos.
     * @return array{id:int, nombre:string, courier:?string, costo:float, gratis_desde:?float, desde:?string, hasta:?string}
     */
    public static function cotizar($method, float $itemsTotal): array
    {
        $subject = new class($itemsTotal) {
            public function __construct(private float $total) {}
            public function itemsTotal(): float { return $this->total; }
        };

        try {
            $costo = (float) $method->estimate($subject)->amount()->getValue();
        } catch (\Throwable $e) {
            // Tarifa mal configurada en el admin (ej. falta "cost"): no se ofrece
            report($e);
            return [];
        }

        $config = $method->configuration();
        [$desde, $hasta] = self::fechas($method);

        return [
            'id' => $method->id,
            'nombre' => $method->name,
            'courier' => $method->carrier?->name,
            'costo' => max(0.0, round($costo, 2)),
            'gratis_desde' => isset($config['free_threshold']) ? (float) $config['free_threshold'] : null,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /** Opciones de envío (cotizadas) para un destino, de la más barata a la más cara */
    public static function opciones(?string $country, ?int $provinceId, float $itemsTotal): Collection
    {
        return self::metodosPara($country, $provinceId)
            ->map(fn ($m) => self::cotizar($m, $itemsTotal))
            ->filter()
            ->sortBy('costo')
            ->values();
    }

    /** Todos los métodos activos con los departamentos/países que cubren (para el checkout) */
    public static function todosConCobertura(float $itemsTotal): Collection
    {
        return ShippingMethodProxy::actives()->with(['carrier', 'zone.members'])->get()
            ->map(function ($method) use ($itemsTotal) {
                $opcion = self::cotizar($method, $itemsTotal);
                if (!$opcion || !$method->zone) {
                    return null;
                }
                $miembros = $method->zone->members;
                $tipo = fn ($m) => $m->member_type->value();
                $opcion['paises'] = $miembros->filter(fn ($m) => $tipo($m) === 'country')->pluck('member_id')->values()->all();
                $opcion['departamentos'] = $miembros->filter(fn ($m) => $tipo($m) === 'province')->pluck('member_id')->map(fn ($id) => (int) $id)->values()->all();

                return $opcion;
            })
            ->filter()
            ->sortBy('costo')
            ->values();
    }

    /** ¿El método llega a ese destino? */
    public static function llegaA(int|string|null $methodId, ?string $country, ?int $provinceId): bool
    {
        return $methodId !== null && self::metodosPara($country, $provinceId)->contains('id', (int) $methodId);
    }

    /** Fechas estimadas de entrega según el tiempo configurado en el método (en días hábiles) */
    private static function fechas($method): array
    {
        $min = $method->eta_min;
        $max = $method->eta_max ?? $min;
        if ($min === null && $max === null) {
            return [null, null];
        }

        $factor = match ($method->eta_units) {
            'w' => 5,
            'h', 'm', 's' => 0,
            default => 1,
        };
        $min = (int) ceil(($min ?? $max) * $factor);
        $max = (int) ceil($max * $factor);

        return [CartController::businessDaysFromToday($min), CartController::businessDaysFromToday($max)];
    }
}
