@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Mis pedidos</h1>

    @if($pedidos->count())
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pedidos as $pedido)
                    <tr>
                        <td>{{ $pedido->id }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $pedidos->links }}
        @else
            <div class="alert alert-info">
                No tienes pedidos registrados
            </div>
        @endif
</div>
@endsection