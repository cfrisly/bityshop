{{--
    Página de producto (productos simples)
    Variables: $product, $images, $brand, $similar, $shipping, $shippingKnown, $location,
               $isFavorite, $giftCost, $whatsapp
--}}
@php
    $price = (float) $product->price;
    $original = (float) ($product->original_price ?? 0);
    $onSale = $original > $price && $price > 0;
    $stock = (float) ($product->stock ?? 0);
    $backorder = (float) ($product->backorder ?? 0) > 0;
    $available = $price > 0 && ($stock > 0 || $backorder);
    $maxQty = $backorder ? 10 : (int) max(1, min(10, $stock));
    $garantia = setting(\App\Support\Ajustes::GARANTIA);
    $politicas = setting(\App\Support\Ajustes::POLITICAS);
    $url = route('product.show', $product->slug);

    // Descripción como lista: cada línea es un punto; lo que va antes de ":" se resalta
    $lineas = collect(preg_split('/\r\n|\r|\n/', (string) $product->description))
        ->map(fn ($l) => trim($l))
        ->filter();
    $punto = function (string $linea) {
        if (preg_match('/^([^:]{2,45}):\s*(.+)$/u', $linea, $m)) {
            return '<strong>' . e($m[1]) . ':</strong> ' . e($m[2]);
        }
        return e($linea);
    };
    $entrega = fn ($s) => $s['desde'] === $s['hasta'] ? 'Llega el ' . $s['desde'] : 'Llega entre ' . $s['desde'] . ' y ' . $s['hasta'];
@endphp

