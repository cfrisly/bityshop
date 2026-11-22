<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CuponesDeSesion;
use App\Support\Ajustes;
use App\Support\Carrito;
use App\Support\Envio;
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
    public const REGALO = 'carrito.regalo';

    public function add(Product $product, Request $request)
    {
        $qty = max(1, min(99, (int) $request->input('qty', 1)));
        Cart::addItem($product, $qty);

        // Empaque para regalo (se cobra el costo configurado en Admin → Configuración → Tienda)
        if ($request->boolean('regalo') && Ajustes::regaloCosto() !== null) {
            session([self::REGALO => true]);
        }

        // "Comprar" lleva directo al pago
        if ($request->input('accion') === 'comprar') {
            return redirect()->route('checkout.show');
        }

        flash()->success(__(':producto se agregó al carrito', ['producto' => $product->name]));

        return redirect()->route('cart.show');
    }

    /** Activa o quita el empaque para regalo desde el carrito */
    public function toggleGift(Request $request)
    {
        session([self::REGALO => $request->boolean('regalo') && Ajustes::regaloCosto() !== null]);

        return redirect()->route('cart.show');
    }

    public function addVariant(MasterProductVariant $masterProductVariant)
    {
        Cart::addItem($masterProductVariant);
        flash()->success(__(':producto se agregó al carrito', ['producto' => $masterProductVariant->name]));

        return redirect()->route('cart.show');
    }

    public function remove(CartItem $cart_item)
    {
        // Antes se podía borrar un producto de cualquier carrito cambiando el número en la URL
        if (!$this->isInCurrentCart($cart_item)) {
            return redirect()->route('cart.show');
        }

        Cart::removeItem($cart_item);
        flash()->info(__(':producto se quitó del carrito', ['producto' => $cart_item->getBuyable()->getName()]));

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
        flash()->success(__(':producto se movió a tus favoritos', ['producto' => $product->getName()]));

        return redirect()->route('cart.show');
    }

    /** Aplica un cupón creado en el admin (Promociones) */
    public function applyCoupon(Request $request, Checkout $checkout)
    {
        $code = trim((string) $request->input('cupon'));
        $coupon = $code !== '' ? CouponProxy::findByCode($code) : null;

        if (!$coupon || !$coupon->canBeUsed() || !$coupon->getPromotion()->isValid()) {
            return redirect()->route('cart.show')->with('cupon_error', __('Ese cupón no existe o ya no está vigente.'));
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
            flash()->warning(__('El cupón :codigo es válido, pero tu carrito todavía no cumple sus condiciones.', ['codigo' => $coupon->code]));
        } else {
            flash()->success(__('Cupón :codigo aplicado.', ['codigo' => $coupon->code]));
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
        if ($cambiados = Carrito::actualizarPrecios()) {
            flash()->info(__('Actualizamos el precio de: :productos.', ['productos' => implode(', ', $cambiados)]));
        }

        $address = Carrito::direccionCliente();
        $itemsTotal = Carrito::totalParaEnvio();
        $pickup = config('tienda.recoger');

        // Con dirección: tarifas que llegan a su departamento (la más barata primero)
        $options = $address ? Envio::opciones($address->country_id, $address->province_id, $itemsTotal) : collect();
        $best = $options->first();
        // Sin dirección: precio "desde" con todas las tarifas activas
        $cheapest = $address ? null : Envio::todosConCobertura($itemsTotal)->first();
        $freeFrom = $best['gratis_desde'] ?? null;

        return view('cart.show', [
            'subtotal' => Carrito::subtotal(),
            'discount' => Carrito::descuento(),
            'coupons' => session(CuponesDeSesion::CLAVE, []),
            'giftCost' => Ajustes::regaloCosto(),
            'gift' => Carrito::regalo(),
            'shipping' => $best,
            'shippingFrom' => $cheapest['costo'] ?? null,
            'noCoverage' => $address !== null && $best === null,
            'freeFrom' => $freeFrom,
            'missingForFree' => $freeFrom ? max(0, $freeFrom - $itemsTotal) : null,
            'itemsTotal' => $itemsTotal,
            'pickupPlace' => $pickup['lugar'],
            'pickupHours' => $pickup['horario'],
            'pickupDate' => $this->businessDaysFromToday($pickup['dias']),
            'location' => $address
                ? collect([$address->city, $address->province?->name ?? $address->country?->name])->filter()->unique()->implode(', ')
                : config('tienda.envio.zona'),
        ]);
    }

    /** Fecha en español, contando solo días hábiles (ej. "mar. 24 de nov.") */
    public static function businessDaysFromToday(int $days): string
    {
        // Formato según el idioma de la tienda: "mar. 24 de nov." / "Tue, Nov 24"
        $formato = app()->getLocale() === 'es' ? 'D j \d\e M' : 'D, M j';

        return Carbon::now()->locale(app()->getLocale())->addWeekdays(max(0, $days))->translatedFormat($formato);
    }

    private function isInCurrentCart(CartItem $cart_item): bool
    {
        return Cart::getItems()->contains(fn ($item) => $item->id == $cart_item->id);
    }
}
