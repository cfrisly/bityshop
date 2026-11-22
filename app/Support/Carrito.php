<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Vanilo\Cart\Facades\Cart;

/**
 * Cálculos del carrito que comparten la página del carrito y el checkout,
 * para que el total que ve el cliente sea el mismo que se cobra.
 * El envío sale de las tarifas del admin (ver App\Support\Envio).
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

        // Cart::total() incluye los ajustes del carrito; el envío se cobra aparte en el pedido
        $ajustes = Cart::model()?->adjustments()->byType(\Vanilo\Adjustments\Models\AdjustmentTypeProxy::SHIPPING())->total() ?? 0;

        return round((float) Cart::total() - $ajustes - self::subtotal(), 2);
    }

    /** ¿El cliente pidió empaque para regalo? (y la opción sigue activa en el admin) */
    public static function regalo(): bool
    {
        return (bool) session(\App\Http\Controllers\CartController::REGALO) && Ajustes::regaloCosto() !== null && !Cart::isEmpty();
    }

    /** Costo del empaque para regalo si el cliente lo pidió */
    public static function costoRegalo(): float
    {
        return self::regalo() ? (float) Ajustes::regaloCosto() : 0.0;
    }

    /** Productos + descuentos + empaque para regalo (sin envío) */
    public static function totalSinEnvio(): float
    {
        return round(self::subtotal() + self::descuento() + self::costoRegalo(), 2);
    }

    /** Total de productos que usan las tarifas para el envío gratis */
    public static function totalParaEnvio(): float
    {
        return Cart::isEmpty() ? 0.0 : round((float) Cart::getItems()->sum('total'), 2);
    }

    /** Dirección de envío predeterminada del cliente con sesión (o null) */
    public static function direccionCliente()
    {
        $customer = Auth::user()?->customer;
        if (!$customer || !$customer->default_shipping_address_id) {
            return null;
        }

        return $customer->addresses()->with(['province', 'country'])->find($customer->default_shipping_address_id);
    }
}
