@extends('layouts.app')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('product.index') }}">Todos los productos</a></li>
    @if ($product->taxons->count())
        @include('product._breadcrumbs', ['taxon' => $product->taxons->first()])
    @endif
    <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($product->name, 60) }}</li>
@stop

@section('content')
@if ($productType !== 'product')
    {{-- Productos con variantes: vista anterior --}}
    <style>
        .thumbnail-container { overflow-x: scroll; }
        .thumbnail { width: 64px; height: auto; display: block; float: left; }
        .thumbnail img { cursor: pointer; }
    </style>
    <div class="container">
        <h1>{{ $product->name }}</h1>
        <hr>
        @include("product.show._$productType")
    </div>
@else
    @include('product.show._detalle')
@endif
@endsection
