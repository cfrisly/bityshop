<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Vanilo\Payment\Models\Payment;
use Vanilo\Payment\PaymentGateways;
use Vanilo\Payment\Processing\PaymentResponseHandler;

class MollieController extends Controller
{
    public function return(string $paymentId)
    {
        $payment = Payment::findByPaymentId($paymentId);

        if (!$payment) {
            abort(404);
        }

        return view('payment.return', [
            'payment' => $payment,
            'order' => $payment->getPayable(),
        ]);
    }

    public function webhook(Request $request)
    {
        Log::debug('Mollie webhook:', $request->toArray());

        // La pasarela espera la petición completa (lee el campo "id" internamente)
        $response = PaymentGateways::make('mollie')->processPaymentResponse($request);
        $payment = Payment::findByPaymentId($response->getPaymentId());

        if (!$payment) {
            abort(404, 'Could not locate payment with id ' . $response->getPaymentId());
        }

        $handler = new PaymentResponseHandler($payment, $response);
        $handler->writeResponseToHistory();
        $handler->updatePayment();
        $handler->fireEvents();

        return new Response();
    }
}
