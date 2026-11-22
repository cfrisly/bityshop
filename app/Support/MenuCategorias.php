<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Vanilo\Category\Models\TaxonomyProxy;
use Vanilo\Category\Models\TaxonProxy;

/**
 * Árbol de categorías para los menús, cargado con 2 consultas en total.
 * Antes cada categoría y subcategoría hacía su propia consulta (más de 90 por página).
 * Solo incluye categorías activas.
 */
class MenuCategorias
{
    /** Taxonomías con sus categorías raíz en ->raices (y cada categoría con ->children) */
    public static function arbol(): Collection
    {
        if (app()->bound('tienda.menu_categorias')) {
            return app('tienda.menu_categorias');
        }

        $taxonomies = TaxonomyProxy::get();
        $taxons = TaxonProxy::query()->where('is_active', true)->orderBy('priority')->orderBy('name')->get();
        $porId = $taxons->keyBy('id');
        $porPadre = $taxons->groupBy('parent_id');

        foreach ($taxons as $taxon) {
            $taxon->setRelation('taxonomy', $taxonomies->firstWhere('id', $taxon->taxonomy_id));
            $taxon->setRelation('parent', $taxon->parent_id ? $porId->get($taxon->parent_id) : null);
            $taxon->setRelation('children', ($porPadre->get($taxon->id) ?? collect())->values());
        }
        foreach ($taxonomies as $taxonomy) {
            $taxonomy->setRelation('raices', $taxons->where('taxonomy_id', $taxonomy->id)->whereNull('parent_id')->values());
        }

        app()->instance('tienda.menu_categorias', $taxonomies);

        return $taxonomies;
    }
}
