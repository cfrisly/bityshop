<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CuponesDeSesion;
use App\Http\Requests\CheckoutRequest;
use App\Support\Carrito;
use Vanilo\Adjustments\Adjusters\SimpleShippingFee;
use Vanilo\Checkout\Events\CouponUtilized;
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
        ]);
    }

    public function submit(CheckoutRequest $request, OrderFactory $orderFactory)
    {
        $this->checkout->update($request->all());
        $this->checkout->setCustomAttribute('notes', $request->get('notes'));
        $this->checkout->setCart($this->cart);

        // Se calcula antes de vaciar el carrito
        $shippingCost = Carrito::costoEnvio();
        $usedCoupons = Carrito::descuento() < 0 ? $this->checkout->getCoupons() : [];

        /** @var Order $order */
        $order = $orderFactory->createFromCheckout($this->checkout);
        if (auth()->check()){
            $order->user_id = auth()->id();
        }
        $order->notes = $request->get('notes');
        $order->save();

        // El envío que se mostró en el carrito se cobra en el pedido
        if ($shippingCost > 0) {
            $order->adjustments()->create(new SimpleShippingFee($shippingCost))->update(['title' => 'Envío']);
        }

        // Cuenta el uso de cada cupón y lo quita de la sesión
        foreach ($usedCoupons as $code) {
            event(new CouponUtilized($this->checkout, $code));
        }
        session()->forget(CuponesDeSesion::CLAVE);

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
