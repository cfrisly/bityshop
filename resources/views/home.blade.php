@extends('layouts.app')

@section('content')
<div class="container">
   <div class="row">
       <div class="col-md-3">
           <div class="card">
               <div class="card-body">
                   <!--<div class="panel panel-default">
                       <div class="panel-heading">
                           {{ Auth::user()->name }}
                       </div>
                   </div>-->
                   <div class="customer-icon">
                        👤
                   </div>
                   <h5>Bienvenido {{ Auth::user()->name }}</h5>
                   <hr>
                   <ul class="list-group list-group-flush">
                       <li class="list-group-item">
                           <a href="#">
                               ❤️ Favoritos
                           </a>
                       </li>

                       <li class="list-group-item">
                           <a href="#">
                               📦 Mis pedidos
                           </a>
                       </li>

                       <li class="list-group-item">
                           <a href="#">
                               📍 Direcciones guardadas
                           </a>
                       </li>

                       <li class="list-group-item">
                           <a href="#">
                               💳 Tarjetas guardadas
                           </a>
                       </li>
                   </ul>
               </div>
           </div>
       </div>

       {{-- Contenido principal --}}
       <div class="col-md-9">
           <div class="card">
               <div class="card-header">
                   <h5>Mis Pedidos</h5>
               </div>

               <div class="card-body">
                   @if ($orders->isEmpty())
                        <div class="alert alert-info">
                            Aun no tienes pedidos realizados
                        </div>
                    @else
                        @foreach ($orders as $order)
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Orden #{{ $order->number }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Fecha:
                                            {{ \Carbon\Carbon::parse($order->ordered_at)->format('d/m/Y H:i') }}
                                        </small>
                                    </div>

                                    <span class="badge bg-secondary">
                                        {{ ucfirst($order->status) }}
                                    </span>

                                    @if ($order->status === 'pending')
                                        <form 
                                        action="{{ route('orders.cancel', $order->id) }}"
                                        method="POST"
                                        style="display: inline;"
                                        onsubmit="return confirm('Seguro que deseas cancelar este pedido?');"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger ms-2"
                                            >
                                                Cancelar pedido
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </div>

                            <div class="card-body">
                                @if ($orders->isEmpty())
                                    <div class="alert alert-info">
                                        Aun no tienes pedidos realizados.
                                    </div>
                                @else
                                
                                @foreach ($order->items as $item)
                                    <div class="row align-items-center mb-3">
                                        {{-- Imagen del producto --}}
                                        <div class="col-md-2">
                                            <div class="border rounded p-2 text-center">
                                                @if ($item->product && $item->product->hasImage())
                                                    <img
                                                        src="{{ $item->product->getThumbnailUrl() }}"
                                                        alt="{{ $item->name }}"
                                                        class="img-fluid"
                                                        style="max-height: 100px; object-fit: contain;"
                                                    >
                                                @else
                                                    <span style="font-size: 35px;">🛍️</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Informacion del producto --}}
                                        <div class="col-md-6">
                                            <h6 class="mb-1">
                                                {{ $item->name }}
                                            </h6>
                                            <small class="text-muted">
                                                Producto ID: {{ $item->product_id }}
                                            </small>
                                            <br>
                                            <small>
                                                Cantidad: {{ $item->quantity}}
                                            </small>
                                        </div>

                                        {{-- Precio --}}
                                        <div class="col-md-4 text-end">
                                            <strong>
                                                {{ number_format($item->price, 2) }}
                                            </strong>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                        @endforeach
                    @endif
               </div>
           </div>
       </div>

       <!--<div class="col-md-8 col-md-offset-2">
           <div class="panel panel-default">
               <div class="panel-heading">Dashboard Pedidos</div>

               <div class="panel-body">
                   @if (session('status'))
                       <div class="alert alert-success">
                           {{ session('status') }}
                       </div>
                   @endif

                   You are logged in! Prueba

               </div>
           </div>
       </div>-->
   </div>
</div>
@endsection
