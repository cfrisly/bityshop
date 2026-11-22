<?php

namespace App\Listeners;

use Vanilo\Adjustments\Models\AdjustmentProxy;
use Vanilo\Adjustments\Models\AdjustmentTypeProxy;
use Vanilo\Cart\Contracts\CartEvent;
use Vanilo\Checkout\Contracts\CheckoutEvent;
use Vanilo\Foundation\Listeners\CalculatePromotions as VaniloCalculatePromotions;
use Vanilo\Promotion\Models\PromotionProxy;
use Vanilo\Support\Dto\DetailedAmount;

/**
 * Igual que el cálculo de promociones de Vanilo, pero respeta el interruptor
 * "Exclusiva" del admin (Vanilo lo guardaba y no lo usaba):
 * si se aplica una promoción exclusiva, se quitan las demás.
 * Si hay varias exclusivas, gana la de mayor prioridad; si empatan, la que da más descuento.
 */
class CalculatePromotions extends VaniloCalculatePromotions
{
    public function handle(CheckoutEvent|CartEvent $event): void
    {
        parent::handle($event);

        if (null === $this->cart || $this->theCartModelIsNotAdjustable()) {
            return;
        }

        $this->dejarSoloLaExclusiva();
    }

    private function dejarSoloLaExclusiva(): void
    {
        // Se consulta la base directamente: la lista en memoria del carrito puede estar desactualizada
        $items = $this->cart->getItems();
        $ajustes = AdjustmentProxy::query()
            ->where('type', 'promotion')
            ->where(function ($q) use ($items) {
                $q->where(fn ($c) => $c->where('adjustable_type', $this->cartModel->getMorphClass())->where('adjustable_id', $this->cartModel->id));
                if ($items->isNotEmpty()) {
                    $q->orWhere(fn ($c) => $c->where('adjustable_type', $items->first()->getMorphClass())->whereIn('adjustable_id', $items->pluck('id')));
                }
            })
            ->get();

        $ids = $ajustes->pluck('source_id')->filter()->unique();
        if ($ids->count() < 2) {
            return; // una sola promoción: no hay nada que resolver
        }

        $exclusivas = PromotionProxy::whereIn('id', $ids)->where('is_exclusive', true)->get();
        if ($exclusivas->isEmpty()) {
            return;
        }

        // Descuento total de cada promoción (los montos son negativos)
        $descuento = $ajustes->groupBy('source_id')->map(fn ($grupo) => $grupo->sum(fn ($a) => $a->getAmount()));
        $ganadora = $exclusivas
            ->sort(fn ($a, $b) => [(int) $b->priority, $descuento[$a->id] ?? 0] <=> [(int) $a->priority, $descuento[$b->id] ?? 0])
            ->first();

        $monto = new DetailedAmount(0);
        foreach ($ajustes as $ajuste) {
            if ((int) $ajuste->source_id === (int) $ganadora->id) {
                $monto->addDetail($ajuste->getTitle(), $ajuste->getAmount());
            } else {
                $ajuste->delete();
            }
        }

        $this->checkout->setPromotionsAmount($monto);
    }
}
