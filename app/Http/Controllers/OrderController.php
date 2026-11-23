<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Vanilo\Order\Models\OrderStatus;

class OrderController extends Controller
{
    public function __construct()
    {
        // Sin sesión no se puede cancelar nada (antes un visitante podía cancelar pedidos de invitados)
        $this->middleware(['auth', 'verified']);
    }

    public function cancel($id)
    {
        // Solo se buscan pedidos del usuario autenticado; si no es suyo responde 404
        $order = Auth::user()->orders()->findOrFail($id);

        //Solo puede cancelar pedidos pendientes
        if ($order->status->value() != OrderStatus::PENDING) {
            return redirect()
                ->route('home', ['section' => 'orders'])
                ->with('error', __('Este pedido ya no puede ser cancelado'));
        }

        //Cancelar pedido
        $order->status = OrderStatus::create(OrderStatus::CANCELLED);
        $order->save();

        return redirect()
            ->route('home', ['section' => 'orders'])
            ->with('success', __('El pedido fue cancelado correctamente.'));
    }
}
