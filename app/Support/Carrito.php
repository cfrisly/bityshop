<?php

namespace App\Support;

use Vanilo\Cart\Facades\Cart;

/**
 * Cálculos del carrito que comparten la página del carrito, el checkout y el pedido,
 * para que el total que ve el cliente sea siempre el mismo que se cobra.
 */
class Carrito
{
    /** Suma de precio x cantidad, sin descuentos */
    public static function subtotal(): float
    {
        if (Cart::isEmpty()) {
            return 0.0;
        }

        return round((float) Cart::getItems()->sum(fn ($item) => $item->price * $item->quantity), 2);
    }

    /** Descuentos de cupones y promociones de Vanilo (número negativo o cero) */
    public static function descuento(): float
    {
        if (Cart::isEmpty()) {
            return 0.0;
        }

        return round((float) Cart::total() - self::subtotal(), 2);
    }

    /** Costo de envío según config/tienda.php (0 si alcanza el envío gratis) */
    public static function costoEnvio(?float $subtotal = null): float
    {
        $subtotal ??= self::subtotal();
        $gratisDesde = (float) config('tienda.envio.gratis_desde');

        if ($subtotal <= 0 || ($gratisDesde > 0 && $subtotal >= $gratisDesde)) {
            return 0.0;
        }

        return max(0.0, (float) config('tienda.envio.costo'));
    }

    /** Total a pagar: productos + descuentos + envío */
    public static function total(): float
    {
        $subtotal = self::subtotal();

        return round($subtotal + self::descuento() + self::costoEnvio($subtotal), 2);
    }
}
