<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Vanilo\Cart\Facades\Cart;
use Vanilo\Foundation\Models\Product;
use Vanilo\Product\Models\ProductState;

class CartPriceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_carrito_usa_el_precio_vigente_si_cambio_en_el_admin()
    {
        $product = Product::create([
            'name' => 'Botas de trabajo',
            'sku' => 'BOTAS-1',
            'state' => ProductState::ACTIVE(),
            'price' => 2210,
        ]);

        $this->post(route('cart.add', $product));
        $this->assertEquals(2210, Cart::getItems()->first()->price);

        // Se pone en oferta después de agregarlo al carrito
        $product->update(['price' => 1899, 'original_price' => 2210]);

        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Actualizamos el precio de')
            ->assertSee('Q1,899')
            ->assertSee('Q2,210.00')
            ->assertSee('Ahorras Q311.00');

        $this->assertEquals(1899, Cart::fresh()->getItems()->first()->price);
    }
}