<style>
    .pd { color: #292942; }
    .pd a { text-decoration: none; }
    .pd .precio sup { font-size: .5em; top: -.9em; margin-left: 1px; }

    /* Galería */
    .pd-gallery { display: flex; gap: 12px; }
    .pd-thumbs { display: flex; flex-direction: column; gap: 10px; width: 84px; flex: none; max-height: 520px; overflow-y: auto; }
    .pd-thumb { border: 1px solid #e3e6ea; border-radius: 8px; background: #fff; padding: 4px; cursor: pointer; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; }
    .pd-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .pd-thumb.is-active { border: 2px solid #0d6efd; }
    .pd-main { position: relative; flex: 1; border: 1px solid #eef0f2; border-radius: 12px; background: #fff; aspect-ratio: 1; overflow: hidden; cursor: zoom-in; }
    .pd-main img { width: 100%; height: 100%; object-fit: contain; transition: transform .15s ease-out; transform-origin: center; }
    .pd-main.is-zooming img { transform: scale(2); }
    .pd-lupa { position: absolute; right: 12px; bottom: 12px; width: 44px; height: 44px; border-radius: 50%; background: rgba(41,41,66,.85); color: #fff; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .2s; pointer-events: none; }
    .pd-main:hover .pd-lupa { opacity: 1; }
    .pd-hint { position: absolute; left: 50%; bottom: 12px; transform: translateX(-50%); background: rgba(255,255,255,.92); border-radius: 999px; padding: 4px 12px; font-size: 13px; color: #495057; opacity: 0; transition: opacity .2s; pointer-events: none; white-space: nowrap; }
    .pd-main:hover .pd-hint { opacity: 1; }

    /* Visor ampliado */
    .pd-lb { position: fixed; inset: 0; background: rgba(15,15,25,.92); z-index: 2000; display: none; flex-direction: column; }
    .pd-lb.is-open { display: flex; }
    .pd-lb-top { display: flex; justify-content: space-between; align-items: center; color: #fff; padding: 12px 18px; }
    .pd-lb-stage { flex: 1; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
    .pd-lb-stage img { max-width: 92vw; max-height: 78vh; object-fit: contain; background: #fff; border-radius: 8px; cursor: zoom-in; transition: transform .2s; }
    .pd-lb-stage img.is-zoomed { transform: scale(2); cursor: zoom-out; }
    .pd-lb-btn { position: absolute; top: 50%; transform: translateY(-50%); width: 48px; height: 48px; border-radius: 50%; border: 0; background: rgba(255,255,255,.9); color: #292942; font-size: 26px; line-height: 1; }
    .pd-lb-prev { left: 18px; } .pd-lb-next { right: 18px; }
    .pd-lb-close { border: 0; background: none; color: #fff; font-size: 34px; line-height: 1; }
    .pd-lb-thumbs { display: flex; gap: 8px; justify-content: center; padding: 12px; overflow-x: auto; }
    .pd-lb-thumbs img { width: 60px; height: 60px; object-fit: contain; background: #fff; border-radius: 6px; cursor: pointer; opacity: .55; border: 2px solid transparent; }
    .pd-lb-thumbs img.is-active { opacity: 1; border-color: #ffd43b; }

    /* Información */
    .pd-title { font-size: 26px; font-weight: 700; line-height: 1.25; margin-bottom: 4px; }
    .pd-brand { color: #0d6efd; font-size: 15px; }
    .pd-ok { color: #198754; font-weight: 700; font-size: 20px; }
    .pd-out { color: #b02a37; font-weight: 700; font-size: 20px; }
    .pd-price { color: #b12704; font-size: 34px; font-weight: 700; }
    .pd-old { color: #6c757d; text-decoration: line-through; font-size: 18px; }
    .pd-save { background: #e7f6ec; color: #146c43; border-radius: 6px; padding: 2px 8px; font-size: 14px; font-weight: 600; }
    .pd-ship { border: 1px solid #e3e6ea; border-left: 4px solid #5b5fe8; border-radius: 8px; padding: 12px 14px; background: #fff; }
    .pd-ship-title { color: #5b5fe8; font-weight: 700; letter-spacing: .02em; font-size: 14px; }
    .pd-desc li { margin-bottom: 6px; line-height: 1.5; }
    .pd-icon-btn { border: 1px solid #dee2e6; background: #fff; border-radius: 50%; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; color: #495057; }

    /* Caja de compra */
    .pd-box { border: 1px solid #dfe3e8; border-radius: 14px; padding: 18px; background: #fff; position: sticky; top: 16px; }
    .pd-buy { display: block; width: 100%; height: 48px; border: 0; border-radius: 10px; font-weight: 700; font-size: 17px; }
    /* Mismos colores que el carrito: "Comprar" como "Proceder al pago" y "Agregar" como "Seguir comprando" */
    .pd-buy-cart { background: #fff; color: #292942; border: 1px solid #ced4da; }
    .pd-buy-cart:hover { background: #f1f3f5; }
    .pd-buy-now { background: #ffd43b; color: #292942; margin-top: 10px; }
    .pd-buy-now:hover { background: #fcc419; }
    .pd-buy:disabled { background: #e9ecef; color: #adb5bd; border-color: #e9ecef; }
    .pd-trust { border: 1px solid #dfe3e8; border-radius: 14px; padding: 14px; text-align: center; margin-top: 14px; }
    .pd-warranty { color: #e8590c; font-size: 14px; }

    @media (max-width: 991px) {
        .pd-gallery { flex-direction: column-reverse; }
        .pd-thumbs { flex-direction: row; width: auto; max-height: none; overflow-x: auto; }
        .pd-thumb { width: 64px; flex: none; }
        .pd-box { position: static; }
        .pd-title { font-size: 22px; }
    }
</style>

<div class="container pd py-3">
    <div class="row g-4">
        {{-- ===== Galería ===== --}}
        <div class="col-lg-5">
            <div class="pd-gallery">
                <div class="pd-thumbs" aria-label="Imágenes del producto">
                    @foreach($images as $i => $img)
                        <button type="button" class="pd-thumb {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}" aria-label="Ver imagen {{ $i + 1 }}">
                            <img src="{{ $img['thumb'] }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
                <div class="pd-main" id="pd-main" role="button" tabindex="0" aria-label="Ampliar imagen">
                    <img id="pd-main-img" src="{{ $images[0]['medium'] }}" alt="{{ $product->name }}">
                    <span class="pd-lupa" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/></svg>
                    </span>
                    <span class="pd-hint">Clic para ampliar</span>
                </div>
            </div>
        </div>

        {{-- ===== Información ===== --}}
        <div class="col-lg-4">
            <h1 class="pd-title">{{ $product->name }}</h1>
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    @if($brand)
                        <a class="pd-brand" href="{{ route('product.index', ['marca' => $brand->value]) }}">Visita la tienda de {{ $brand->title }}</a>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a class="pd-icon-btn" target="_blank" rel="noopener" title="Compartir por WhatsApp"
                       href="https://wa.me/?text={{ rawurlencode($product->name . ' ' . $url) }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2s.2-1.1.1-1.2-.2-.2-.5-.3z"/></svg>
                    </a>
                    <button type="button" class="pd-icon-btn" id="pd-share" title="Compartir" data-url="{{ $url }}" data-title="{{ $product->name }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
                    </button>
                </div>
            </div>
            <hr>

            <div class="{{ $available ? 'pd-ok' : 'pd-out' }}">{{ $available ? 'Disponible.' : 'Agotado por ahora.' }}</div>
            <div class="d-flex align-items-baseline gap-2 flex-wrap mt-1">
                <span class="text-muted">Precio:</span>
                <x-precio :valor="$price" class="pd-price"/>
                @if($onSale)
                    <span class="pd-old">Q{{ number_format($original, 2) }}</span>
                    <span class="pd-save">Ahorras Q{{ number_format($original - $price, 2) }}</span>
                @endif
            </div>

            @if($garantia)
                <p class="mt-3 mb-2">
                    <strong>Garantía:</strong> {{ $garantia }}
                    @if($politicas)
                        <a href="#" data-bs-toggle="collapse" data-bs-target="#pd-politicas" title="Ver políticas">&#9432;</a>
                    @endif
                </p>
                @if($politicas)
                    <div class="collapse" id="pd-politicas"><div class="alert alert-light border small mb-2">{{ $politicas }}</div></div>
                @endif
            @endif

            <div class="mt-3 mb-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e8590c" stroke-width="2"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                @auth
                    <a href="{{ route('home', ['section' => 'addresses']) }}" style="color:#e8590c; text-decoration: underline;">{{ $location ?? 'Agrega tu dirección para calcular el envío' }}</a>
                @else
                    <a href="{{ route('login') }}" style="color:#e8590c; text-decoration: underline;">Inicia sesión para calcular el envío a tu dirección</a>
                @endauth
            </div>

            <div class="pd-ship mb-3">
                <div class="pd-ship-title">ENVÍO ESTÁNDAR</div>
                @if($shipping)
                    <div>
                        @if($shippingKnown)
                            {{ $shipping['desde'] ? $entrega($shipping) : '' }}
                            <div class="small text-muted">
                                con {{ $shipping['courier'] ?? $shipping['nombre'] }} ·
                                @if($shipping['costo'] > 0) <x-precio :valor="$shipping['costo']"/> @else <strong class="text-success">Envío GRATIS</strong> @endif
                            </div>
                        @else
                            @if($shipping['costo'] > 0) Desde <x-precio :valor="$shipping['costo']"/> @else <strong class="text-success">Envío GRATIS</strong> disponible @endif
                            <div class="small text-muted">El costo y la fecha exactos dependen de tu departamento.</div>
                        @endif
                    </div>
                @elseif($shippingKnown)
                    <div>Por ahora no tenemos envío a {{ $location }}.</div>
                @else
                    <div class="small text-muted">El costo de envío se calcula en el checkout según tu departamento.</div>
                @endif
            </div>

            @if($lineas->isNotEmpty())
                <ul class="pd-desc ps-3 mt-4">
                    @foreach($lineas as $linea)
                        <li>{!! $punto($linea) !!}</li>
                    @endforeach
                </ul>
            @endif

            @php $props = $product->propertyValues->reject(fn ($v) => $v->property?->slug === 'marca'); @endphp
            @if($props->isNotEmpty())
                <table class="table table-sm mt-3">
                    <tbody>
                    @foreach($props as $pv)
                        <tr><th class="w-50">{{ $pv->property->name }}</th><td>{{ $pv->title }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            <p class="fst-italic small text-muted mt-3">* Precio y unidades sujetos a disponibilidad.</p>
        </div>

        {{-- ===== Caja de compra ===== --}}
        <div class="col-lg-3">
            <div class="pd-box">
                <x-precio :valor="$price" class="pd-price" style="font-size: 26px;"/>
                @if($onSale)
                    <div><span class="pd-old">Q{{ number_format($original, 2) }}</span> <span class="pd-save">-{{ round(($original - $price) / $original * 100) }}%</span></div>
                @endif
                <div class="{{ $available ? 'pd-ok' : 'pd-out' }}" style="font-size: 18px;">{{ $available ? 'Disponible.' : 'Agotado por ahora.' }}</div>

                @if($shipping && $shippingKnown && $shipping['desde'])
                    <div class="pd-ship my-3">
                        <div class="pd-ship-title">ENVÍO ESTÁNDAR</div>
                        <div class="small">{{ $entrega($shipping) }}</div>
                    </div>
                @endif

                @if($garantia)
                    <div class="pd-warranty my-3">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                        <strong>Garantía:</strong> {{ $garantia }}
                    </div>
                @endif

                <form action="{{ route('cart.add', $product) }}" method="POST">
                    @csrf
                    <label for="pd-qty" class="fw-bold mb-1">Cantidad:</label>
                    <select id="pd-qty" name="qty" class="form-select mb-3" @disabled(!$available)>
                        @for($q = 1; $q <= $maxQty; $q++)
                            <option value="{{ $q }}">{{ $q }}</option>
                        @endfor
                    </select>

                    @if($giftCost !== null)
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="regalo" value="1" id="pd-regalo">
                            <label class="form-check-label fw-bold" for="pd-regalo">
                                Agregar empaque para regalo
                                <span class="fw-normal text-muted">({{ $giftCost > 0 ? 'Q' . number_format($giftCost, 2) : 'gratis' }})</span>
                            </label>
                        </div>
                    @endif

                    <button type="submit" name="accion" value="carrito" class="pd-buy pd-buy-cart" @disabled(!$available)>Agregar al carrito</button>
                    <button type="submit" name="accion" value="comprar" class="pd-buy pd-buy-now" @disabled(!$available)>Comprar</button>
                </form>

                <div class="text-center mt-3">
                    @auth
                        <form action="{{ route('favorites.toggle', $product->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm">{{ $isFavorite ? '♥ En tus favoritos' : '♡ Agregar a favoritos' }}</button>
                        </form>
                    @else
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('login') }}">♡ Agregar a favoritos</a>
                    @endauth
                </div>
            </div>

            <div class="pd-trust">
                <div class="fw-bold">100% compra protegida.</div>
                <div class="small" style="color:#e8590c; font-weight:600;">Productos originales | Pagos seguros</div>
            </div>
        </div>
    </div>

    @if($whatsapp)
        <p class="mt-4">
            <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, quiero recibir ofertas y novedades de BityShop') }}" target="_blank" rel="noopener" class="fw-semibold" style="color:#0d47a1; text-decoration: underline;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#25d366"><circle cx="12" cy="12" r="10"/></svg>
                Recibe ofertas y novedades en WhatsApp
            </a>
        </p>
    @endif

    @if($similar->isNotEmpty())
        <h2 class="h4 fw-bold mt-5 mb-3">Productos similares</h2>
        @include('product._grid', ['products' => $similar])
    @endif
</div>

{{-- Visor de imágenes ampliadas --}}
<div class="pd-lb" id="pd-lb" role="dialog" aria-modal="true" aria-label="Imágenes ampliadas">
    <div class="pd-lb-top">
        <span id="pd-lb-count"></span>
        <button type="button" class="pd-lb-close" id="pd-lb-close" aria-label="Cerrar">&times;</button>
    </div>
    <div class="pd-lb-stage">
        <button type="button" class="pd-lb-btn pd-lb-prev" id="pd-lb-prev" aria-label="Anterior">&#8249;</button>
        <img id="pd-lb-img" src="" alt="{{ $product->name }}">
        <button type="button" class="pd-lb-btn pd-lb-next" id="pd-lb-next" aria-label="Siguiente">&#8250;</button>
    </div>
    <div class="pd-lb-thumbs" id="pd-lb-thumbs">
        @foreach($images as $i => $img)
            <img src="{{ $img['thumb'] }}" data-index="{{ $i }}" alt="">
        @endforeach
    </div>
</div>

@push('scripts')
<script>
(function () {
    var images = @json($images);
    var current = 0;
    var main = document.getElementById('pd-main');
    var mainImg = document.getElementById('pd-main-img');
    var thumbs = document.querySelectorAll('.pd-thumb');
    var lb = document.getElementById('pd-lb');
    var lbImg = document.getElementById('pd-lb-img');
    var lbThumbs = document.querySelectorAll('#pd-lb-thumbs img');
    var hasHover = window.matchMedia('(hover: hover)').matches;

    function show(i) {
        current = (i + images.length) % images.length;
        mainImg.src = images[current].medium;
        thumbs.forEach(function (t, k) { t.classList.toggle('is-active', k === current); });
        if (lb.classList.contains('is-open')) { showLb(); }
    }

    // Miniaturas: al pasar el mouse o tocar cambian la imagen principal
    thumbs.forEach(function (t) {
        var i = parseInt(t.dataset.index, 10);
        t.addEventListener('click', function () { show(i); });
        if (hasHover) { t.addEventListener('mouseenter', function () { show(i); }); }
    });

    // Lupa: al mover el mouse se acerca la parte donde está el cursor
    if (hasHover) {
        main.addEventListener('mousemove', function (e) {
            var r = main.getBoundingClientRect();
            mainImg.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
            main.classList.add('is-zooming');
        });
        main.addEventListener('mouseleave', function () { main.classList.remove('is-zooming'); });
    }

    // Visor ampliado
    function showLb() {
        lbImg.classList.remove('is-zoomed');
        lbImg.src = images[current].large;
        document.getElementById('pd-lb-count').textContent = (current + 1) + ' / ' + images.length;
        lbThumbs.forEach(function (t, k) { t.classList.toggle('is-active', k === current); });
    }
    function openLb() { lb.classList.add('is-open'); document.body.style.overflow = 'hidden'; showLb(); }
    function closeLb() { lb.classList.remove('is-open'); document.body.style.overflow = ''; }

    main.addEventListener('click', openLb);
    main.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openLb(); } });
    document.getElementById('pd-lb-close').addEventListener('click', closeLb);
    document.getElementById('pd-lb-prev').addEventListener('click', function () { show(current - 1); });
    document.getElementById('pd-lb-next').addEventListener('click', function () { show(current + 1); });
    lbThumbs.forEach(function (t) { t.addEventListener('click', function () { show(parseInt(t.dataset.index, 10)); }); });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.classList.contains('pd-lb-stage')) { closeLb(); } });
    lbImg.addEventListener('click', function (e) {
        var r = lbImg.getBoundingClientRect();
        lbImg.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
        lbImg.classList.toggle('is-zoomed');
    });
    document.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('is-open')) { return; }
        if (e.key === 'Escape') { closeLb(); }
        if (e.key === 'ArrowLeft') { show(current - 1); }
        if (e.key === 'ArrowRight') { show(current + 1); }
    });
    if (images.length < 2) {
        document.getElementById('pd-lb-prev').style.display = 'none';
        document.getElementById('pd-lb-next').style.display = 'none';
    }

    // Compartir: menú del teléfono, o copiar el enlace en computadora
    document.getElementById('pd-share').addEventListener('click', function () {
        var b = this;
        if (navigator.share) {
            navigator.share({ title: b.dataset.title, url: b.dataset.url }).catch(function () {});
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(b.dataset.url).then(function () { b.title = 'Enlace copiado'; alert('Enlace copiado'); });
        }
    });
})();
</script>
@endpush
