<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CuponesDeSesion;
use App\Http\Requests\CheckoutRequest;
use App\Support\Carrito;
use App\Support\Envio;
use Vanilo\Checkout\Events\ShippingMethodSelected;
use Vanilo\Adjustments\Adjusters\SimpleFee;
use Vanilo\Checkout\Events\CouponUtilized;
use Vanilo\Foundation\Events\PromotionUtilized;
use Vanilo\Promotion\Models\PromotionProxy;
use Konekt\Address\Models\CountryProxy;
use Vanilo\Cart\Contracts\CartManager;
use Vanilo\Checkout\Contracts\Checkout;
use Vanilo\Foundation\Models\Cart;
use Vanilo\Order\Contracts\OrderFactory;
use Vanilo\Foundation\Models\Order;
use Vanilo\Payment\Factories\PaymentFactory;
use Vanilo\Payment\Models\PaymentHistory;
use Vanilo\Payment\Models\PaymentMethod;

class CheckoutController extends Controller
{
    /** @var Checkout */
    private $checkout;

    /** @var Cart */
    private $cart;

    public function __construct(Checkout $checkout, CartManager $cart)
    {
        $this->checkout = $checkout;
        $this->cart     = $cart;
    }

    public function show()
    {
        if ($cambiados = Carrito::actualizarPrecios()) {
            flash()->info('Actualizamos el precio de: ' . implode(', ', $cambiados) . '.');
        }

        $checkout = false;

        if ($this->cart->isNotEmpty()) {
            $checkout = $this->checkout;
            if ($old = old()) {
                $checkout->update($old);
            }

            $checkout->setCart($this->cart);
        }

        return view('checkout.show', [
            'checkout' => $checkout,
            'countries' => CountryProxy::all(),
            'paymentMethods' => PaymentMethod::actives()->get(),
            'shippingOptions' => $checkout ? Envio::todosConCobertura(Carrito::totalParaEnvio()) : collect(),
        ]);
    }

    public function submit(CheckoutRequest $request, OrderFactory $orderFactory)
    {
        // El pedido siempre se cobra con el precio vigente
        Carrito::actualizarPrecios();
        $this->checkout->update($request->all());
        $this->checkout->setCustomAttribute('notes', $request->get('notes'));
        $this->checkout->setCart($this->cart);

        // Tarifa del courier elegido: Vanilo la calcula y la agrega al carrito, y de ahí pasa al pedido
        if ($request->filled('shipping_method_id')) {
            event(new ShippingMethodSelected($this->checkout, null));
        }

        $usedCoupons = Carrito::descuento() < 0 ? $this->checkout->getCoupons() : [];
        $giftCost = Carrito::regalo() ? Carrito::costoRegalo() : null;

        /** @var Order $order */
        $order = $orderFactory->createFromCheckout($this->checkout);
        if (auth()->check()){
            $order->user_id = auth()->id();
        }
        $order->notes = $request->get('notes');
        // Empaque para regalo: queda en las notas del pedido y, si tiene costo, como cargo aparte
        if ($giftCost !== null) {
            $order->notes = trim('[REGALO] Empacar para regalo' . "\n" . $order->notes);
            if ($giftCost > 0) {
                $order->adjustments()->create(new SimpleFee($giftCost))->update(['title' => 'Empaque para regalo']);
            }
        }
        $order->save();

        // Cuenta el uso de cada cupón y lo quita de la sesión
        foreach ($usedCoupons as $code) {
            event(new CouponUtilized($this->checkout, $code));
        }

        // Cuenta un uso por cada promoción aplicada al pedido (Vanilo no lo hacía,
        // por eso el "Límite de uso" de las promociones nunca se cumplía)
        foreach ($this->appliedPromotions($order) as $promotion) {
            event(new PromotionUtilized($this->checkout, $promotion));
        }
        session()->forget([CuponesDeSesion::CLAVE, CartController::REGALO]);

        $this->cart->destroy();

        $paymentMethod = $request->paymentMethod();
        $payment = PaymentFactory::createFromPayable($order, $paymentMethod);
        PaymentHistory::begin($payment);
        $paymentRequest = $paymentMethod
            ->getGateway()
            ->createPaymentRequest($payment, options: $this->gatewayOptions($paymentMethod->getGatewayName(), $payment));

        if ($paymentRequest->getRemoteId()) {
            $payment->update([
                'remote_id' => $paymentRequest->getRemoteId(),
            ]);
        }

        return view('checkout.thankyou', [
            'order' => $order,
            'paymentRequest' => $paymentRequest,
        ]);
    }

    /** Promociones que dejaron un descuento en el pedido o en sus productos */
    private function appliedPromotions(Order $order)
    {
        $morph = (new (PromotionProxy::modelClass()))->getMorphClass();
        $adjustments = collect(iterator_to_array($order->adjustments()));
        foreach ($order->items as $item) {
            $adjustments = $adjustments->concat(iterator_to_array($item->adjustments()));
        }

        $ids = $adjustments
            ->filter(fn ($a) => $a->source_type === $morph && $a->source_id)
            ->pluck('source_id')
            ->unique();

        return $ids->isEmpty() ? collect() : PromotionProxy::whereIn('id', $ids)->get();
    }

    /** Opciones que necesita cada pasarela (antes se mandaban las de Mollie a todas) */
    private function gatewayOptions(string $gateway, $payment): array
    {
        return match ($gateway) {
            'mollie' => [
                'webhookUrl' => route('payment.mollie.webhook'),
                'redirectUrl' => route('payment.mollie.return', $payment->hash),
            ],
            default => [],
        };
    }
}
