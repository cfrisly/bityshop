<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800&display=swap" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href=" {{ asset('css/footer.css')}} " rel="stylesheet">
    @include('layouts._favicons')
</head>
<body>
    <div id="app">
        @include('layouts._navbar')

        <main class="py-4">

            <div class="container">
                @yield('categories-menu')
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent">
                        @yield('breadcrumbs')
                    </ol>
                </nav>
                @include('flash::message')
            </div>

            @yield('content')
        </main>
    </div>

    <div class="">
        <footer class="footer">
            <div class="container">
                <div class="row">

                    <div class="col-md-3">
                        <h4>Mi cuenta</h4>
                        <ul class="list-unstyled">
                            <li><a href="{{ route('home', ['section' => 'profile']) }}">Mi perfil</a></li>
                            <li><a href="{{ route('home', ['section' => 'orders']) }}">Mis Pedidos</a></li>
                            <li><a href="{{ route('home', ['section' => 'favorites']) }}">Favoritos</a></li>
                        </ul>
                    </div>

                    <div class="col-md-3">
                        <h4>Informacion</h4>
                        <ul class="list-unstyled">
                            <li><a href="#">Sobre Nosotros</a></li>
                            <li><a href="#">Terminos y Condiciones</a></li>
                            <li><a href="#">Politica y Privacidad</a></li>
                            <li><a href="#">Politica y cookies</a></li>
                        </ul>
                    </div>

                    <div class="col-md-3">
                        <h4>Pagos seguros</h4>
                        <p>
                            Visa · Mastercard · PayPal · Stripe
                        </p>
                        <h4>Siguenos</h4>
                        <p>
                            Facebook · Instagram · X
                        </p>
                    </div>

                    <div class="text-center">
                        <p>
                            © {{ date('Y') }} Bity Shop
                            Todos los derechos reservados.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    @stack('alpine')
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
