@extends('appshell::layouts.private')

@section('title')
    Nuevo logo
@stop

@section('content')
    <form action="{{ route('tienda.admin.logo.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf
        
        <x-appshell::card accent="success">
            <x-slot:title>Nuevo logo</x-slot:title>

            @include('admin.logo._form')

            <x-slot:footer>
                <button class="btn btn-success">Guardar logo</button>
                <a href="{{ route('tienda.admin.logo.index') }}" class="btn btn-link text-muted">Cancelar</a>
            </x-slot:footer>
        </x-appshell::card>
    </form>
@stop
