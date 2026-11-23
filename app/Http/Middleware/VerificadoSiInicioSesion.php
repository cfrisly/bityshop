<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;

/**
 * Para el checkout: los invitados pueden comprar sin cuenta, pero si el cliente
 * inició sesión con una cuenta que no verificó su correo, primero tiene que verificarlo.
 */
class VerificadoSiInicioSesion
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, __('Verifica tu correo electrónico para continuar.'))
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
