{{--
    Cuadrícula de tarjetas de producto
    Guardar en: resources/views/product/_grid.blade.php
    Usar en la vista de productos con:
        @include('product._grid', ['products' => $products])
    En la seccion de favoritos del panel:
        @include('product._grid', ['products' => $favorites, 'removeOnUnFavorite' => true])
--}}

@php
    // IDs de los productos que el usuario ya tiene en favoritos
    $favoriteIds = auth()->check() ? auth()->user()->favoriteProducts()->pluck('favorites.product_id')->all() : [];
    // En la seccion de favoritos, al quitar un corazon la tarjeta desaparece
    $removeOnUnfavorite = $removeOnUnfavorite ?? false; 

    // Separa el precio en enteros y centavos: 399.00 -> ['399', '00']
    $splitPrice = function ($amount) {
        $formatted = number_format((float) $amount, 2, '.', ',');
        return explode('.', $formatted);
    };

    // Revisa si el producto tiene una propiedad de Vanilo marcada como "sí"
    // (se crean en el admin: Propiedades -> envio-gratis / entrega-rapida)
    $hasFlag = function ($product, $slug) {
        try {
            if (! method_exists($product, 'valueOfProperty')) {
                return false;
            }
            $value = $product->valueOfProperty($slug);
            if (! $value) {
                return false;
            }
            $raw = method_exists($value, 'getCastedValue') ? $value->getCastedValue() : $value->value;
            return filter_var($raw, FILTER_VALIDATE_BOOLEAN)
                || in_array(mb_strtolower((string) $raw), ['si', 'sí', 'yes']);
        } catch (\Throwable $e) {
            return false;
        }
    };
@endphp

