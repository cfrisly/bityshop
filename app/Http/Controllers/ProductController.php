<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use Vanilo\Category\Contracts\Taxon;
use Vanilo\Category\Models\TaxonomyProxy;
use Vanilo\Category\Models\TaxonProxy;
use Vanilo\Foundation\Search\ProductSearch;
use Vanilo\Product\Models\Product;
use Vanilo\Product\Models\ProductProxy;
use App\Support\Ajustes;
use App\Support\Carrito;
use App\Support\Envio;
use Vanilo\Properties\Models\PropertyProxy;

class ProductController extends Controller
{
    private ProductSearch $productFinder;

    public function __construct(ProductSearch $productFinder)
    {
        $this->productFinder = $productFinder;
    }

    public function index(ProductIndexRequest $request, ?string $taxonomyName = null, ?Taxon $taxon = null)
    {
        $taxonomies = TaxonomyProxy::get();
        $properties = PropertyProxy::get();

        if ($taxon) {
            $this->productFinder->withinTaxon($taxon);
        }

        foreach ($request->filters($properties) as $property => $values) {
            $this->productFinder->havingPropertyValuesByName($property, $values);
        }

        return view('product.index', [
            'products'   => $this->productFinder->getResults(),
            'taxonomies' => $taxonomies,
            'taxon'      => $taxon,
            'properties' => $properties,
            'filters'    => $request->filters($properties)
        ]);
    }

    public function show(string $slug)
    {
        if (!$product = $this->productFinder->findBySlug($slug)) {
            abort(404);
        }

        $productType = shorten($product::class);
        if ($productType !== 'product') {
            // Productos con variantes: se mantiene la vista anterior
            return view('product.show', ['product' => $product, 'productType' => $productType]);
        }

        $brand = $product->valueOfProperty('marca');
        $address = Carrito::direccionCliente();
        $price = (float) $product->price;
        $shipping = $address
            ? Envio::opciones($address->country_id, $address->province_id, $price)->first()
            : Envio::todosConCobertura($price)->first();

        return view('product.show', [
            'product' => $product,
            'productType' => $productType,
            'images' => $this->images($product),
            'brand' => $brand,
            'similar' => $this->similarProducts($product),
            'shipping' => $shipping,
            'shippingKnown' => $address !== null,
            'location' => $address
                ? collect([$address->city, $address->province?->name ?? $address->country?->name])->filter()->unique()->implode(', ')
                : null,
            'isFavorite' => auth()->check() && auth()->user()->favoriteProducts()->where('products.id', $product->id)->exists(),
            'giftCost' => Ajustes::regaloCosto(),
            'whatsapp' => Ajustes::whatsapp(),
        ]);
    }

    /** Imágenes del producto: miniatura, mediana y original (para la ampliación) */
    private function images($product): array
    {
        $images = $product->getMedia()->map(fn ($media) => [
            'thumb' => $media->getUrl('thumbnail'),
            'medium' => $media->getUrl('medium'),
            'large' => $media->getUrl(),
        ])->values()->all();

        return $images ?: [['thumb' => '/images/product.jpg', 'medium' => '/images/product-medium.jpg', 'large' => '/images/product-medium.jpg']];
    }

    /**
     * Productos similares: primero de la misma subcategoría, luego de la categoría
     * padre y, si faltan, los más vendidos de la tienda.
     */
    private function similarProducts($product, int $limit = 8)
    {
        $result = collect();
        $taxons = $product->taxons;
        $levels = [$taxons->pluck('id')->all(), $taxons->pluck('parent_id')->filter()->unique()->all()];

        foreach ($levels as $taxonIds) {
            if (empty($taxonIds) || $result->count() >= $limit) {
                continue;
            }
            $result = $result->merge(
                ProductProxy::actives()
                    ->whereKeyNot($product->id)
                    ->whereNotIn('id', $result->pluck('id'))
                    ->whereHas('taxons', fn ($q) => $q->whereIn('taxons.id', $taxonIds))
                    ->orderByDesc('units_sold')
                    ->limit($limit - $result->count())
                    ->get()
            );
        }

        if ($result->count() < $limit) {
            $result = $result->merge(
                ProductProxy::actives()
                    ->whereKeyNot($product->id)
                    ->whereNotIn('id', $result->pluck('id'))
                    ->orderByDesc('units_sold')
                    ->latest('id')
                    ->limit($limit - $result->count())
                    ->get()
            );
        }

        return $result;
    }

    public function search(ProductIndexRequest $request){
        $q = trim((string) $request->q);

        // Busqueda vacia: se muestra el catalogo normal
        if ($q === '') {
            return redirect()->route('product.index');
        }

        $allTaxonomies = TaxonomyProxy::get();
        $properties = PropertyProxy::get();

        //Busca por nombre del producto
        $productsByName = (new ProductSearch())
            ->nameContains($q)
            ->getResults();

        // Buscar Taxons
        $taxons = TaxonProxy::query()
            ->where('name', 'LIKE', "%$q%")
            ->get();

        //Busca Taxonomy
        $taxonomies = TaxonomyProxy::query()
            ->where('name', 'LIKE', "%$q%")
            ->get();


        //Reune todos los taxons que se debe buscar
        $taxonsToSearch = collect();
        
        //Taxons encontrados directamente
        foreach ($taxons as $taxon) {
            $taxonsToSearch->push($taxon);
        }

        // Taxons pertenecientes a las taxonomies encontrados
        foreach ($taxonomies as $taxonomy) {
            $taxonomyTaxon = TaxonProxy::query()
                ->where('taxonomy_id', $taxonomy->id)
                ->get();

            $taxonsToSearch = $taxonsToSearch->merge(
                $taxonomyTaxon
            );
        }

        //Buscar productos por categoria
        $productsByCategory = collect();

        if ($taxonsToSearch->isNotEmpty()) {
            $productsByCategory = (new ProductSearch())
                ->withinTaxons(
                    $taxonsToSearch
                        ->unique('id')
                        ->values()
                        ->all()
                )
                ->getResults();
        }
        
        //Unir los resultados 
        $products = $productsByName
            ->merge($productsByCategory)
            ->unique(function ($product) {
                //return $product::class . '-' . $product->id;
                return get_class($product) . '-' . $product->id;
            })
            ->values();

        //Mostrar los resultados
        return view('product.index', [
            'products' => $products,
            // El menu lateral muestra todas las categorias, no solo las que coinciden con la busqueda
            'taxonomies' => $allTaxonomies,
            'taxon' => null,
            'properties' => $properties,
            'filters' => []
        ]);
    }
}
