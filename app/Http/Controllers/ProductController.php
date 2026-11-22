<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use Vanilo\Category\Contracts\Taxon;
use Vanilo\Category\Models\TaxonomyProxy;
use Vanilo\Foundation\Search\ProductSearch;
use Vanilo\Product\Models\Product;
use Vanilo\Product\Models\ProductProxy;
use App\Support\Ajustes;
use App\Support\Buscador;
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
            'products'   => Buscador::precargar($this->productFinder->getResults()),
            'taxonomies' => $taxonomies,
            'taxon'      => $taxon,
            'properties' => $properties,
            'filters'    => $request->filters($properties)
        ]);
    }

    /** Ofertas: productos con precio original mayor al de venta, de mayor a menor descuento */
    public function ofertas()
    {
        $products = ProductProxy::actives()
            ->where('price', '>', 0)
            ->whereColumn('original_price', '>', 'price')
            ->orderByRaw('(original_price - price) / original_price DESC')
            ->get();

        return $this->listado($products, 'Ofertas', 'Por ahora no hay productos en oferta. ¡Vuelve pronto!');
    }

    /** Lo + nuevo: los productos agregados más recientemente */
    public function nuevo()
    {
        $products = ProductProxy::actives()->latest('created_at')->latest('id')->limit(24)->get();

        return $this->listado($products, 'Lo + nuevo', 'Todavía no hay productos nuevos.');
    }

    private function listado($products, string $heading, string $empty)
    {
        return view('product.index', [
            'products' => Buscador::precargar($products),
            'taxonomies' => TaxonomyProxy::get(),
            'taxon' => null,
            'properties' => PropertyProxy::get(),
            'filters' => [],
            'heading' => $heading,
            'emptyMessage' => $empty,
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
            'similar' => Buscador::precargar($this->similarProducts($product)),
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

    /** Sugerencias mientras el cliente escribe en el buscador (JSON) */
    public function suggest(\Illuminate\Http\Request $request)
    {
        $q = Buscador::normalizar($request->input('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['productos' => [], 'categorias' => [], 'sugerencia' => null]);
        }

        $products = Buscador::buscar($q, 6);
        $precio = function ($valor) {
            [$entero, $centavos] = explode('.', number_format((float) $valor, 2, '.', ','));

            return ['entero' => $entero, 'centavos' => $centavos];
        };

        return response()->json([
            'productos' => $products->map(fn ($p) => [
                'nombre' => $p->name,
                'url' => route('product.show', $p->slug),
                'imagen' => $p->getThumbnailUrl() ?: '/images/product.jpg',
                'precio' => $precio($p->price),
                'antes' => (float) $p->original_price > (float) $p->price ? 'Q' . number_format((float) $p->original_price, 2) : null,
            ])->values(),
            'categorias' => Buscador::categorias($q)->map(fn ($t) => [
                'nombre' => $t->name,
                'url' => route('product.category', [$t->taxonomy->slug, $t]),
            ])->values(),
            'sugerencia' => $products->isEmpty() ? Buscador::sugerencia($q) : null,
            'todos' => route('product.search', ['q' => $q]),
        ])->header('Cache-Control', 'private, max-age=60');
    }

    public function search(ProductIndexRequest $request)
    {
        $q = Buscador::normalizar($request->input('q'));

        // Busqueda vacia: se muestra el catalogo normal
        if ($q === '') {
            return redirect()->route('product.index');
        }

        $products = Buscador::buscar($q);
        $suggestion = $products->isEmpty() ? Buscador::sugerencia($q) : null;

        return view('product.index', [
            'products' => $products,
            'taxonomies' => TaxonomyProxy::get(),
            'taxon' => null,
            'properties' => PropertyProxy::get(),
            'filters' => [],
            'heading' => 'Resultados para “' . $q . '” (' . $products->count() . ')',
            'emptyMessage' => 'No encontramos productos para “' . $q . '”. Revisa cómo está escrito o prueba con otra palabra.',
            'suggestion' => $suggestion,
        ]);
    }
}
