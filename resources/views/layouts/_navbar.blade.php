{{--
    Barra de navegación de la tienda (celeste).
    El logo se cambia en Admin → Configuración → Logos de temporada.
--}}
@php
    $navLogo = \App\Models\Logo::paraTienda();
    $navTaxonomies = \App\Support\MenuCategorias::arbol();
    $navCategorias = $navTaxonomies->flatMap(fn ($t) => $t->raices)->take(6);
    $navCartCount = Cart::isNotEmpty() ? (int) Cart::itemCount() : 0;
@endphp

<style>
    .bn { --bn: #12808c; --bn-dark: #0b5f69; --bn-light: #e3f4f5; --bn-accent: #ffb020; color: #fff; font-family: Nunito, system-ui, sans-serif; }
    .bn a { color: inherit; text-decoration: none; }
    .bn-top { background: var(--bn); }
    .bn-top .container { display: flex; align-items: center; gap: 16px; padding-top: 12px; padding-bottom: 12px; }
    .bn-logo { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 24px; white-space: nowrap; }
    .bn-logo img { max-height: 48px; max-width: 200px; object-fit: contain; }
    .bn-logo .bn-mark { width: 34px; height: 34px; border-radius: 9px; background: #fff; color: var(--bn); display: flex; align-items: center; justify-content: center; }
    .bn-cat { background: var(--bn-dark); border: 0; color: #fff; border-radius: 999px; padding: 8px 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
    .bn-cat:hover { background: #094f58; }
    .bn-search-wrap { flex: 1; position: relative; min-width: 0; }
    .bn-search { display: flex; background: #fff; border-radius: 999px; overflow: hidden; min-width: 0; }
    .bn-search input::-webkit-search-cancel-button { cursor: pointer; }
    /* Sugerencias mientras se escribe */
    .bn-sug { position: absolute; left: 0; right: 0; top: calc(100% + 6px); background: #fff; color: #292942; border-radius: 14px; box-shadow: 0 14px 36px rgba(0,0,0,.22); z-index: 1050; overflow: hidden; display: none; }
    .bn-sug.is-open { display: block; }
    .bn-sug-title { font-size: 12px; font-weight: 800; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; padding: 10px 16px 4px; }
    .bn-sug a { display: flex; align-items: center; gap: 12px; padding: 8px 16px; color: #292942 !important; }
    .bn-sug a.is-active, .bn-sug a:hover { background: var(--bn-light); }
    .bn-sug img { width: 44px; height: 44px; object-fit: contain; border-radius: 8px; background: #f4f5f7; flex: none; }
    .bn-sug-name { flex: 1; min-width: 0; font-size: 14px; line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .bn-sug-name mark { background: none; color: inherit; font-weight: 800; padding: 0; }
    .bn-sug-price { font-weight: 800; white-space: nowrap; font-size: 15px; }
    .bn-sug-price sup { font-size: .6em; }
    .bn-sug-old { display: block; text-align: right; color: #6c757d; text-decoration: line-through; font-size: 12px; font-weight: 400; }
    .bn-sug-cat { font-size: 14px; }
    .bn-sug-all { border-top: 1px solid #eef0f2; font-weight: 800; color: var(--bn-dark) !important; justify-content: center; padding: 12px 16px !important; }
    .bn-sug-empty { padding: 14px 16px; font-size: 14px; color: #495057; }
    .bn-search input { flex: 1; border: 0; outline: 0; padding: 0 20px; height: 44px; font-size: 16px; color: #292942; min-width: 0; }
    .bn-search button { border: 0; background: var(--bn-accent); color: #292942; width: 56px; display: flex; align-items: center; justify-content: center; }
    .bn-search button:hover { background: #ffc247; }
    .bn-icon { display: flex; flex-direction: column; align-items: center; font-size: 12px; font-weight: 700; gap: 2px; position: relative; white-space: nowrap; background: none; border: 0; color: #fff; padding: 0; }
    .bn-icon:hover { opacity: .85; }
    .bn-badge { position: absolute; top: -7px; right: -6px; background: var(--bn-accent); color: #292942; border-radius: 999px; font-size: 11px; font-weight: 800; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; padding: 0 5px; }
    .bn-bottom { background: var(--bn-dark); }
    .bn-bottom .container { display: flex; align-items: center; gap: 6px; padding-top: 8px; padding-bottom: 8px; overflow-x: auto; scrollbar-width: none; }
    .bn-bottom .container::-webkit-scrollbar { display: none; }
    .bn-link { padding: 6px 14px; border-radius: 999px; white-space: nowrap; font-weight: 600; font-size: 15px; }
    .bn-link:hover { background: rgba(255,255,255,.15); }
    .bn-pill { background: #fff; color: var(--bn-dark) !important; font-weight: 800; }
    .bn-pill:hover { background: var(--bn-light); }
    .bn-pill2 { background: var(--bn-accent); color: #292942 !important; font-weight: 800; }
    .bn-pill2:hover { background: #ffc247; }
    .bn-menu { border: 0; border-radius: 14px; box-shadow: 0 12px 32px rgba(0,0,0,.18); padding: 10px; min-width: 280px; max-height: 70vh; overflow-y: auto; }
    .bn-menu .dropdown-item { border-radius: 8px; padding: 8px 12px; }
    .bn-menu .dropdown-item:hover { background: var(--bn-light); }
    .bn-menu .dropdown-header { color: var(--bn-dark); font-weight: 800; font-size: 14px; }
    @media (max-width: 767px) {
        .bn-top .container { flex-wrap: wrap; gap: 10px; }
        .bn-logo { flex: 1; font-size: 21px; }
        .bn-logo img { max-height: 38px; max-width: 150px; }
        .bn-search-wrap { order: 10; flex-basis: 100%; }
        .bn-search input { height: 40px; font-size: 15px; }
        .bn-hide-sm { display: none !important; }
        .bn-cat { padding: 8px 10px; }
    }
</style>

<header class="bn">
    <div class="bn-top">
        <div class="container">
            <a href="{{ url('/') }}" class="bn-logo" aria-label="{{ config('app.name') }} - inicio">
                @if($navLogo)
                    <img src="{{ $navLogo->url() }}" alt="{{ config('app.name') }}">
                @else
                    <span class="bn-mark">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 7h12l1 13H5z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                    </span>
                    {{ config('app.name', 'BityShop') }}
                @endif
            </a>

            {{-- Categorías --}}
            <div class="dropdown">
                <button class="bn-cat" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Categorías">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="4" y="4" width="6" height="6" rx="1.5"/><rect x="14" y="4" width="6" height="6" rx="1.5"/><rect x="4" y="14" width="6" height="6" rx="1.5"/><rect x="14" y="14" width="6" height="6" rx="1.5"/></svg>
                    <span class="bn-hide-sm">Categorías</span>
                </button>
                <div class="dropdown-menu bn-menu">
                    @foreach($navTaxonomies as $taxonomy)
                        @include('product.index._category_level', ['taxons' => $taxonomy->raices])
                    @endforeach
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item fw-bold" href="{{ route('product.index') }}">Ver todos los productos →</a>
                </div>
            </div>

            {{-- Buscador --}}
            <div class="bn-search-wrap" id="bn-search-wrap">
                <form action="{{ route('product.search') }}" method="GET" class="bn-search" role="search">
                    <input type="search" name="q" id="bn-q" value="{{ request('q') }}" placeholder="¿Qué producto estás buscando?"
                           aria-label="Buscar productos" maxlength="100" enterkeyhint="search" autocomplete="off"
                           role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="bn-sug"
                           data-url="{{ route('product.suggest') }}">
                    <button type="submit" aria-label="Buscar">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </button>
                </form>
                <div class="bn-sug" id="bn-sug" role="listbox" aria-label="Sugerencias"></div>
            </div>

            <a href="{{ auth()->check() ? route('home', ['section' => 'favorites']) : route('login') }}" class="bn-icon bn-hide-sm" title="Favoritos">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20s-7-4.4-9-9a4.8 4.8 0 0 1 9-3 4.8 4.8 0 0 1 9 3c-2 4.6-9 9-9 9z"/></svg>
                Favoritos
            </a>

            <a href="{{ route('cart.show') }}" class="bn-icon" title="Carrito">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.5L21 8H6"/></svg>
                <span class="bn-hide-sm">Carrito</span>
                @if($navCartCount)
                    <span class="bn-badge">{{ $navCartCount }}</span>
                @endif
            </a>

            <div class="dropdown">
                <button class="bn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Mi cuenta">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span class="bn-hide-sm">{{ auth()->check() ? \Illuminate\Support\Str::limit(Auth::user()->name, 12) : 'Mi cuenta' }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end bn-menu" style="min-width: 220px;">
                    @guest
                        <a class="dropdown-item fw-bold" href="{{ route('login') }}">Inicia sesión</a>
                        @if (Route::has('register'))
                            <a class="dropdown-item" href="{{ route('register') }}">Crea tu cuenta</a>
                        @endif
                    @else
                        <a class="dropdown-item" href="{{ route('home', ['section' => 'profile']) }}">Mi perfil</a>
                        <a class="dropdown-item" href="{{ route('home', ['section' => 'orders']) }}">Mis compras</a>
                        <a class="dropdown-item" href="{{ route('home', ['section' => 'favorites']) }}">Favoritos</a>
                        <a class="dropdown-item" href="{{ route('home', ['section' => 'addresses']) }}">Mis direcciones</a>
                        @role('admin')
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ config('konekt.app_shell.ui.url') }}">Administración</a>
                        @endrole
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar sesión</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                    @endguest
                </div>
            </div>
        </div>
    </div>

    <nav class="bn-bottom" aria-label="Secciones">
        <div class="container">
            <a href="{{ route('product.ofertas') }}" class="bn-link bn-pill">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="vertical-align:-2px"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.3"/></svg>
                Ofertas
            </a>
            <a href="{{ route('product.nuevo') }}" class="bn-link bn-pill2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="vertical-align:-2px"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/></svg>
                Lo + nuevo
            </a>
            @foreach($navCategorias as $cat)
                <a href="{{ route('product.category', [$cat->taxonomy->slug, $cat]) }}" class="bn-link">{{ $cat->name }}</a>
            @endforeach
        </div>
    </nav>
</header>

@push('scripts')
<script>
// Sugerencias mientras se escribe en el buscador
(function () {
    var input = document.getElementById('bn-q');
    var box = document.getElementById('bn-sug');
    var wrap = document.getElementById('bn-search-wrap');
    if (!input || !box || !window.fetch) { return; }

    var timer = null, controller = null, items = [], active = -1, cache = {};

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) { e.className = cls; }
        if (text !== undefined) { e.textContent = text; }
        return e;
    }
    // Resalta lo escrito dentro del nombre (sin usar innerHTML con datos)
    function resaltar(nombre, q) {
        var span = el('span', 'bn-sug-name');
        var palabras = q.toLowerCase().split(/\s+/).filter(function (p) { return p.length > 1; });
        var plano = nombre.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        var marcas = new Array(nombre.length).fill(false);
        palabras.forEach(function (p) {
            p = p.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            var i = plano.indexOf(p);
            while (p && i !== -1) { for (var k = i; k < i + p.length; k++) { marcas[k] = true; } i = plano.indexOf(p, i + p.length); }
        });
        var actual = '', enMarca = false;
        function soltar() { if (!actual) { return; } span.appendChild(enMarca ? el('mark', '', actual) : document.createTextNode(actual)); actual = ''; }
        for (var i = 0; i < nombre.length; i++) { if (marcas[i] !== enMarca) { soltar(); enMarca = marcas[i]; } actual += nombre[i]; }
        soltar();
        return span;
    }
    function cerrar() { box.classList.remove('is-open'); input.setAttribute('aria-expanded', 'false'); active = -1; }
    function marcar(i) {
        items.forEach(function (a, k) { a.classList.toggle('is-active', k === i); a.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
        active = i;
        if (items[i]) { items[i].scrollIntoView({ block: 'nearest' }); }
    }
    function pintar(data, q) {
        box.innerHTML = '';
        if (data.productos.length) {
            box.appendChild(el('div', 'bn-sug-title', 'Productos'));
            data.productos.forEach(function (p) {
                var a = el('a'); a.href = p.url; a.setAttribute('role', 'option');
                var img = el('img'); img.src = p.imagen; img.alt = ''; img.loading = 'lazy';
                var precio = el('span', 'bn-sug-price', 'Q' + p.precio.entero);
                precio.appendChild(el('sup', '', p.precio.centavos));
                if (p.antes) { precio.appendChild(el('span', 'bn-sug-old', p.antes)); }
                a.appendChild(img); a.appendChild(resaltar(p.nombre, q)); a.appendChild(precio);
                box.appendChild(a);
            });
        }
        if (data.categorias.length) {
            box.appendChild(el('div', 'bn-sug-title', 'Categorías'));
            data.categorias.forEach(function (c) {
                var a = el('a', 'bn-sug-cat'); a.href = c.url; a.setAttribute('role', 'option');
                a.appendChild(el('span', '', '▸ ')); a.appendChild(resaltar(c.nombre, q));
                box.appendChild(a);
            });
        }
        if (!data.productos.length && !data.categorias.length) {
            var vacio = el('div', 'bn-sug-empty', 'No encontramos productos para “' + q + '”.');
            if (data.sugerencia) {
                vacio.appendChild(document.createElement('br'));
                vacio.appendChild(document.createTextNode('¿Quisiste decir '));
                var s = el('a', '', data.sugerencia);
                s.href = data.todos.replace(/q=[^&]*/, 'q=' + encodeURIComponent(data.sugerencia));
                s.style.cssText = 'display:inline;padding:0;font-weight:800;color:#0b5f69';
                vacio.appendChild(s); vacio.appendChild(document.createTextNode('?'));
            }
            box.appendChild(vacio);
        } else {
            var todos = el('a', 'bn-sug-all', 'Ver todos los resultados para “' + q + '” →');
            todos.href = data.todos; todos.setAttribute('role', 'option');
            box.appendChild(todos);
        }
        items = Array.prototype.slice.call(box.querySelectorAll('a[role="option"]'));
        active = -1;
        box.classList.add('is-open');
        input.setAttribute('aria-expanded', 'true');
    }
    function buscar() {
        var q = input.value.trim();
        if (q.length < 2) { cerrar(); return; }
        if (cache[q]) { pintar(cache[q], q); return; }
        if (controller) { controller.abort(); }
        controller = new AbortController();
        fetch(input.dataset.url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, signal: controller.signal })
            .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
            .then(function (data) { cache[q] = data; if (input.value.trim() === q) { pintar(data, q); } })
            .catch(function () { /* sin conexión o búsqueda cancelada: no se muestra nada */ });
    }

    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(buscar, 220); });
    input.addEventListener('focus', function () { if (input.value.trim().length >= 2) { buscar(); } });
    input.addEventListener('keydown', function (e) {
        if (!box.classList.contains('is-open') || !items.length) { return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); marcar((active + 1) % items.length); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(active <= 0 ? items.length - 1 : active - 1); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); window.location = items[active].href; }
        else if (e.key === 'Escape') { cerrar(); }
    });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) { cerrar(); } });
})();
</script>
@endpush
