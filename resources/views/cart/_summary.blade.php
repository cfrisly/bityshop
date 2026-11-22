@php
    $sumSubtotal = \App\Support\Carrito::subtotal();
    $sumDescuento = \App\Support\Carrito::descuento();
    $sumEnvio = \App\Support\Carrito::costoEnvio($sumSubtotal);
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
    <tr>
        <th class="pl-0">Envío:</th>
        <td>{{ $sumEnvio > 0 ? format_price($sumEnvio) : 'Gratis' }}</td>
    </tr>
</table>

<h5>{{ __('Total') }}:</h5>
<h3>{{ format_price(\App\Support\Carrito::total()) }}</h3>

<hr>
