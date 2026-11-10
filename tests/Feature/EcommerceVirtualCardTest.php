<?php

namespace Tests\Feature;

use App\Http\Controllers\VirtualCardController;
use App\Models\CardTransaction;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Recharge;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcommerceVirtualCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_automatically_generates_complete_virtual_card(): void
    {
        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);

        $card = $user->virtualCard;

        $this->assertNotNull($card);
        $this->assertEquals(0.00, (float) $card->balance);
        $this->assertEquals(0.00, (float) $card->cashback_earned);
        $this->assertTrue($card->is_active);
        $this->assertNull($card->security_pin);
        // Prefixo claramente fictício 9900
        $this->assertMatchesRegularExpression('/^9900 \d{4} \d{4} \d{4}$/', $card->card_number);
        $this->assertMatchesRegularExpression('/^\d{3}$/', $card->cvv);
        $this->assertEquals('12/30', $card->expiration_date);

        // Verifica que no banco SQLite os valores brutos estão criptografados
        $rawRow = DB::table('virtual_cards')->where('id', $card->id)->first();
        $this->assertNotEquals($card->card_number, $rawRow->card_number);
        $this->assertNotEquals($card->cvv, $rawRow->cvv);
    }

    public function test_customer_can_request_recharge_with_private_receipt_and_duplicate_is_blocked(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create();
        $contents = '%PDF-1.4 comprovante-pix-unico';

        $response = $this->actingAs($user)->post(route('card.recharge'), [
            'amount' => 250.00,
            'receipt' => UploadedFile::fake()->createWithContent('comprovante.pdf', $contents),
        ]);

        $response->assertSessionHas('success');

        $recharge = Recharge::where('virtual_card_id', $user->virtualCard->id)->firstOrFail();

        $this->assertEquals(250.00, (float) $recharge->amount);
        $this->assertEquals('pendente', $recharge->status);
        $this->assertEquals(hash('sha256', $contents), $recharge->receipt_hash);

        Storage::disk('local')->assertExists($recharge->receipt_path);
        Storage::disk('public')->assertMissing($recharge->receipt_path);

        $duplicate = $this->actingAs($user)->post(route('card.recharge'), [
            'amount' => 250.00,
            'receipt' => UploadedFile::fake()->createWithContent('copia.pdf', $contents),
        ]);

        $duplicate->assertSessionHasErrors('receipt');
        $this->assertDatabaseCount('recharges', 1);
    }

    public function test_s1_receipt_route_enforces_authentication_and_authorization(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($owner)->post(route('card.recharge'), [
            'amount' => 180.00,
            'receipt' => UploadedFile::fake()->createWithContent('recibo.pdf', '%PDF-1.4 recibo-privado'),
        ]);

        $recharge = Recharge::firstOrFail();

        auth()->logout();
        $this->get(route('recharges.receipt', $recharge))
            ->assertRedirect(route('login'));

        $this->actingAs($otherCustomer)
            ->get(route('recharges.receipt', $recharge))
            ->assertForbidden();

        $ownerResponse = $this->actingAs($owner)
            ->get(route('recharges.receipt', $recharge));

        $ownerResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $ownerResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('inline; filename="comprovante-recarga-', (string) $ownerResponse->headers->get('Content-Disposition'));

        $this->actingAs($admin)
            ->get(route('recharges.receipt', $recharge))
            ->assertOk();
    }

    public function test_admin_can_approve_recharge_crediting_approved_amount_and_recording_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $recharge = $customer->virtualCard->recharges()->create([
            'amount' => 500.00,
            'receipt_path' => 'receipts/test.pdf',
            'receipt_hash' => hash('sha256', 'test-recharge-approve'),
            'status' => Recharge::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.recharges.approve', $recharge), [
            'approved_amount' => 150.00,
        ]);

        $response->assertSessionHas('success');

        $customer->virtualCard->refresh();
        $this->assertEquals(150.00, (float) $customer->virtualCard->balance);

        $recharge->refresh();
        $this->assertEquals(Recharge::STATUS_APPROVED, $recharge->status);
        $this->assertEquals(150.00, (float) $recharge->approved_amount);
        $this->assertEquals($admin->id, $recharge->reviewed_by);
        $this->assertNotNull($recharge->reviewed_at);

        $this->assertDatabaseHas('card_transactions', [
            'virtual_card_id' => $customer->virtualCard->id,
            'type' => CardTransaction::TYPE_RECHARGE,
            'amount' => 150.00,
            'balance_after' => 150.00,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'recharge.approved',
            'auditable_type' => Recharge::class,
            'auditable_id' => $recharge->id,
        ]);
    }

    public function test_s3_admin_cannot_approve_or_reject_own_recharge(): void
    {
        $admin = User::factory()->admin()->create();

        $ownRecharge = $admin->virtualCard->recharges()->create([
            'amount' => 1000.00,
            'receipt_path' => 'receipts/self.pdf',
            'receipt_hash' => hash('sha256', 'self-recharge'),
            'status' => Recharge::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.recharges.approve', $ownRecharge), ['approved_amount' => 1000.00])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.recharges.reject', $ownRecharge), ['admin_notes' => 'Tentativa própria'])
            ->assertForbidden();

        $this->assertEquals(Recharge::STATUS_PENDING, $ownRecharge->fresh()->status);
        $this->assertEquals(0.00, (float) $admin->virtualCard->fresh()->balance);
    }

    public function test_admin_must_provide_reason_to_reject_recharge(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $recharge = $customer->virtualCard->recharges()->create([
            'amount' => 100.00,
            'receipt_path' => 'receipts/reject.pdf',
            'receipt_hash' => hash('sha256', 'test-recharge-reject'),
            'status' => Recharge::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.recharges.reject', $recharge), ['admin_notes' => ''])
            ->assertSessionHasErrors('admin_notes');

        $this->assertEquals(Recharge::STATUS_PENDING, $recharge->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.recharges.reject', $recharge), ['admin_notes' => 'Comprovante ilegível.'])
            ->assertSessionHas('success');

        $recharge->refresh();
        $this->assertEquals(Recharge::STATUS_REJECTED, $recharge->status);
        $this->assertEquals('Comprovante ilegível.', $recharge->admin_notes);
        $this->assertEquals($admin->id, $recharge->reviewed_by);
    }

    public function test_customer_cannot_checkout_with_insufficient_balance(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update(['security_pin' => Hash::make('1234')]);
        [$product] = $this->createProduct(['price' => 200.00]);

        $response = $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua das Flores, 123',
            'card_pin' => '1234',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertSessionHasErrors('balance');
        $this->assertEquals(0, Order::count());
    }

    public function test_customer_can_checkout_when_balance_is_sufficient_and_pin_is_valid(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct([
            'price' => 300.00,
            'stock' => 5,
            'cashback_percent' => 5.00,
        ]);

        $response = $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Av. Central, 1000 - Apto 402',
            'card_pin' => '1234',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertEquals(Order::STATUS_REQUESTED, $order->status);
        $this->assertEquals(300.00, (float) $order->total_amount);
        $this->assertEquals(15.00, (float) $order->cashback_total);
        $this->assertCount(1, $order->items);
        $this->assertEquals(Order::STATUS_REQUESTED, $order->items->first()->item_status);

        $customer->virtualCard->refresh();
        $this->assertEquals(200.00, (float) $customer->virtualCard->balance);

        $product->refresh();
        $this->assertEquals(4, $product->stock);

        $this->assertDatabaseHas('card_transactions', [
            'virtual_card_id' => $customer->virtualCard->id,
            'type' => CardTransaction::TYPE_PURCHASE,
            'amount' => -300.00,
            'balance_after' => 200.00,
        ]);
    }

    public function test_s5_checkout_requires_pin_to_be_configured_before_first_purchase(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => null,
        ]);

        [$product] = $this->createProduct(['price' => 100.00]);

        $payload = [
            'shipping_address' => 'Rua do PIN, 10',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $this->actingAs($customer)
            ->post(route('orders.checkout'), $payload)
            ->assertSessionHasErrors('card_pin');

        $this->assertEquals(0, Order::count());

        $customer->virtualCard->update(['security_pin' => Hash::make('1234')]);

        $this->actingAs($customer)
            ->post(route('orders.checkout'), $payload + ['card_pin' => '9999'])
            ->assertSessionHasErrors('card_pin');

        $this->actingAs($customer)
            ->post(route('orders.checkout'), $payload + ['card_pin' => '1234'])
            ->assertSessionHasNoErrors();

        $this->assertEquals(1, Order::count());
    }

    public function test_s5_pin_attempts_are_rate_limited_after_five_failures(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct(['price' => 100.00]);

        $payload = [
            'shipping_address' => 'Rua RateLimit, 55',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        RateLimiter::clear(VirtualCardController::pinThrottleKey($customer->id));

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($customer)
                ->post(route('orders.checkout'), $payload + ['card_pin' => '0000'])
                ->assertSessionHasErrors('card_pin');
        }

        $blockedCheckout = $this->actingAs($customer)
            ->post(route('orders.checkout'), $payload + ['card_pin' => '1234']);

        $blockedCheckout->assertSessionHasErrors('card_pin');
        $this->assertStringContainsString(
            'Muitas tentativas incorretas de PIN',
            session('errors')->first('card_pin')
        );
        $this->assertEquals(0, Order::count());

        $blockedPinUpdate = $this->actingAs($customer)
            ->patch(route('card.update-pin'), [
                'current_pin' => '1234',
                'pin' => '5678',
                'pin_confirmation' => '5678',
            ]);

        $blockedPinUpdate->assertSessionHasErrors('current_pin');
    }

    public function test_blocked_card_cannot_be_used_for_checkout(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
            'is_active' => false,
        ]);

        [$product] = $this->createProduct(['price' => 100.00]);

        $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua Bloqueada, 1',
            'card_pin' => '1234',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('card');

        $this->assertEquals(0, Order::count());
    }

    public function test_customer_can_cancel_requested_order_and_receive_refund(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct([
            'price' => 300.00,
            'stock' => 5,
        ]);

        $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua Cancelamento, 20',
            'card_pin' => '1234',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::firstOrFail();

        $this->actingAs($customer)
            ->patch(route('orders.cancel', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);

        $customer->virtualCard->refresh();
        $this->assertEquals(500.00, (float) $customer->virtualCard->balance);

        $product->refresh();
        $this->assertEquals(5, $product->stock);

        $this->assertDatabaseHas('card_transactions', [
            'virtual_card_id' => $customer->virtualCard->id,
            'type' => CardTransaction::TYPE_REFUND,
            'amount' => 300.00,
            'balance_after' => 500.00,
        ]);
    }

    public function test_s2_cancelled_order_cannot_be_marked_as_delivered_or_credit_cashback(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct([
            'price' => 300.00,
            'stock' => 5,
            'cashback_percent' => 10.00,
        ]);

        $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua Cancelada, 99',
            'card_pin' => '1234',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::firstOrFail();

        $this->actingAs($customer)->patch(route('orders.cancel', $order));
        $this->assertEquals(Order::STATUS_CANCELLED, $order->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.orders.deliver', $order), ['tracking_code' => 'BR000000000'])
            ->assertSessionHasErrors('order');

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
        $this->assertNull($order->delivered_at);

        $customer->virtualCard->refresh();
        $this->assertEquals(500.00, (float) $customer->virtualCard->balance);
        $this->assertEquals(0.00, (float) $customer->virtualCard->cashback_earned);
        $this->assertDatabaseMissing('card_transactions', [
            'virtual_card_id' => $customer->virtualCard->id,
            'type' => CardTransaction::TYPE_CASHBACK,
        ]);
    }

    public function test_admin_can_mark_order_as_delivered_and_cashback_is_credited_once(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct([
            'price' => 300.00,
            'stock' => 5,
            'cashback_percent' => 5.00,
        ]);

        $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua Entrega, 30',
            'card_pin' => '1234',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.deliver', $order), ['tracking_code' => 'BR123456789'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_DELIVERED, $order->status);
        $this->assertEquals(Order::STATUS_DELIVERED, $order->items->first()->item_status);
        $this->assertNotNull($order->delivered_at);
        $this->assertEquals('BR123456789', $order->tracking_code);

        $customer->virtualCard->refresh();
        $this->assertEquals(215.00, (float) $customer->virtualCard->balance);
        $this->assertEquals(15.00, (float) $customer->virtualCard->cashback_earned);

        $this->assertDatabaseHas('card_transactions', [
            'virtual_card_id' => $customer->virtualCard->id,
            'type' => CardTransaction::TYPE_CASHBACK,
            'amount' => 15.00,
            'balance_after' => 215.00,
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.deliver', $order));

        $customer->virtualCard->refresh();
        $this->assertEquals(215.00, (float) $customer->virtualCard->balance);
        $this->assertDatabaseCount('card_transactions', 2);
    }

    public function test_customer_can_toggle_card_block_and_update_pin(): void
    {
        $customer = User::factory()->create();
        $card = $customer->virtualCard;

        $this->actingAs($customer)->patch(route('card.toggle-block'))->assertSessionHas('success');
        $this->assertFalse($card->fresh()->is_active);

        $this->actingAs($customer)->patch(route('card.toggle-block'))->assertSessionHas('success');
        $this->assertTrue($card->fresh()->is_active);

        $this->actingAs($customer)->patch(route('card.update-pin'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('1234', $card->fresh()->security_pin));

        $this->actingAs($customer)->patch(route('card.update-pin'), [
            'current_pin' => '0000',
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ])->assertSessionHasErrors('current_pin');

        $this->actingAs($customer)->patch(route('card.update-pin'), [
            'current_pin' => '1234',
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('5678', $card->fresh()->security_pin));
    }

    public function test_cart_can_add_update_and_remove_items_from_session(): void
    {
        $customer = User::factory()->create();
        [$product] = $this->createProduct(['price' => 50.00, 'stock' => 10]);

        $this->actingAs($customer)
            ->post(route('cart.add', $product), ['quantity' => 2])
            ->assertSessionHas('success');

        $this->assertEquals(2, session('cart')[$product->id]['quantity']);

        $this->actingAs($customer)
            ->patch(route('cart.update', $product), ['quantity' => 3])
            ->assertSessionHas('success');

        $this->assertEquals(3, session('cart')[$product->id]['quantity']);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee($product->name);

        $this->actingAs($customer)
            ->delete(route('cart.remove', $product))
            ->assertSessionHas('success');

        $this->assertArrayNotHasKey($product->id, session('cart', []));
    }

    public function test_checkout_uses_session_cart_and_clears_it(): void
    {
        $customer = User::factory()->create();
        $customer->virtualCard->update([
            'balance' => 500.00,
            'security_pin' => Hash::make('1234'),
        ]);

        [$product] = $this->createProduct(['price' => 120.00, 'stock' => 5]);

        $this->actingAs($customer)->post(route('cart.add', $product), ['quantity' => 2]);

        $this->actingAs($customer)->post(route('orders.checkout'), [
            'shipping_address' => 'Rua do Carrinho, 40',
            'card_pin' => '1234',
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertEquals(240.00, (float) $order->total_amount);
        $this->assertCount(1, $order->items);
        $this->assertEquals(2, $order->items->first()->quantity);
        $this->assertNull(session('cart'));
    }

    public function test_s4_database_seeder_does_not_create_demo_accounts_in_production(): void
    {
        $originalEnv = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            // Invoca DatabaseSeeder diretamente (pois Artisan db:seed usa ConfirmableTrait e aborta em 'production' sem --force)
            app(DatabaseSeeder::class)->run();
        } finally {
            app()->detectEnvironment(fn () => $originalEnv);
        }

        $this->assertDatabaseMissing('users', ['email' => 'admin@digitalcard.com']);
        $this->assertDatabaseMissing('users', ['email' => 'cliente@digitalcard.com']);
        $this->assertGreaterThan(0, Product::count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Product, 1: Category}
     */
    private function createProduct(array $attributes = []): array
    {
        $category = Category::create([
            'name' => 'Eletrônicos',
            'slug' => 'eletronicos-'.uniqid(),
        ]);

        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Produto Teste',
            'slug' => 'produto-teste-'.uniqid(),
            'description' => 'Descrição do produto de teste.',
            'price' => 200.00,
            'compare_at_price' => 250.00,
            'cashback_percent' => 2.00,
            'stock' => 10,
            'is_active' => true,
        ], $attributes));

        return [$product, $category];
    }
}
