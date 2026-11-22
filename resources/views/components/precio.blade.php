@props(['valor'])
@php
    [$entero, $centavos] = explode('.', number_format((float) $valor, 2, '.', ','));
@endphp
<span {{ $attributes->merge(['class' => 'precio']) }}>Q{{ $entero }}<sup>{{ $centavos }}</sup></span>
