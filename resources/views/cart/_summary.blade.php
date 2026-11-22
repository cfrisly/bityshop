@php
    $sumSubtotal = \App\Support\Carrito::subtotal();
    $sumDescuento = \App\Support\Carrito::descuento();
    $sumBase = \App\Support\Carrito::totalSinEnvio();
@endphp
<table class="table table-borderless table-condensed">
    <tr>
        <th class="pl-0">{{ __('Products') }}:</th>
        <td>{{ format_price($sumSubtotal) }}</td>
    </tr>
    @if($sumDescuento < 0)
    <tr class="text-success">
        <th class="pl-0">Descuento:</th>
        <td>-{{ format_price(abs($sumDescuento)) }}</td>
    </tr>
    @endif
    @if(isset($shippingOptions) && $shippingOptions->isNotEmpty())
    <tr>
        <th class="pl-0">Envío:</th>
        <td data-resumen-envio>Elige una opción</td>
    </tr>
    @endif
</table>

<h5>{{ __('Total') }}:</h5>
<h3 data-resumen-total data-base="{{ $sumBase }}">{{ format_price($sumBase) }}</h3>

<hr>
