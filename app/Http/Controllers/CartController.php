<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CuponesDeSesion;
use App\Support\Carrito;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Vanilo\Cart\Contracts\CartItem;
use Vanilo\Cart\Facades\Cart;
use Vanilo\Checkout\Contracts\Checkout;
use Vanilo\Foundation\Models\MasterProductVariant;
use Vanilo\Product\Contracts\Product;
use Vanilo\Promotion\Models\CouponProxy;

class CartController extends Controller
{
    public function add(Product $product)
    {
        Cart::addItem($product);
        flash()->success($product->name . ' se agregó al carrito');

        return redirect()->route('cart.show');
    }

    public function addVariant(MasterProductVariant $masterProductVariant)
    {
        Cart::addItem($masterProductVariant);
        flash()->success($masterProductVariant->name . ' se agregó al carrito');

        return redirect()->route('cart.show');
    }

    public function remove(CartItem $cart_item)
    {
        // Antes se podía borrar un producto de cualquier carrito cambiando el número en la URL
        if (!$this->isInCurrentCart($cart_item)) {
            return redirect()->route('cart.show');
        }

        Cart::removeItem($cart_item);
        flash()->info($cart_item->getBuyable()->getName() . ' se quitó del carrito');

        return redirect()->route('cart.show');
    }

    public function update(CartItem $cart_item, Request $request)
    {
        if (!$this->isInCurrentCart($cart_item)) {
            return redirect()->route('cart.show');
        }

        $qty = (int) $request->get('qty', $cart_item->getQuantity());
        // Cantidad 0 o negativa: se quita el producto del carrito en lugar de guardar un valor inválido
        if ($qty < 1) {
            return $this->remove($cart_item);
        }
        $cart_item->quantity = min($qty, 999);
        $cart_item->save();

        return redirect()->route('cart.show');
    }

    /** Guarda el producto en favoritos y lo quita del carrito */
    public function moveToFavorites(CartItem $cart_item)
    {
        if (!$this->isInCurrentCart($cart_item)) {
            return redirect()->route('cart.show');
        }

        $product = $cart_item->getBuyable();
        // La tabla favorites solo guarda productos simples
        if (!$product instanceof Product) {
            return redirect()->route('cart.show');
        }

        Auth::user()->favoriteProducts()->syncWithoutDetaching([$product->id]);
        Cart::removeItem($cart_item);
        flash()->success($product->getName() . ' se movió a tus favoritos');

        return redirect()->route('cart.show');
    }

    /** Aplica un cupón creado en el admin (Promociones) */
    public function applyCoupon(Request $request, Checkout $checkout)
    {
        $code = trim((string) $request->input('cupon'));
        $coupon = $code !== '' ? CouponProxy::findByCode($code) : null;

        if (!$coupon || !$coupon->canBeUsed() || !$coupon->getPromotion()->isValid()) {
            return redirect()->route('cart.show')->with('cupon_error', 'Ese cupón no existe o ya no está vigente.');
        }

        $coupons = session(CuponesDeSesion::CLAVE, []);
        if (!in_array($coupon->code, $coupons)) {
            $coupons[] = $coupon->code;
            session([CuponesDeSesion::CLAVE => $coupons]);
        }

        // Vanilo calcula el descuento y lo guarda en el carrito
        $checkout->setCart(Cart::model());
        $checkout->removeCoupon($coupon->code);
        $checkout->addCoupon($coupon->code);

        if (Carrito::descuento() >= 0) {
            flash()->warning('El cupón ' . $coupon->code . ' es válido, pero tu carrito todavía no cumple sus condiciones.');
        } else {
            flash()->success('Cupón ' . $coupon->code . ' aplicado.');
        }

        return redirect()->route('cart.show');
    }

    public function removeCoupon(string $code, Checkout $checkout)
    {
        session([CuponesDeSesion::CLAVE => array_values(array_diff(session(CuponesDeSesion::CLAVE, []), [$code]))]);

        if (Cart::exists()) {
            $checkout->setCart(Cart::model());
            // El checkout todavía tiene el cupón en esta petición: al quitarlo, Vanilo recalcula sin él
            $checkout->removeCoupon($code);
        }

        return redirect()->route('cart.show');
    }

    public function show()
    {
        $subtotal = Carrito::subtotal();
        $shipping = config('tienda.envio');
        $freeFrom = (float) $shipping['gratis_desde'];
        $pickup = config('tienda.recoger');

        return view('cart.show', [
            'subtotal' => $subtotal,
            'discount' => Carrito::descuento(),
            'coupons' => session(CuponesDeSesion::CLAVE, []),
            'shippingCost' => Carrito::costoEnvio($subtotal),
            'freeFrom' => $freeFrom,
            'missingForFree' => $freeFrom > 0 ? max(0, $freeFrom - $subtotal) : null,
            'deliveryFrom' => $this->businessDaysFromToday($shipping['dias_min']),
            'deliveryTo' => $this->businessDaysFromToday($shipping['dias_max']),
            'pickupPlace' => $pickup['lugar'],
            'pickupHours' => $pickup['horario'],
            'pickupDate' => $this->businessDaysFromToday($pickup['dias']),
            'location' => $this->customerLocation() ?? $shipping['zona'],
        ]);
    }

    /** Fecha en español, contando solo días hábiles (ej. "mar. 24 de nov.") */
    public static function businessDaysFromToday(int $days): string
    {
        return Carbon::now()->locale('es')->addWeekdays(max(0, $days))->translatedFormat('D j \d\e M');
    }

    private function isInCurrentCart(CartItem $cart_item): bool
    {
        return Cart::getItems()->contains(fn ($item) => $item->id == $cart_item->id);
    }

    /** Ciudad de la dirección de envío predeterminada del cliente */
    private function customerLocation(): ?string
    {
        $customer = Auth::user()?->customer;
        if (!$customer || !$customer->default_shipping_address_id) {
            return null;
        }

        $address = $customer->addresses()->find($customer->default_shipping_address_id);

        return $address ? trim($address->city . ', ' . ($address->country?->name ?? ''), ', ') : null;
    }
}
