@extends('layouts.app')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('product.index') }}">{{ __('All Products') }}</a></li>
    <li class="breadcrumb-item">Carrito</li>
@stop

@section('content')
<style>
    .ct { color: #292942; }
    .ct a { color: #0d6efd; text-decoration: none; }
    .ct a:hover { text-decoration: underline; }
    .ct .precio sup { font-size: .55em; top: -.75em; margin-left: 1px; }
    .ct-card { background: #fff; border: 1px solid #e3e6ea; border-radius: 14px; }
    .ct-title { font-size: 30px; font-weight: 700; margin: 0; }
    .ct-count { font-size: 16px; color: #6c757d; font-weight: 400; }
    .ct-info { display: flex; gap: 12px; align-items: flex-start; padding: 6px 0; }
    .ct-info svg { flex: none; margin-top: 2px; }
    .ct-item { display: flex; gap: 18px; padding: 20px; }
    .ct-item + .ct-item { border-top: 1px solid #e3e6ea; }
    .ct-img { width: 120px; height: 120px; flex: none; border: 1px solid #eef0f2; border-radius: 10px; background: #f7f8f9; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .ct-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .ct-name { font-size: 18px; font-weight: 600; color: #292942 !important; line-height: 1.3; }
    .ct-price { font-size: 22px; font-weight: 700; white-space: nowrap; }
    .ct-price--sale { color: #b12704; }
    .ct-old { color: #6c757d; text-decoration: line-through; font-size: 14px; }
    .ct-save { display: inline-block; background: #e7f6ec; color: #146c43; border-radius: 6px; padding: 1px 8px; font-size: 13px; font-weight: 600; margin: 2px 0; }
    .ct-ok { color: #198754; font-size: 14px; }
    .ct-warn { color: #b26b00; font-size: 14px; }
    .ct-eta { font-size: 14px; color: #495057; }
    .ct-qty { display: inline-flex; align-items: center; border: 1.5px solid #0d6efd; border-radius: 999px; height: 40px; }
    .ct-qty button { border: 0; background: none; width: 40px; height: 38px; color: #0d6efd; display: flex; align-items: center; justify-content: center; border-radius: 999px; }
    .ct-qty button:hover { background: #eef4ff; }
    .ct-qty span { min-width: 26px; text-align: center; font-weight: 600; }
    .ct-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-top: 12px; font-size: 15px; }
    .ct-actions form { display: inline; }
    .ct-link { border: 0; background: none; padding: 0; color: #0d6efd; }
    .ct-link:hover { text-decoration: underline; }
    .ct-sep { color: #ced4da; }
    .ct-foot { border-top: 1px solid #e3e6ea; padding: 16px 20px; text-align: right; font-size: 18px; }
    .ct-sum { background: #f4f5f7; border-radius: 16px; padding: 22px; position: sticky; top: 16px; }
    .ct-bar { height: 8px; border-radius: 4px; background: #dfe3e8; overflow: hidden; margin: 8px 0 18px; }
    .ct-bar div { height: 100%; background: #198754; }
    .ct-coupon { background: #fff; border: 1px solid #e3e6ea; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; }
    .ct-coupon summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 10px; font-weight: 600; }
    .ct-coupon summary::-webkit-details-marker { display: none; }
    .ct-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 16px; }
    .ct-total { display: flex; justify-content: space-between; font-size: 22px; font-weight: 700; border-top: 1px solid #dfe3e8; margin-top: 8px; padding: 12px 0 16px; }
    .ct-pay { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 52px; border-radius: 999px; background: #ffd43b; color: #292942 !important; font-size: 18px; font-weight: 700; border: 0; }
    .ct-pay:hover { background: #fcc419; text-decoration: none !important; }
    .ct-more { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 46px; border-radius: 999px; background: #fff; border: 1px solid #ced4da; color: #292942 !important; font-weight: 600; margin-top: 10px; }
    .ct-more:hover { background: #f8f9fa; text-decoration: none !important; }
    @media (max-width: 575px) {
        .ct-item { gap: 12px; padding: 14px; }
        .ct-img { width: 72px; height: 72px; }
        .ct-name { font-size: 16px; }
        .ct-price { font-size: 18px; }
        .ct-sep { display: none; }
        .ct-title { font-size: 24px; }
    }
</style>

<div class="container ct py-3">
    @if(Cart::isEmpty())
        <div class="ct-card p-5 text-center">
            <h1 class="ct-title mb-2">Tu carrito está vacío</h1>
            <p class="text-muted mb-4">Explora el catálogo y agrega lo que te guste.</p>
            <a href="{{ route('product.index') }}" class="ct-pay d-inline-flex" style="max-width: 280px;">Ver productos</a>
        </div>
    @else
    @php $itemCount = (int) Cart::getItems()->sum('quantity'); @endphp
    <div class="row g-4">
        {{-- Columna izquierda: productos --}}
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h1 class="ct-title">Carrito <span class="ct-count">({{ $itemCount }} {{ $itemCount === 1 ? 'producto' : 'productos' }})</span></h1>
                <a href="{{ auth()->check() ? route('home', ['section' => 'addresses']) : route('login') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    {{ $location }} &#9662;
                </a>
            </div>

            {{-- Envío y recogida --}}
            <div class="ct-card px-3 py-2 mb-3">
                <div class="ct-info">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h11v9H3zM14 9h4l3 3v3h-7z"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                    <div>
                        @if($shipping)
                            <strong>Envío
                                @if($shipping['costo'] > 0) <x-precio :valor="$shipping['costo']"/> @else <span class="text-success">gratis</span> @endif
                            </strong>
                            con {{ $shipping['courier'] ?? $shipping['nombre'] }}
                            @if($shipping['desde']) · {{ $shipping['desde'] === $shipping['hasta'] ? 'recibe el ' . $shipping['desde'] : 'recibe entre ' . $shipping['desde'] . ' y ' . $shipping['hasta'] }} @endif
                            en {{ $location }}
                        @elseif($noCoverage)
                            <strong>Aún no tenemos envío a {{ $location }}.</strong> Puedes elegir otra dirección en el checkout.
                        @else
                            <strong>
                                @if($shippingFrom === null) Envío
                                @elseif($shippingFrom <= 0) Envío <span class="text-success">gratis</span> disponible
                                @else Envío desde <x-precio :valor="$shippingFrom"/>
                                @endif
                            </strong>
                            · el costo exacto depende de tu departamento y se calcula en el checkout
                        @endif
                        <a href="{{ auth()->check() ? route('home', ['section' => 'addresses']) : route('login') }}">{{ auth()->check() ? 'Cambiar' : 'Inicia sesión' }}</a>
                    </div>
                </div>
                @if($pickupPlace)
                <div class="ct-info">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10v10h16V10M3 10l2-6h14l2 6zM9 20v-5h6v5"/></svg>
                    <div><strong>Recoge gratis en {{ $pickupPlace }}</strong> · {{ $pickupDate }} {{ $pickupHours }}</div>
                </div>
                @endif
            </div>

            {{-- Productos --}}
            <div class="ct-card">
                @foreach(Cart::getItems() as $item)
                    @php
                        $buyable = $item->getBuyable();
                        $url = route('product.show', $buyable->masterProduct ? $buyable->masterProduct->slug : $buyable->slug);
                        $stock = (float) ($buyable->stock ?? 0);
                        $inStock = $stock >= $item->quantity || (float) ($buyable->backorder ?? 0) > 0;
                        $antes = (float) ($buyable->original_price ?? 0);
                        $enOferta = $antes > (float) $item->price && (float) $item->price > 0;
                    @endphp
                    <div class="ct-item">
                        <a href="{{ $url }}" class="ct-img">
                            <img src="{{ $buyable->getThumbnailUrl() ?: '/images/product.jpg' }}" alt="{{ $buyable->getName() }}">
                        </a>
                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex justify-content-between gap-3">
                                <a href="{{ $url }}" class="ct-name">{{ $buyable->getName() }}</a>
                                <div class="text-end">
                                    <x-precio :valor="$item->price * $item->quantity" class="ct-price {{ $enOferta ? 'ct-price--sale' : '' }}"/>
                                    @if($enOferta)
                                        <div class="ct-old">Q{{ number_format($antes * $item->quantity, 2) }}</div>
                                    @endif
                                </div>
                            </div>
                            @if($enOferta)
                                <div class="ct-save">Ahorras Q{{ number_format(($antes - $item->price) * $item->quantity, 2) }}</div>
                            @endif
                            @if($inStock)
                                <div class="ct-ok">Disponible</div>
                            @else
                                <div class="ct-warn">
                                    @if((int) $stock === 1) Solo queda 1 unidad
                                    @elseif($stock > 1) Solo quedan {{ (int) $stock }} unidades
                                    @else Sin existencias por ahora
                                    @endif
                                </div>
                            @endif
                            <div class="ct-eta">
                                <strong class="text-success">{{ $item->quantity }} {{ $item->quantity == 1 ? 'unidad' : 'unidades' }}</strong>
                                @if($shipping && $shipping['desde'])
                                    {{ $item->quantity == 1 ? 'llega' : 'llegan' }} {{ $shipping['desde'] === $shipping['hasta'] ? 'el ' . $shipping['desde'] : 'entre ' . $shipping['desde'] . ' y ' . $shipping['hasta'] }}
                                @endif
                                @if($item->quantity > 1)
                                    <span class="text-muted">· <x-precio :valor="$item->price"/> c/u</span>
                                @endif
                            </div>

                            <div class="ct-actions">
                                <form action="{{ route('cart.update', $item) }}" method="POST">
                                    @csrf
                                    <span class="ct-qty">
                                        <button type="submit" name="qty" value="{{ $item->quantity - 1 }}" aria-label="{{ $item->quantity <= 1 ? 'Eliminar' : 'Quitar uno' }}" dusk="cart-minus-{{ $buyable->id }}">
                                            @if($item->quantity <= 1)
                                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                            @else
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/></svg>
                                            @endif
                                        </button>
                                        <span>{{ $item->quantity }}</span>
                                        <button type="submit" name="qty" value="{{ $item->quantity + 1 }}" aria-label="Agregar uno">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
                                        </button>
                                    </span>
                                </form>

                                <form action="{{ route('cart.remove', $item) }}" method="POST">
                                    @csrf
                                    <button class="ct-link" dusk="cart-delete-{{ $buyable->id }}">Eliminar</button>
                                </form>

                                @if($buyable instanceof \Vanilo\Product\Contracts\Product)
                                    <span class="ct-sep">|</span>
                                    @auth
                                        <form action="{{ route('cart.favorite', $item) }}" method="POST">
                                            @csrf
                                            <button class="ct-link">&#9825; Mover a favoritos</button>
                                        </form>
                                    @else
                                        <a href="{{ route('login') }}">&#9825; Mover a favoritos</a>
                                    @endauth
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="ct-foot">
                    Subtotal ({{ $itemCount }} {{ $itemCount === 1 ? 'producto' : 'productos' }}): <strong><x-precio :valor="$subtotal"/></strong>
                </div>
            </div>
        </div>

        {{-- Columna derecha: resumen --}}
        <div class="col-lg-4">
            <div class="ct-sum">
                @if($missingForFree !== null)
                    <div class="text-center">
                        @if($missingForFree > 0)
                            Agrega <strong><x-precio :valor="$missingForFree"/></strong> más para <strong class="text-success">envío gratis</strong>
                        @else
                            <strong class="text-success">¡Tienes envío gratis!</strong>
                        @endif
                    </div>
                    <div class="ct-bar"><div style="width: {{ min(100, round($itemsTotal / $freeFrom * 100)) }}%"></div></div>
                @endif

                {{-- Cupón --}}
                <details class="ct-coupon" @if(session('cupon_error')) open @endif>
                    <summary>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h18v3a2 2 0 0 0 0 4v3H3v-3a2 2 0 0 0 0-4z"/><path d="M14 7v10" stroke-dasharray="2 2"/></svg>
                        <span class="flex-grow-1">Aplicar cupón</span>
                        <span>&rarr;</span>
                    </summary>
                    <form action="{{ route('cart.coupon.apply') }}" method="POST" class="d-flex gap-2 mt-3">
                        @csrf
                        <input type="text" name="cupon" class="form-control" placeholder="Código del cupón" required maxlength="50">
                        <button class="btn btn-outline-primary">Aplicar</button>
                    </form>
                    @if(session('cupon_error'))
                        <div class="text-danger small mt-2">{{ session('cupon_error') }}</div>
                    @endif
                </details>

                @if($giftCost !== null)
                    <form action="{{ route('cart.gift') }}" method="POST" class="ct-coupon d-flex align-items-center gap-2" style="padding: 10px 14px;">
                        @csrf
                        <input type="hidden" name="regalo" value="0">
                        <input type="checkbox" class="form-check-input m-0" id="ct-regalo" name="regalo" value="1" @checked($gift) onchange="this.form.submit()">
                        <label for="ct-regalo" class="flex-grow-1 m-0" style="cursor:pointer; font-weight:600;">Agregar empaque para regalo</label>
                        <span class="text-muted small">@if($giftCost > 0) <x-precio :valor="$giftCost"/> @else gratis @endif</span>
                    </form>
                @endif

                <div class="ct-row"><span>Subtotal productos</span><x-precio :valor="$subtotal"/></div>
                @if($discount < 0)
                    <div class="ct-row text-success">
                        <span>Descuento
                            @foreach($coupons as $code)
                                <form action="{{ route('cart.coupon.remove', $code) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="badge bg-success-subtle text-success border-0" title="Quitar cupón">{{ $code }} &times;</button>
                                </form>
                            @endforeach
                        </span>
                        <span>-<x-precio :valor="abs($discount)"/></span>
                    </div>
                @elseif(count($coupons))
                    <div class="ct-row small text-muted">
                        <span>Cupón
                            @foreach($coupons as $code)
                                <form action="{{ route('cart.coupon.remove', $code) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="badge bg-secondary-subtle text-secondary border-0" title="Quitar cupón">{{ $code }} &times;</button>
                                </form>
                            @endforeach
                        </span>
                        <span>sin descuento aún</span>
                    </div>
                @endif
                @if($gift)
                    <div class="ct-row"><span>Empaque para regalo</span>@if($giftCost > 0) <x-precio :valor="$giftCost"/> @else <span class="text-success">Gratis</span> @endif</div>
                @endif
                <div class="ct-row text-muted">
                    <span>Envío</span>
                    @if(!$shipping) <span>Se calcula en el checkout</span>
                    @elseif($shipping['costo'] > 0) <x-precio :valor="$shipping['costo']"/>
                    @else <span class="text-success">Gratis</span> @endif
                </div>
                <div class="ct-total"><span>Total</span><x-precio :valor="$subtotal + $discount + ($gift ? $giftCost : 0) + ($shipping['costo'] ?? 0)"/></div>

                <a href="{{ route('checkout.show') }}" class="ct-pay">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    Proceder al pago
                </a>
                <a href="{{ route('product.index') }}" class="ct-more">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    Seguir comprando
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
