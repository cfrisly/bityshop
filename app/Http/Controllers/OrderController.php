<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vanilo\Order\Models\OrderProxy;
use Vanilo\Order\Models\OrderStatus;

class OrderController extends Controller
{
    public function cancel($id){
        $order = OrderProxy::findOrFail($id);

        //Verificar que el pedido pertenece al usuario autenticado
        if($order->user_id != auth()->id()) {
            abort(403, 'No tienes permiso para cancelar este pedido');
        }

        //Solo puede cancelar pedidos pendites
        if($order->status->value() != 'pending') {
            return redirect()
                ->route('home')
                ->with('error', 'Este pedido ya no puede ser cancelado');
        }

        //Cancelar pedido
        $order->status = OrderStatus::create('cancelled');
        $order->save();

        return redirect()
            ->route('home')
            ->with('success', 'El pedido fue cancelado correctamente.');
    }
}
