@extends('layouts.app')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('product.index') }}">{{ __('All Products') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cart.show') }}">{{ __('Cart') }}</a></li>
    <li class="breadcrumb-item">{{ __('Checkout') }}</li>
    <li class="breadcrumb-item">{{ __('Order Complete') }}</li>

@stop

@section('content')
    <div class="container">
        <h1>{{ __('Thank you, :name!', ['name' => $order->getBillpayer()->firstname]) }}</h1>
        <hr>

        <div class="alert alert-success">{!! __('Your order has been registered with number :number.', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}</div>

        <h3>{{ __('Payment') }}</h3>

        {!! $paymentRequest->getHtmlSnippet(); !!}

        @unless($paymentRequest->willRedirect())
            @include('checkout._final_success_text')
        @endunless

    </div>
@endsection
