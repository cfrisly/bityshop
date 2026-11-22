<div class="row" x-data="masterProduct">
    <div class="col-md-6">
        <div class="mb-2">
            <img :src="selectedVariant.imageUrl" id="product-image" />
        </div>

        <div class="thumbnail-container">
            <template x-for="image in selectedVariant.images">
                <div class="thumbnail mr-1">
                    <img class="mw-100" :src="image.thumbnail"
                         @click="document.getElementById('product-image').setAttribute('src', image.medium)"
                    />
                </div>
            </template>
        </div>
    </div>

    <div class="col-md-6">
        <div>
            <h4>Variants</h4>
            <div>
            @foreach($product->variants as $variant)
                <button class="btn" :class="selectedId === {{$variant->id}} ? 'btn-primary' : 'btn-outline-dark'"
                        @click="selectedId = {{$variant->id}}">{{ $variant->name }}</button>
            @endforeach
            </div>

            <form :action="'{{ route('cart.add-variant', '0000') }}'.replace('0000', selectedId)" method="post" class="my-4" :disabled="null === selectedId">
                {{ csrf_field() }}

                <span class="mr-2 font-weight-bold text-primary btn-lg" x-text="selectedVariant.priceFmt">{{ format_price($product->price) }}</span>
                <button type="submit" class="btn btn-success btn-lg" :disabled="null === selectedId">Add to cart</button>
            </form>

            <hr>
            <p class="text-secondary" x-html="selectedVariant.description"></p>
            <hr>
        </div>
    </div>
</div>

@push('alpine')
    <script>
      document.addEventListener("alpine:init", () => {
        Alpine.data('masterProduct', () => ({
          selectedId: @js($product->variants->first()?->id),
          variants: {
            @foreach($product->variants as $variant)
              "{{$variant->id}}": {
                name: @js($variant->name),
                price: @js((float) $variant->price),
                originalPrice: @js($variant->original_price !== null ? (float) $variant->original_price : null),
                priceFmt: @js(format_price($variant->price)),
                originalPriceFmt: @js($variant->original_price !== null ? format_price($variant->original_price) : null),
                description: @js(nl2br(e($variant->description ?? $product->description))),
                imageUrl: @js($variant->getImageUrl('medium') ?: ($product->getImageUrl('medium') ?: '/images/product-medium.jpg')),
                images: [
                  @forelse($variant->getMedia() as $media)
                    { medium: @js($media->getUrl('medium')), thumbnail: @js($media->getUrl('thumbnail')) },
                  @empty
                    { medium: @js($product->getImageUrl('medium') ?: '/images/product-medium.jpg'), thumbnail: @js($product->getImageUrl('thumbnail') ?: '/images/product.jpg') },
                  @endforelse
                ]
              },
            @endforeach
          },
          get selectedVariant() {
            return this.selectedId ? this.variants[this.selectedId] : {priceFmt: @js(format_price($product->price))};
          },
        }))
      });
    </script>
@endpush

