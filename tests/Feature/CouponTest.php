<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_coupon_chips_render_actual_coupon_codes_and_apply_correctly(): void
    {
        $customer = User::factory()->create();
        [$product] = $this->createProduct(['price' => 600.00, 'stock' => 5]);

        $this->actingAs($customer)->post(route('cart.add', $product), ['quantity' => 1]);

        $cartPage = $this->actingAs($customer)->get(route('cart.index'));
        $cartPage->assertOk();

        // Deve renderizar o código real do model Coupon (DIGITAL10) e jamais value="0"
        $cartPage->assertSee('value="DIGITAL10"', false);
        $cartPage->assertDontSee('name="coupon_code" value="0"', false);

        $this->actingAs($customer)
            ->post(route('cart.coupon.apply'), ['coupon_code' => 'DIGITAL10'])
            ->assertSessionHas('success');

        $this->assertEquals('DIGITAL10', session('cart_coupon'));
    }

    public function test_cashback_is_calculated_on_discounted_paid_amount_and_respects_coupon_limits(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 1000.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct([
            'price' => 200.00,
            'cashback_percent' => 10.00,
            'stock' => 10,
        ]);

        Coupon::updateOrCreate(
            ['code' => 'DESC50'],
            [
                'label' => 'R$ 50 OFF',
                'type' => 'fixed',
                'value' => 50.00,
                'min_total' => 100.00,
                'max_uses' => 1,
                'used_count' => 0,
                'per_user_limit' => 1,
                'is_active' => true,
            ]
        );

        $this->actingAs($customer)->post(route('cart.add', $product), ['quantity' => 1]);
        $this->actingAs($customer)->post(route('cart.coupon.apply'), ['coupon_code' => 'DESC50'])
            ->assertSessionHas('success');

        $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua Desconto, 50',
            'card_pin' => '1234',
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertEquals(150.00, (float) $order->total_amount);
        $this->assertEquals(15.00, (float) $order->cashback_total);

        $this->actingAs($customer)->post(route('cart.add', $product), ['quantity' => 1]);
        $this->actingAs($customer)->post(route('cart.coupon.apply'), ['coupon_code' => 'DESC50'])
            ->assertSessionHas('error');
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
