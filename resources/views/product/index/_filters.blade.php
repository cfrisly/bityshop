<form class="card card-default mb-3" action="{{
    $taxon ?
    route('product.category', [$taxon->taxonomy->slug, $taxon])
    :
    route('product.index')
}}">
    <div class="card-header">{{ __('Filters') }}
        <button class="btn btn-sm btn-primary float-right pt-0 pb-0">{{ __('Apply') }}</button>
    </div>
    <ul class="list-group list-group-flush">
        @foreach($properties as $property)
            @include('product.index._property', ['property' => $property, 'filters' => $filters[$property->slug] ?? []])
        @endforeach
    </ul>
</form>
