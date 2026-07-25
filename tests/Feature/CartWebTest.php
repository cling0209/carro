<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartWebTest extends TestCase
{
    use RefreshDatabase;

    protected function createProduct(): Product
    {
        return Product::create([
            'sku' => 'TEST001',
            'name' => 'Producto de prueba',
            'slug' => 'producto-de-prueba',
            'price' => 9990,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    protected function addToCart(Product $product)
    {
        return $this->from(route('cart.index'))->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_authenticated_user_can_add_product_to_cart(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = $this->createProduct();

        $response = $this->actingAs($user)->addToCart($product);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('success');
        $response->assertCookieMissing('cart_session');
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
    }

    public function test_guest_receives_cart_session_cookie(): void
    {
        $product = $this->createProduct();

        $response = $this->addToCart($product);

        $response->assertRedirect(route('cart.index'));
        $response->assertCookie('cart_session');
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }
}
