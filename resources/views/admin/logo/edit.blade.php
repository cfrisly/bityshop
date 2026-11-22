@extends('appshell::layouts.private')

@section('title')
    Editar logo
@stop

@section('content')
    <form action="{{ route('tienda.admin.logo.update', $logo) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf
        @method('PUT')
        <x-appshell::card accent="success">
            <x-slot:title>Editar logo</x-slot:title>

            @include('admin.logo._form')

            <x-slot:footer>
                <button class="btn btn-success">Guardar cambios</button>
                <a href="{{ route('tienda.admin.logo.index') }}" class="btn btn-link text-muted">Cancelar</a>
            </x-slot:footer>
        </x-appshell::card>
    </form>
@stop
