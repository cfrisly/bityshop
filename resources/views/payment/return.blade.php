@extends('layouts.app')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('product.index') }}">{{ __('All Products') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cart.show') }}">{{ __('Cart') }}</a></li>
    <li class="breadcrumb-item">{{ __('Checkout') }}</li>
    <li class="breadcrumb-item">{{ __('Payment') }}</li>

@stop

@section('content')
    <div class="container">
        <h1>{{ __('Payment of order :number', ['number' => $order->getNumber()]) }}</h1>

        @if(isset($response))
            @if ($response->getReturnStatus()->isSuccess())
                <div class="alert alert-success">
                    {!! __('Your payment for order :number was successful.', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                </div>
            @elseif ($response->getReturnStatus()->isFail())
                <div class="alert alert-danger">
                    {!! __('Payment for order :number has been declined.', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                </div>
            @elseif ($response->getReturnStatus()->isTimeout())
                <div class="alert alert-danger">
                    {!! __('Payment for order :number has timed out.', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                </div>
            @elseif ($response->getReturnStatus()->isCancel())
                <div class="alert alert-info">
                    {!! __('Payment for order :number has been canceled.', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                </div>
            @endif
        @else
            @if($payment->getStatus()->isPaid() || $payment->getStatus()->isAuthorized())
                <div class="alert alert-success">
                    {!! __('Your payment for order :number was successful:', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                    <em>{{ $payment->status_message }}</em>
                </div>
            @elseif($payment->getStatus()->isPending())
                <div class="alert alert-info">
                    {!! __('Payment for order :number is being processed', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                    @if(null !== $payment->status_message)
                        <div>{{ __('Last known status is:') }} <em>{{ $payment->status_message }}</em></div>
                    @endif
                </div>
            @elseif($payment->getStatus()->isOnHold())
                <div class="alert alert-warning">
                    {!! __('Payment for order :number has been accepted but the transaction requires additional security checks to complete.', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                    @if(null !== $payment->status_message)
                        <div><em>{{ $payment->status_message }}</em></div>
                    @endif
                </div>
            @elseif($payment->getStatus()->isDeclined())
                <div class="alert alert-danger">
                    {!! __('Payment for order :number has been declined:', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                    <em>{{ $payment->status_message }}</em>
                </div>
            @elseif($payment->getStatus()->isTimeout())
                <div class="alert alert-danger">
                    {!! __('Payment for order :number has timed out.', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                    <em>{{ $payment->status_message }}</em>
                </div>
            @else
                <div class="alert alert-warning">
                    {!! __('Payment status for order :number is:', ['number' => '<strong>' . e($order->getNumber()) . '</strong>']) !!}
                    <strong>{{ $payment->getStatus()->label() }}</strong>
                    @if(null !== $payment->status_message)
                        <div>{{ __('The last known status message is:') }} <em>{{ $payment->status_message }}</em></div>
                    @endif
                </div>
            @endif

            @if($payment->getStatus()->isPaid()|| $payment->getStatus()->isAuthorized())
                <h2>{{ __('Next Steps') }}</h2>
                <p>{{ __('Your order has been registered and the products will be handed over to shipping within 24 hours.') }}</p>
            @endif
        @endif
    </div>
@endsection
