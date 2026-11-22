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
    .bn-search { flex: 1; display: flex; background: #fff; border-radius: 999px; overflow: hidden; min-width: 0; }
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
        .bn-search { order: 10; flex-basis: 100%; }
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
            <form action="{{ route('product.search') }}" method="GET" class="bn-search" role="search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="¿Qué producto estás buscando?" aria-label="Buscar productos" maxlength="100" enterkeyhint="search">
                <button type="submit" aria-label="Buscar">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </button>
            </form>

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
