@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>resultados para:
            <strong>{{$q}}</strong>
        </h2>
        <hr>
        <div class="row">
            @forelse($products as $product)
                <div class="col-md-3">
                    <div class="thumbnail">
                        <img src="{{ $product->getThumbnailUrl() }}" alt="">
                        <div class="caption">
                            <h4>
                                {{ $product->name }}
                            </h4>
                            <p>
                                {{ $product->price }}
                            </p>
                            <a href="{{ route('product.show', $product->slug) }}" 
                                class="btn btn-primary">
                                Ver producto
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning">
                    No se encontraron productos.
                </div>
            @endforelse
        </div>
        {{ $products->links() }}
    </div>
@endsection