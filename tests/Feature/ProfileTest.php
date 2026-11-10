<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account_when_balance_is_zero_and_no_open_orders(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNull(User::find($user->id));
    }

    public function test_s7_user_cannot_delete_account_with_positive_balance_or_open_order(): void
    {
        $user = User::factory()->create();
        $user->virtualCard->update(['balance' => 50.00]);

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'balance')
            ->assertRedirect('/profile');

        $this->assertNotNull(User::find($user->id));

        // Zera o saldo, mas cria pedido em aberto
        $user->virtualCard->update(['balance' => 0.00]);
        Order::create([
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'cashback_total' => 2.00,
            'status' => Order::STATUS_REQUESTED,
            'shipping_address' => 'Rua Aberta, 10',
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'orders')
            ->assertRedirect('/profile');

        $this->assertNotNull(User::find($user->id));
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull(User::find($user->id));
    }
}
