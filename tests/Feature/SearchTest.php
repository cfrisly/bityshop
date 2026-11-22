<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Vanilo\Foundation\Models\Product;
use Vanilo\Product\Models\ProductState;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function producto(string $name, array $extra = []): Product
    {
        return Product::create(array_merge([
            'name' => $name,
            'sku' => 'SKU-' . md5($name),
            'state' => ProductState::ACTIVE(),
            'price' => 100,
        ], $extra));
    }

    #[Test]
    public function busqueda_vacia_lleva_al_catalogo()
    {
        $this->get(route('product.search', ['q' => '   ']))->assertRedirect(route('product.index'));
    }

    #[Test]
    public function encuentra_palabras_en_cualquier_orden()
    {
        $this->producto('Laptop Dell Latitude 5300');
        $this->producto('Reloj Samsung');

        $this->get(route('product.search', ['q' => 'dell laptop']))
            ->assertOk()
            ->assertSee('Laptop Dell Latitude 5300')
            ->assertDontSee('Reloj Samsung');
    }

    #[Test]
    public function busca_en_sku_y_descripcion()
    {
        $this->producto('Reloj inteligente', ['sku' => 'R-920', 'description' => 'Modelo SM-R920 con GPS']);

        $this->get(route('product.search', ['q' => 'R-920']))->assertSee('Reloj inteligente');
        $this->get(route('product.search', ['q' => 'gps']))->assertSee('Reloj inteligente');
    }

    #[Test]
    public function no_muestra_productos_inactivos()
    {
        $this->producto('Botas activas');
        $this->producto('Botas ocultas', ['state' => ProductState::INACTIVE()]);

        $this->get(route('product.search', ['q' => 'botas']))
            ->assertSee('Botas activas')
            ->assertDontSee('Botas ocultas');
    }

    #[Test]
    public function el_nombre_pesa_mas_que_la_descripcion()
    {
        $this->producto('Funda protectora', ['description' => 'Compatible con MacBook Air']);
        $this->producto('MacBook Air 13');

        $html = $this->get(route('product.search', ['q' => 'macbook']))->getContent();

        $this->assertLessThan(strpos($html, 'Funda protectora'), strpos($html, 'MacBook Air 13'));
    }

    #[Test]
    public function sin_resultados_sugiere_la_palabra_correcta()
    {
        $this->producto('MacBook Pro');

        $this->get(route('product.search', ['q' => 'macbok']))
            ->assertOk()
            ->assertSee('No encontramos productos')
            ->assertSee('¿Quisiste decir', false)
            ->assertSee('macbook');
    }
}
