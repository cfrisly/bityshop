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
            'taxonomies' => $taxonomies,
            'taxon' => null,
            'properties' => $properties,
            'filters' => []
        ]);
    }
}
