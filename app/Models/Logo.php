<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Logo de la tienda. El principal se usa todo el año; los de temporada
 * (Navidad, Halloween, Semana Santa...) solo entre "desde" y "hasta".
 * Con "cada_anio" las fechas se repiten todos los años (solo cuenta día y mes).
 */
class Logo extends Model
{
    protected $fillable = ['nombre', 'imagen', 'es_principal', 'desde', 'hasta', 'cada_anio', 'activo'];

    protected $casts = [
        'es_principal' => 'bool',
        'cada_anio' => 'bool',
        'activo' => 'bool',
        'desde' => 'date',
        'hasta' => 'date',
    ];

    public function url(): string
    {
        return Storage::disk('public')->url($this->imagen);
    }

    /** ¿Está dentro de sus fechas en el día indicado? */
    public function vigenteEn(Carbon $dia): bool
    {
        if ($this->es_principal || !$this->desde || !$this->hasta) {
            return false;
        }

        if (!$this->cada_anio) {
            return $dia->betweenIncluded($this->desde->copy()->startOfDay(), $this->hasta->copy()->endOfDay());
        }

        // Se compara solo mes y día (ej. 1220 = 20 de diciembre)
        $hoy = (int) $dia->format('md');
        $inicio = (int) $this->desde->format('md');
        $fin = (int) $this->hasta->format('md');

        // Rango que cruza el año (ej. 15 de diciembre al 6 de enero)
        return $inicio <= $fin ? ($hoy >= $inicio && $hoy <= $fin) : ($hoy >= $inicio || $hoy <= $fin);
    }

    /** Texto de las fechas para el listado del admin */
    public function periodo(): string
    {
        if ($this->es_principal) {
            return __('Todo el año (principal)');
        }
        if (!$this->desde || !$this->hasta) {
            return __('Sin fechas');
        }
        $es = app()->getLocale() === 'es';
        $formato = $this->cada_anio ? ($es ? 'j \d\e F' : 'F j') : ($es ? 'j \d\e F \d\e Y' : 'F j, Y');
        $fecha = fn ($d) => $d->locale(app()->getLocale())->translatedFormat($formato);
        $texto = __(':desde al :hasta', ['desde' => $fecha($this->desde), 'hasta' => $fecha($this->hasta)]);

        return $this->cada_anio ? __(':periodo (cada año)', ['periodo' => $texto]) : $texto;
    }

    /**
     * Logo para la barra de la tienda (una sola consulta por página).
     * Si la tabla todavía no existe (falta migrar), la tienda muestra el nombre.
     */
    public static function paraTienda(): ?self
    {
        if (app()->bound('tienda.logo')) {
            return app('tienda.logo');
        }

        try {
            $logo = static::actual();
        } catch (\Illuminate\Database\QueryException $e) {
            $logo = null;
        }
        app()->instance('tienda.logo', $logo);

        return $logo;
    }

    /** Logo que se muestra hoy: el de temporada vigente o, si no hay, el principal */
    public static function actual(?Carbon $dia = null): ?self
    {
        $dia ??= Carbon::today();
        $logos = static::where('activo', true)->get();

        return $logos->filter(fn (self $logo) => $logo->vigenteEn($dia))
                ->sortByDesc('desde')
                ->first()
            ?? $logos->firstWhere('es_principal', true);
    }
}
