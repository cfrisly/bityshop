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

                    //Valores actuales: del cliente, si no hay cliente, se toman del usuario
                    $parts = preg_split('/\s+/', trim($user->name), 2);
                    $firstname = $customer->firstname ?? $parts[0];
                    $lastname = $customer->lastname ?? ($parts[1] ?? '');
                    $phone = $customer->phone ?? '';
                    $companyName = $customer->company_name ?? '';
                    $taxNr = $customer->tax_nr ?? '';
                    $type = $customer ? (string) $customer->type->value() : 'individual';
                    $typeLabels = ['individual' => 'Persona individual', 'organization' => 'Empresa'];
                    $fmt = function ($date, $format = 'd/m/Y') {
                        return $date ? \Carbon\Carbon::parse($date)->format($format) : '-';
                    }
                @endphp

                <div class="card">

                    <div class="card-header d-flex justify-content-between align-item-center">
                        <h5 class="mb-0">Mi Perfil</h5>

                        @if (! $editing)
                            <a href="{{ route('home', ['section' => 'profile', 'edit' => 1]) }}"
                                class="btn btn-sm btn-outline-danger">
                                ✎ Editar
                            </a>
                        @endif

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

                        @if (! $customer && ! $editing)
                            <div class="alert alert-warning">
                                Tu perfil aún no tiene datos de cliente completos.
                                <a href="{{ route('home', ['section' => 'profile', 'edit' => 1]) }}">Complétalos aquí</a>.
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
                                        <input type="text" id="name" name="firstname"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('firstname', $firstname) }}" required
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="fw-bold">Apellido</label>
                                        <input type="text" id="name" name="lastname"
                                            class="form-control @error('lastname') is-invalid @enderror"
                                            value="{{ old('lastname', $lastname) }}" required
                                        >
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="fw-bold">Correo electronico</label>
                                        <input type="email" id="email" name="email" 
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="fw-bold">Teléfono</label>
                                        <input type="text" id="phone" name="phone" class="form-control"
                                            value="{{ old('phone', $phone) }}">
                                    </div>

                                    <div class="col-md-4 md-3">
                                        <label for="type" class="fw-bold">Tipo de cliente</label>
                                        @php $selectdType = old('type', $type); @endphp
                                        <select name="type" id="type" class="form-select form-control">
                                            @foreach ($typeLabels as $value => $label)
                                                <option value="{{ $value }}" {{ old('type', $type) == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="company_name" class="fw-bold">Empresa</label>
                                        <input type="text" id="company_name" name="company_name" class="form-control"
                                            placeholder="Solo si eres empresa"
                                            value="{{ old('company_name', $companyName) }}">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="tax_nr" class="fw-bold">NIT</label>
                                        <input type="text" id="tax_nr" name="tax_nr" class="form-control"
                                            value="{{ old('tax_nr', $taxNr) }}">
                                    </div>

                                    <hr>

                                    <h6 class="text-muted mb-3">Direccion</h6>
                                    <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="address" class="fw-bold">Direccion</label>
                                            <input type="text" id="address" name="address" class="form-control"
                                                placeholder="Calle, avenida, numero de casa"
                                                value="{{ old('address', $address->address ?? '') }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="address2" class="fw-bold">Zona / Referencia</label>
                                            <input type="text" id="address2" name="address2" class="form-control"
                                                value="{{ old('address2', $address->address2 ?? '') }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="city" class="fw-bold">Ciudad / Municipio</label>
                                            <input type="text" id="city" name="city" class="form-control"
                                                value="{{ old('city', $address->city ?? '') }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="postalcode" class="fw-bold">Código postal</label>
                                            <input type="text" id="postalcode" name="postalcode" class="form-control"
                                                value="{{ old('postalcode', $address->postalcode ?? '') }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="country_id" class="fw-bold">País</label>
                                            <select id="country_id" name="country_id" class="form-select form-control">
                                                <option value="">Selecciona un país</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}"
                                                        {{ old('country_id', $address->country_id ?? 'GT') == $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

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
                                    <div class="form-control bg-light">{{ $firstname ?: '-' }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Apellido</label>
                                    <div class="form-control bg-light">{{ $lastname ?: '-' }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Correo electronico</label>
                                    <div class="form-control bg-light">{{ $user->email }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Usuario</label>
                                    <div class="form-control bg-light">{{ $user->email }}</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold">Teléfono</label>
                                    <div class="form-control bg-light">{{ $phone ?: '-' }}</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold">Tipo de cliente</label>
                                    <div class="form-control bg-light">{{ $typeLabels[$type] ?? $type }}</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold">Empresa</label>
                                    <div class="form-control bg-light">{{ $companyName ?: '—' }}</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold">NIT</label>
                                    <div class="form-control bg-light">{{ $taxNr ?: '—' }}</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold">Número de cliente</label>
                                    <div class="form-control bg-light">{{ $customer->customer_number ?? '—' }}</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold">Cliente desde</label>
                                    <div class="form-control bg-light">{{ $fmt($user->created_at) }}</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="fw-bold">Última compra</label>
                                    <div class="form-control bg-light">{{ $fmt($customer->last_purchase_at ?? null) }}</div>
                                </div>

                            </div>

                            <hr>
                            <h6 class="text-muted mb-3">Direccion</h6>
                            @if($address)
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="fw-bold">Dirección</label>
                                        <div class="form-control bg-light">{{ $address->address }}</div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="fw-bold">Zona / Referencia</label>
                                        <div class="form-control bg-light">{{ $address->address2 ?: '—' }}</div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="fw-bold">Ciudad / Municipio</label>
                                        <div class="form-control bg-light">{{ $address->city ?: '—' }}</div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="fw-bold">Código postal</label>
                                        <div class="form-control bg-light">{{ $address->postalcode ?: '—' }}</div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="fw-bold">País</label>
                                        <div class="form-control bg-light">{{ optional($address->country)->name ?? $address->country_id }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-light border">
                                    Aún no tienes una dirección registrada.
                                    <a href="{{ route('home', ['section' => 'profile', 'edit' => 1]) }}">Agrégala aquí</a>.
                                </div>
                            @endif

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

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

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
                        <h5 class="mb-0">Mis Favoritos</h5>
                    </div>

                    <div class="card-body">
                        @if($favorites->isEmpty())
                            <div class="alert alert-info">
                                Aún no tienes productos favoritos.
                            </div>
                        @else
                            @include('product._grid', ['products' => $favorites, 'removeOnUnfavorite' => true])
                        @endif
                    </div>

                </div>


            {{-- ========================================= --}}
            {{-- DIRECCIONES --}}
            {{-- ========================================= --}}

            @elseif ($section == 'addresses')

            @php
                $editingId = request('edit_address');
                $showNew   = request('new') || ($errors->any() && ! $editingId);
                $typeNames = ['shipping' => 'Envío', 'billing' => 'Facturación'];
            @endphp

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Direcciones guardadas</h5>

                    @if (! $showNew && ! $editingId)
                        <a href="{{ route('home', ['section' => 'addresses', 'new' => 1]) }}"
                        class="btn btn-sm btn-outline-danger">
                            + Agregar dirección
                        </a>
                    @endif
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

                    {{-- Formulario para agregar --}}
                    @if ($showNew)
                        @include('partials.address-form', [
                            'action'    => route('addresses.store'),
                            'method'    => 'POST',
                            'addr'      => null,
                            'isDefault' => false,
                        ])
                    @endif

                    {{-- Lista de direcciones --}}
                    @forelse ($addresses as $addr)

                        @php
                            $addrType  = $addr->type->value();
                            $defaultId = $addrType === 'shipping'
                                ? $customer->default_shipping_address_id
                                : $customer->default_billing_address_id;
                            $isDefault = $defaultId == $addr->id;
                        @endphp

                        @if ($editingId == $addr->id)

                            @include('partials.address-form', [
                                'action'    => route('addresses.update', $addr->id),
                                'method'    => 'PUT',
                                'addr'      => $addr,
                                'isDefault' => $isDefault,
                            ])

                        @else

                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">

                                    <div>
                                        <div class="mb-1">
                                            <span class="badge bg-secondary">{{ $typeNames[$addrType] ?? $addrType }}</span>
                                            @if ($isDefault)
                                                <span class="badge bg-success">Predeterminada</span>
                                            @endif
                                        </div>

                                        <strong>{{ $addr->name }}</strong><br>
                                        {{ $addr->address }}{{ $addr->address2 ? ', ' . $addr->address2 : '' }}<br>
                                        {{ $addr->city }}{{ $addr->postalcode ? ', ' . $addr->postalcode : '' }},
                                        {{ optional($addr->country)->name ?? $addr->country_id }}<br>

                                        @if ($addr->phone)
                                            <small class="text-muted">Tel: {{ $addr->phone }}</small><br>
                                        @endif
                                        @if ($addr->tax_nr)
                                            <small class="text-muted">NIT: {{ $addr->tax_nr }}</small>
                                        @endif
                                    </div>

                                    <div class="d-flex flex-column gap-2 align-items-end">
                                        <a href="{{ route('home', ['section' => 'addresses', 'edit_address' => $addr->id]) }}"
                                        class="btn btn-sm btn-outline-secondary">
                                            ✎ Editar
                                        </a>

                                        @if (! $isDefault)
                                            <form action="{{ route('addresses.default', $addr->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    Usar como predeterminada
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('addresses.destroy', $addr->id) }}" method="POST"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar esta dirección?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>

                        @endif

                    @empty

                        @if (! $showNew)
                            <div class="alert alert-info">
                                Aún no tienes direcciones guardadas.
                                <a href="{{ route('home', ['section' => 'addresses', 'new' => 1]) }}">Agrega la primera</a>.
                            </div>
                        @endif

                    @endforelse

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
