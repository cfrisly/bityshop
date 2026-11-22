<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Vanilo\Product\Models\Product;
use Vanilo\Product\Models\ProductState;

class ProductShowPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_show_active_product()
    {
        $product = Product::create([
            'name'  => 'Dacia Logan',
            'sku'   => 'DCA-LOGAN',
            'state' => ProductState::ACTIVE(),
            'price' => 11500
        ]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertStatus(200);

        $response->assertSee('Dacia Logan');
        // El precio se muestra separado en entero y centavos (Q11,500<sup>00</sup>)
        $response->assertSee('Q11,500');
        $response->assertSee('Agregar al carrito');
        $response->assertSee('Comprar');
        $response->assertSee(route('cart.add', $product));
    }
}
