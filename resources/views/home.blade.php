@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">

        {{-- ========================================= --}}
        {{-- BARRA LATERAL --}}
        {{-- ========================================= --}}
        <div class="col-md-3">

            <div class="card">
                <div class="card-body">

                    {{-- Perfil del cliente --}}
                    <div class="profile-header">
                        {{-- Avatar --}}
                        <div class="profile-avatar-container">
                            <div class="profile-avatar">
                                <span>👤</span>
                            </div>

                            {{-- Boton editar --}}
                            <a href="{{ route('home', ['section' => 'profile', 'edit'=> 1]) }}" 
                            class="profile-edit-button"
                            title="Editar mi perfil">
                                ✎
                            </a>
                        </div>

                        {{-- Información --}}
                        <div class="profile-info">
                            <div class="profile-greeting">
                                ¡Hola {{ Auth::user()->name }}!
                            </div>

                            <a href="{{ route('home', ['section' => 'profile', 'edit'=> 1]) }}"
                            class="profile-edit-link">
                                Editar mi perfil
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                    <hr>

                    <ul class="list-group list-group-flush">

                        {{-- PERFIL --}}
                        <li class="list-group-item">
                            <a href="{{ route('home', ['section' => 'profile']) }}"
                               class="text-decoration-none">
                                👤 Perfil
                            </a>
                        </li>

                        {{-- FAVORITOS --}}
                        <li class="list-group-item">
                            <a href="{{ route('home', ['section' => 'favorites']) }}"
                               class="text-decoration-none">
                                ❤️ Favoritos
                            </a>
                        </li>

                        {{-- MIS PEDIDOS --}}
                        <li class="list-group-item">
                            <a href="{{ route('home', ['section' => 'orders']) }}"
                               class="text-decoration-none">
                                📦 Mis pedidos
                            </a>
                        </li>

                        {{-- DIRECCIONES --}}
                        <li class="list-group-item">
                            <a href="{{ route('home', ['section' => 'addresses']) }}"
                               class="text-decoration-none">
                                📍 Direcciones guardadas
                            </a>
                        </li>

                        {{-- TARJETAS --}}
                        <li class="list-group-item">
                            <a href="{{ route('home', ['section' => 'cards']) }}"
                               class="text-decoration-none">
                                💳 Tarjetas guardadas
                            </a>
                        </li>

                    </ul>

                </div>
            </div>

        </div>


        {{-- ========================================= --}}
        {{-- CONTENIDO PRINCIPAL --}}
        {{-- ========================================= --}}
        <div class="col-md-9">

            @php
                $section = request('section', 'orders');
            @endphp


            {{-- ========================================= --}}
            {{-- PERFIL --}}
            {{-- ========================================= --}}
            @if ($section == 'profile')

                @php
                    $user = Auth::user();
                    // Si hubo error al guardar, se queda en modo edicion
                    $editing = request('edit') || $errors->any();
                @endphp

                <div class="card">

                    <div class="card-header d-flex justify-content-between align-item-center">
                        <h5 class="mb-0">Mi Perfil</h5>

                        @unless($editing)
                            <a href="{{ route('home', ['section' => 'profile', 'edit' => 1]) }}"
                                class="btn btn-sm btn-outline-danger">
                                ✎ Editar
                            </a>
                        @endunless
                    </div>

                    <div class="card-body">

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($editing)
                            {{--Modo_edicion--}}
                            <form method="post" action="{{ route('profile.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="fw-bold">Nombre</label>
                                        <input type="text" id="name" name="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name', $user->name) }}" required
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="fw-bold">Correo electronico</label>
                                        <input type="email" id="email" name="email" 
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}" required>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('home', ['section' => 'profile']) }}"
                                    class="bt btn-secondary">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        Actualizar
                                    </button>
                                </div>
                            </form>
                        @else
                            {{--Modo_Lectura--}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Nombre</label>
                                    <div class="form-control bg-light">{{ $user->name }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Correo electronico</label>
                                    <div class="form-control bg-light">{{ $user->email }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Usuario</label>
                                    <div class="form-control bg-light">{{ $user->email }}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md6 mb-3">
                                    <label class="fw-bold">Cliente desde</label>
                                    <div class="form-control bg-light">
                                        {{ $user->created_at?->format('d/m/Y') }}
                                    </div>
                                </div>
                            </div>

                        @endif

                    </div>

                </div>


            {{-- ========================================= --}}
            {{-- MIS PEDIDOS --}}
            {{-- ========================================= --}}
            @elseif ($section == 'orders')

                <div class="card">

                    <div class="card-header">
                        <h5>Mis Pedidos</h5>
                    </div>

                    <div class="card-body">

                        @if ($orders->isEmpty())

                            <div class="alert alert-info">
                                Aún no tienes pedidos realizados.
                            </div>

                        @else

                            @foreach ($orders as $order)

                                <div class="card mb-4">

                                    {{-- CABECERA DEL PEDIDO --}}
                                    <div class="card-header">

                                        <div class="d-flex justify-content-between align-items-center">

                                            {{-- Información del pedido --}}
                                            <div>

                                                <strong>
                                                    Orden #{{ $order->number }}
                                                </strong>

                                                <br>

                                                <small class="text-muted">
                                                    Fecha:
                                                    {{ \Carbon\Carbon::parse($order->ordered_at)->format('d/m/Y H:i') }}
                                                </small>

                                            </div>


                                            {{-- Estado y botón --}}
                                            <div class="d-flex align-items-center gap-2">

                                                <span class="badge bg-secondary">
                                                    {{ ucfirst($order->status) }}
                                                </span>


                                                {{-- Cancelar pedido --}}
                                                @if (strtolower($order->status) == 'pending')

                                                    <form
                                                        action="{{ route('orders.cancel', $order->id) }}"
                                                        method="POST"
                                                        style="display: inline;"
                                                        onsubmit="return confirm('Seguro que deseas cancelar este pedido?');"
                                                    >

                                                        @csrf

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-danger"
                                                        >
                                                            Cancelar pedido
                                                        </button>

                                                    </form>

                                                @endif

                                            </div>

                                        </div>

                                    </div>


                                    {{-- PRODUCTOS DEL PEDIDO --}}
                                    <div class="card-body">

                                        @foreach ($order->items as $item)

                                            <div class="row align-items-center mb-3">

                                                {{-- Imagen --}}
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

                                                            <span style="font-size: 35px;">
                                                                🛍️
                                                            </span>

                                                        @endif

                                                    </div>

                                                </div>


                                                {{-- Información --}}
                                                <div class="col-md-6">

                                                    <h6 class="mb-1">
                                                        {{ $item->name }}
                                                    </h6>

                                                    <small class="text-muted">
                                                        Código:
                                                        {{ $item->product->sku ?? 'Sin codigo' }}
                                                    </small>

                                                    <br>

                                                    <small>
                                                        Cantidad:
                                                        {{ $item->quantity }}
                                                    </small>

                                                </div>


                                                {{-- Precio --}}
                                                <div class="col-md-4 text-end">

                                                    <strong>
                                                        {{ format_price($item->price) }}
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


            {{-- ========================================= --}}
            {{-- FAVORITOS --}}
            {{-- ========================================= --}}
            @elseif ($section == 'favorites')

                <div class="card">

                    <div class="card-header">
                        <h5>Mis Favoritos</h5>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-info">
                            Aún no tienes productos favoritos.
                        </div>

                    </div>

                </div>


            {{-- ========================================= --}}
            {{-- DIRECCIONES --}}
            {{-- ========================================= --}}
            @elseif ($section == 'addresses')

                <div class="card">

                    <div class="card-header">
                        <h5>Direcciones guardadas</h5>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-info">
                            Aquí aparecerán tus direcciones guardadas.
                        </div>

                    </div>

                </div>


            {{-- ========================================= --}}
            {{-- TARJETAS --}}
            {{-- ========================================= --}}
            @elseif ($section == 'cards')

                <div class="card">

                    <div class="card-header">
                        <h5>Tarjetas guardadas</h5>
                    </div>

                    <div class="card-body">

                        <div class="alert alert-info">
                            Aquí aparecerán tus tarjetas guardadas.
                        </div>

                    </div>

                </div>

            @endif

        </div>

    </div>
</div>
@endsection

<style>

    /* ========================================
       PERFIL DEL CLIENTE
    ======================================== */

    .profile-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 5px 0 10px 0;
    }


    /* Avatar */

    .profile-avatar-container {
        position: relative;
        width: 60px;
        min-width: 60px;
        height: 60px;
    }

/*cambia la forma del circulo o cuadrado */
    .profile-avatar {
        width: 60px;
        height: 60px;

        border-radius: 50%;

        background: #f1f1f6;

        border: 2px solid #dedee8;

        display: flex;
        align-items: center;
        justify-content: center;

        overflow: hidden;
    }

/*cambia el tama;o del avatar*/
    .profile-avatar span {
        font-size: 52px;
        opacity: 0.35;
    }


    /* Botón rojo de editar */

    .profile-edit-button {
        position: absolute;

        right: -2px;
        bottom: 3px;

        width: 22px;
        height: 22px;

        border-radius: 50%;

        background: #ff5b4d;
        color: white;

        display: flex;
        align-items: center;
        justify-content: center;

        text-decoration: none;

        font-size: 11px;

        border: 3px solid white;

        transition: 0.2s;
    }


    .profile-edit-button:hover {
        background: #e94a3c;
        color: white;

        transform: scale(1.05);
    }


    /* Información */

    .profile-info {
        display: flex;
        flex-direction: column;

        justify-content: center;
    }

/* Cambia el texto de saludo hola */
    .profile-greeting {
        font-size: 15px;
        font-weight: 700;

        color: #292942;

        line-height: 1.2;

        margin-bottom: 8px;
    }

/**texto editar fuera del circulo */
    .profile-edit-link {
        color: #55556c;

        text-decoration: none;

        font-size: 11px;

        display: flex;
        align-items: center;

        gap: 8px;
    }

/**Cambia el tama;o de la flecha */
    .profile-edit-link span {
        font-size: 11px;
        line-height: 15px;
    }


    .profile-edit-link:hover {
        color: #292942;
    }


    /* ========================================
       MENU LATERAL
    ======================================== */

    .list-group-item a {
        color: #34344a;
    }


    .list-group-item a:hover {
        color: #ff5b4d;
    }

</style>
