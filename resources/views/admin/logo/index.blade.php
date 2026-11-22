@extends('appshell::layouts.private')

@section('title')
    Logos de temporada
@stop

@push('page-actions')
    @can('create logos')
        <a href="{{ route('tienda.admin.logo.create') }}" class="btn btn-sm btn-outline-success">+ Nuevo logo</a>
    @endcan
@endpush

@section('content')
    <x-appshell::card accent="secondary">
        <x-slot:title>Logo que se ve hoy en la tienda</x-slot:title>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="background:#12808c; border-radius:10px; padding:10px 16px; min-width:180px; min-height:56px; display:flex; align-items:center;">
                @if($actual)
                    <img src="{{ $actual->url() }}" alt="{{ $actual->nombre }}" style="max-height:44px; max-width:220px;">
                @else
                    <span style="color:#fff; font-weight:700; font-size:20px;">{{ config('app.name') }}</span>
                @endif
            </div>
            <div class="text-muted small">
                @if($actual)
                    <strong>{{ $actual->nombre }}</strong> · {{ $actual->periodo() }}
                @else
                    No hay logo activo: la tienda muestra el nombre "{{ config('app.name') }}". Sube un logo principal.
                @endif
                <br>Los logos de temporada se muestran solos entre sus fechas; el resto del año se usa el principal.
            </div>
        </div>
    </x-appshell::card>

    <x-appshell::card accent="secondary">
        <x-slot:title>Todos los logos</x-slot:title>
        @if($logos->isEmpty())
            <p class="text-muted mb-0">Todavía no hay logos. Crea primero el logo principal y luego los de temporada (Navidad, Halloween, Semana Santa...).</p>
        @else
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Logo</th><th>Nombre</th><th>Cuándo se muestra</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($logos as $logo)
                    <tr>
                        <td><div style="background:#12808c; border-radius:6px; padding:6px 10px; display:inline-block;"><img src="{{ $logo->url() }}" alt="" style="max-height:32px; max-width:140px;"></div></td>
                        <td>
                            <strong>{{ $logo->nombre }}</strong>
                            @if($logo->es_principal) <span class="badge bg-primary">Principal</span> @endif
                            @if($actual && $actual->id === $logo->id) <span class="badge bg-success">Se ve hoy</span> @endif
                        </td>
                        <td>{{ $logo->periodo() }}</td>
                        <td>{!! $logo->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' !!}</td>
                        <td class="text-end text-nowrap">
                            @can('edit logos')
                                <a href="{{ route('tienda.admin.logo.edit', $logo) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                            @endcan
                            @can('delete logos')
                                <form action="{{ route('tienda.admin.logo.destroy', $logo) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el logo {{ $logo->nombre }}? No se puede deshacer.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </x-appshell::card>
@stop
