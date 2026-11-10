<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'user_type' => 'cliente',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admin_user_can_authenticate_as_administrator_and_redirects_to_recharges_tab(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
            'user_type' => 'administrador',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.recharges.index'));
    }

    public function test_regular_user_cannot_authenticate_as_administrator(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);

        $response = $this->post('/login', [
            'email' => $regularUser->email,
            'password' => 'password',
            'user_type' => 'administrador',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
