@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="text-center p-4 p-md-5" style="background:#fff; border:1px solid #e3e6ea; border-radius:16px;">
                <div style="width:72px; height:72px; border-radius:50%; background:#e3f4f5; color:#0b5f69; display:flex; align-items:center; justify-content:center; margin:0 auto 18px;">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                </div>

                <h1 class="h3 fw-bold mb-3">{{ __('Verifica tu correo electrónico') }}</h1>

                @if (session('resent'))
                    <div class="alert alert-success" role="alert">{{ __('Te enviamos un nuevo enlace de verificación.') }}</div>
                @endif

                <p class="mb-1">{{ __('Te enviamos un enlace de verificación a') }}</p>
                <p class="fw-bold fs-5 mb-3" style="color:#0b5f69;">{{ Auth::user()->email }}</p>
                <p class="text-muted">{{ __('Abre el correo y haz clic en «Verificar mi correo» para activar tu cuenta. Así sabremos que tu correo es real y podrás ver tus pedidos, guardar direcciones y favoritos, y comprar con tu cuenta.') }}</p>
                <p class="text-muted small mb-4">{{ __('¿No te llegó? Revisa la carpeta de spam o promociones.') }}</p>

                <form method="POST" action="{{ route('verification.resend') }}" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-lg px-4 fw-bold" style="background:#ffd43b; color:#292942; border-radius:999px;">
                        {{ __('Reenviar el correo') }}
                    </button>
                </form>

                <p class="small mb-1">{{ __('Mientras tanto puedes seguir viendo la tienda.') }}
                    <a href="{{ route('product.index') }}">{{ __('Seguir comprando como invitado') }}</a>
                </p>
                <p class="small text-muted mb-0">{{ __('¿Correo equivocado?') }}
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Cierra sesión y regístrate de nuevo.') }}</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
