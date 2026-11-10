<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_idempotency_key_prevents_duplicate_orders(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 1000.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct(['price' => 300.00, 'stock' => 5]);

        $payload = [
            'shipping_address' => 'Av. Idempotente, 100',
            'card_pin' => '1234',
            'idempotency_key' => 'idem-token-unique-001',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $first = $this->actingAs($customer)->post(route('orders.checkout'), $payload);
        $order = Order::firstOrFail();
        $first->assertRedirect(route('orders.show', $order));

        $second = $this->actingAs($customer)->post(route('orders.checkout'), $payload);
        $second->assertRedirect(route('orders.show', $order));

        $this->assertEquals(1, Order::count());
        $this->assertEquals(700.00, (float) $customer->virtualCard->fresh()->balance);
        $this->assertEquals(4, $product->fresh()->stock);
    }

    public function test_order_status_transitions_and_seven_day_regret_return(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct(['price' => 200.00, 'stock' => 5, 'cashback_percent' => 5.0]);

        $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua Fluxo, 10',
            'card_pin' => '1234',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $order = Order::firstOrFail();

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => OrderStatus::SEPARATING->value,
        ])->assertSessionHas('success');
        $this->assertEquals(OrderStatus::SEPARATING->value, $order->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => OrderStatus::SHIPPED->value,
            'tracking_code' => 'BR998877665',
        ])->assertSessionHas('success');
        $this->assertEquals(OrderStatus::SHIPPED->value, $order->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => OrderStatus::DELIVERED->value,
        ])->assertSessionHas('success');
        $this->assertEquals(OrderStatus::DELIVERED->value, $order->fresh()->status);
        $this->assertEquals(310.00, (float) $customer->virtualCard->fresh()->balance);

        $this->actingAs($customer)
            ->post(route('orders.return', $order))
            ->assertSessionHas('success');

        $this->assertEquals(Order::STATUS_RETURNED, $order->fresh()->status);
        $this->assertEquals(500.00, (float) $customer->virtualCard->fresh()->balance);
        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_identity_honesty_real_histogram_and_portuguese_pagination(): void
    {
        $this->seed(CatalogSeeder::class);

        $home = $this->get(route('catalog.index'));
        $home->assertOk();
        $home->assertDontSee('cardstore', false);
        $home->assertDontSee('SIGCAP', false);
        $home->assertDontSee('Av. Fernandes Lima', false);
        $home->assertDontSee('Catálogo DigitalCard DigitalCard', false);
        $home->assertSee('Em separação');
        $home->assertSee('Enviado');

        $product = Product::firstOrFail();
        $show = $this->get(route('catalog.product', $product->slug));
        $show->assertOk();
        $show->assertSee('Seja o primeiro a avaliar');
        $show->assertSee('Produtos relacionados');
        $show->assertDontSee('Quem viu este produto também comprou');
        $show->assertDontSee('Marcos Cavalcanti');
        $show->assertDontSee('84%');
    }

    public function test_product_review_histogram_calculates_real_distribution_from_database(): void
    {
        $this->seed(CatalogSeeder::class);
        $product = Product::firstOrFail();

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $product->reviews()->create([
            'user_id' => $u1->id,
            'rating' => 5,
            'comment' => 'Excelente acabamento e entrega no prazo.',
            'is_verified_purchase' => true,
        ]);
        $product->reviews()->create([
            'user_id' => $u2->id,
            'rating' => 3,
            'comment' => 'Bom custo-benefício.',
            'is_verified_purchase' => true,
        ]);
        $product->recalculateRating();

        $response = $this->get(route('catalog.product', $product->slug));
        $response->assertOk();
        $response->assertSee('50%');
        $response->assertSee('Excelente acabamento e entrega no prazo.');
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"@type":"Product"', false);
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
