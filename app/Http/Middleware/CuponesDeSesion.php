<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * El checkout de Vanilo (driver "request") lee los cupones de la petición.
 * Este middleware pone en cada petición los cupones que el cliente aplicó
 * en el carrito (guardados en la sesión), para que Vanilo calcule el
 * descuento en el carrito y lo copie al pedido.
 * Se reemplaza cualquier valor que venga del navegador para que no se
 * puedan inyectar cupones sin validar.
 */
class CuponesDeSesion
{
    public const CLAVE = 'carrito.cupones';

    public function handle(Request $request, Closure $next)
    {
        $request->merge(['coupons' => $request->session()->get(self::CLAVE, [])]);

        return $next($request);
    }
}
