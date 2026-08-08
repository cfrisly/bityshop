<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use Vanilo\Category\Contracts\Taxon;
use Vanilo\Category\Models\TaxonomyProxy;
use Vanilo\Category\Models\TaxonProxy;
use Vanilo\Foundation\Search\ProductSearch;
use Vanilo\Product\Models\Product;
use Vanilo\Properties\Models\PropertyProxy;

class ProductController extends Controller
{
    private ProductSearch $productFinder;

    public function __construct(ProductSearch $productFinder)
    {
        $this->productFinder = $productFinder;
    }

    public function index(ProductIndexRequest $request, string $taxonomyName = null, Taxon $taxon = null)
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

        return view('product.show', [
            'product' => $product,
            'productType' => shorten($product::class),
        ]);
    }

    public function search(ProductIndexRequest $request){
        $q = trim($request->q);

        $taxonomies = TaxonomyProxy::get();
        $properties = PropertyProxy::get();

        //Busca por nombre del producto
        $productsByName = $this->productFinder
            ->nameContains($q)
            ->getResults();

        //Busca si existe una categorias y subcategorias
        $taxons = TaxonProxy::query()
            ->where('name', 'LIKE', "%{$q}%")
            ->get();

        //Obtiene todos los Taxon Hijos
        $allTaxons = collect();
        foreach ($taxons as $taxon){

            //Agregar Categoria encontrada
            $allTaxons->push($taxon);

            //Agregar todos sus hijos
            $this->addChildTaxons($taxon, $allTaxons);
        }

        //Busca productos de las categorias
        $productsByCategory = collect();

        foreach ($taxons as $taxon) {

            $categoryProducts = (new ProductSearch())
                ->withinTaxon($taxon)
                ->getResults();

            $productsByCategory = $productsByCategory->merge(
                $categoryProducts
            );
        }

        //Unir los resultados 
        $products = $productsByName
            ->merge($productsByCategory)
            ->unique(function ($product) {
                return $product::class . '-' . $product->id;
            })
            ->values();

        //Mostrar los resultados
        return view('product.index', [
            'products' => $products,
            'taxonomies' => $taxonomies,
            'taxon' => null,
            'properties' => $properties,
            'filters' => []
        ]);
    }

    private function addChildTaxons($taxon, &$collection){
        foreach($taxon->children as $child) {
            $collection->push($child);
            $this->addChildTaxons($child, $collection);
        }
    }
}
