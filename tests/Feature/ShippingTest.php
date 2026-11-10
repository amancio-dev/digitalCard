<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_calculator_applies_fee_under_threshold_and_free_shipping_above(): void
    {
        $pricing = app(CartPricing::class);
        [$cheapProduct] = $this->createProduct(['price' => 100.00, 'stock' => 10]);
        [$expensiveProduct] = $this->createProduct(['price' => 350.00, 'stock' => 10]);

        $underThreshold = $pricing->calculate(
            rawCart: [$cheapProduct->id => ['product_id' => $cheapProduct->id, 'quantity' => 1]],
            cep: '57100-000'
        );
        $this->assertGreaterThan(0.0, $underThreshold['shippingCost']);

        $aboveThreshold = $pricing->calculate(
            rawCart: [$expensiveProduct->id => ['product_id' => $expensiveProduct->id, 'quantity' => 1]],
            cep: '57100-000'
        );
        $this->assertEquals(0.0, $aboveThreshold['shippingCost']);
    }

    public function test_guest_can_use_cart_and_merge_on_login(): void
    {
        [$product] = $this->createProduct(['price' => 80.00, 'stock' => 5]);

        $this->post(route('cart.add', $product), ['quantity' => 2])
            ->assertSessionHas('success');

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Product, 1: Category}
     */
    private function createProduct(array $attributes = []): array
    {
        $category = Category::create([
            'name' => 'Departamento',
            'slug' => 'dep-'.uniqid(),
        ]);

        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Item Teste',
            'slug' => 'item-teste-'.uniqid(),
            'description' => 'Descrição item.',
            'price' => 100.00,
            'compare_at_price' => null,
            'cashback_percent' => 5.00,
            'stock' => 10,
            'is_active' => true,
        ], $attributes));

        return [$product, $category];
    }
}