<style>
    /* ===== Cuadrícula ===== */
    .pc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 28px;
    }

    /* ===== Tarjeta ===== */
    .pc-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #fff;
    }

    /* Imagen: misma área cuadrada para todos */
    .pc-image {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        aspect-ratio: 1 / 1;
        background: #f4f4f4;
        border-radius: 18px;
        overflow: hidden;
        padding: 18px;
    }
    .pc-image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        mix-blend-mode: multiply;   /* el fondo blanco de la foto se funde con el gris */
        transition: transform .25s;
    }
    .pc-card:hover .pc-image img { transform: scale(1.04); }
    .pc-noimage { font-size: 64px; opacity: .35; }

    /* Etiqueta de descuento */
    .pc-discount {
        position: absolute;
        top: 12px;
        left: 12px;
        background: #b3263e;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 8px;
    }

    /* Corazón de favoritos */
    .pc-fav {
        position: absolute;
        right: 12px;
        bottom: 12px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,.85);
        border: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #292942;
        cursor: pointer;
        transition: .2s;
    }
    .pc-fav:hover { color: #b3263e; transform: scale(1.08); }
    .pc-fav svg { fill: none; transition: .2s; }
    .pc-fav--active { color: #e0245e; }
    .pc-fav--active svg { fill: currentColor; }   /* corazón relleno */

    .pc-fav-form { margin: 0; }

    /* Cuerpo: crece para que el botón quede siempre abajo */
    .pc-body {
        display: flex;
        flex-direction: column;
        flex: 1;
        padding-top: 14px;
    }

    /* Título: máximo 2 líneas, siempre el mismo alto */
    .pc-title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.7em;
        line-height: 1.35;
        font-size: 17px;
        color: #292942;
        text-decoration: none;
        margin-bottom: 10px;
    }
    .pc-title:hover { text-decoration: underline; color: #292942; }

    /* Precio */
    .pc-price-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 4px;
    }
    .pc-price {
        font-size: 30px;
        font-weight: 800;
        color: #292942;
        line-height: 1;
    }
    .pc-price sup {
        font-size: 15px;
        top: -.9em;
        margin-left: 1px;
    }
    .pc-price--sale { color: #b3263e; }
    .pc-price-old {
        font-size: 16px;
        color: #8a8a99;
        text-decoration: line-through;
    }
    .pc-savings {
        font-size: 14px;
        color: #b3263e;
        margin-bottom: 8px;
    }

    /* Parte de abajo: etiquetas + botón, siempre alineada al fondo */
    .pc-bottom {
        margin-top: auto;
        padding-top: 10px;
    }

    .pc-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-height: 30px;          /* reserva espacio aunque no haya etiquetas */
        margin-bottom: 14px;
    }
    .pc-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 14px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 8px;
        line-height: 1.3;
    }
    .pc-badge--fast { background: #d4f5df; color: #1d6b3a; }
    .pc-badge--free { background: #e2e3fb; color: #3b3fb5; }

    /* Botón Comprar */
    .pc-buy {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        color: #292942;
        border: 2px solid #e2e2ea;
        border-radius: 30px;
        padding: 9px 26px;
        font-size: 17px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,.06);
        transition: .2s;
    }
    .pc-buy:hover {
        border-color: #292942;
        background: #292942;
        color: #fff;
    }

    @media (max-width: 576px) {
        .pc-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .pc-title { font-size: 15px; }
        .pc-price { font-size: 24px; }
        .pc-buy { padding: 8px 16px; font-size: 15px; }
    }
</style>

<div class="pc-grid">
    @foreach ($products as $product)

        @php
            $price     = (float) $product->price;
            $original  = (float) ($product->original_price ?? 0);
            $onSale    = $original > $price && $price > 0;
            $discount  = $onSale ? round(($original - $price) / $original * 100) : 0;
            $savings   = $onSale ? $original - $price : 0;
            [$int, $dec] = $splitPrice($price);
            $freeShip  = $hasFlag($product, 'envio-gratis');
            $fast      = $hasFlag($product, 'entrega-rapida');
        @endphp

        <div class="pc-card">

            {{-- Imagen + descuento + corazón --}}
            <div style="position: relative;">
                <a href="{{ route('product.show', $product->slug) }}" class="pc-image">
                    @if ($product->hasImage())
                        <img src="{{ $product->getThumbnailUrl() }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <span class="pc-noimage">🛍️</span>
                    @endif

                    @if ($onSale)
                        <span class="pc-discount">{{ $discount }}% off</span>
                    @endif
                </a>

                {{-- Corazon de favoritos --}}
                @auth
                    @php $isFav = in_array($product->id, $favoriteIds); @endphp
                    <form action="{{ route('favorites.toggle', $product->id) }}" method="POST"
                          class="pc-fav-form" data-remove="{{ $removeOnUnfavorite ? 1 : 0 }}">
                        @csrf
                        <button type="submit" class="pc-fav {{ $isFav ? 'pc-fav--active' : '' }}"
                                title="{{ $isFav ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                            <svg width="22" height="22" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>
                            </svg>
                        </button>
                    </form>
                    @else
                        {{-- Sin sesión: lleva a iniciar sesión --}}
                        <a href="{{ route('login') }}" class="pc-fav" title="Inicia sesión para guardar favoritos">
                            <svg width="22" height="22" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>
                            </svg>
                        </a>
                @endauth

            </div>

            <div class="pc-body">

                {{-- Título --}}
                <a href="{{ route('product.show', $product->slug) }}" class="pc-title" title="{{ $product->name }}">
                    {{ $product->name }}
                </a>

                {{-- Precio --}}
                <div class="pc-price-row">
                    <span class="pc-price {{ $onSale ? 'pc-price--sale' : '' }}">Q{{ $int }}<sup>{{ $dec }}</sup></span>
                    @if ($onSale)
                        <span class="pc-price-old">Q{{ number_format($original, 0) }}</span>
                    @endif
                </div>

                @if ($onSale)
                    <div class="pc-savings">
                        Ahorras <strong>Q{{ number_format($savings, 0) }}</strong> por tiempo limitado
                    </div>
                @endif

                {{-- Etiquetas + botón: siempre alineados abajo --}}
                <div class="pc-bottom">

                    <div class="pc-badges">
                        @if ($fast)
                            <span class="pc-badge pc-badge--fast">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>
                                Entrega rápida
                            </span>
                        @endif
                        @if ($freeShip)
                            <span class="pc-badge pc-badge--free">Envío GRATIS</span>
                        @endif
                    </div>

                    <form action="{{ route('cart.add', $product) }}" method="POST">
                        @csrf
                        <button type="submit" class="pc-buy">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h3l2.4 11.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L21 7H6"/>
                                <circle cx="9.5" cy="20" r="1.3"/>
                                <circle cx="17.5" cy="20" r="1.3"/>
                                <line x1="17" y1="2" x2="17" y2="6"/>
                                <line x1="15" y1="4" x2="19" y2="4"/>
                            </svg>
                            Comprar
                        </button>
                    </form>

                </div>
            </div>
        </div>

    @endforeach
</div>

<script>
    // Marca o desmarca favoritos sin recargar la página
    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.pc-fav-form');
        if (!form) return;

        event.preventDefault();

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        })
        .then(function (response) {
            if (!response.ok) throw new Error();
            return response.json();
        })
        .then(function (data) {
            var button = form.querySelector('.pc-fav');
            button.classList.toggle('pc-fav--active', data.favorite);
            button.title = data.favorite ? 'Quitar de favoritos' : 'Agregar a favoritos';

            // En la sección "Favoritos", la tarjeta desaparece al quitarla
            if (!data.favorite && form.dataset.remove === '1') {
                var card = form.closest('.pc-card');
                if (card) card.remove();
            }
        })
        .catch(function () {
            form.submit();   // si algo falla, se envía de forma normal
        });
    });
</script>
