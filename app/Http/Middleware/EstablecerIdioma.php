<?php

namespace App\Http\Middleware;

use App\Support\Ajustes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Aplica en cada página (tienda y administración) el idioma elegido en
 * Admin → Configuración → Tienda → Idioma.
 */
class EstablecerIdioma
{
    public function handle(Request $request, Closure $next)
    {
        $idioma = Ajustes::idioma();
        app()->setLocale($idioma);
        Carbon::setLocale($idioma);

        return $next($request);
    }
}
