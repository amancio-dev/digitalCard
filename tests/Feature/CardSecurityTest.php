<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payment\SimulatedPixGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CardSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_page_renders_real_pix_flow_and_status_polling_endpoint_works(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('card.pix.generate'), ['amount' => 250.00])
            ->assertSessionHas('success');

        $charge = $owner->virtualCard->recharges()->where('payment_method', 'pix_dynamic')->firstOrFail();

        $cardView = $this->actingAs($owner)->get(route('card.show'));
        $cardView->assertOk();
        $cardView->assertSee($charge->txid);
        $cardView->assertSee($charge->pix_payload);
        $cardView->assertDontSee('cardstore', false);
        $cardView->assertDontSee('6304ABCD', false);

        $this->actingAs($otherUser)
            ->getJson(route('card.pix.status', $charge))
            ->assertForbidden();

        $statusResponse = $this->actingAs($owner)
            ->getJson(route('card.pix.status', $charge));

        $statusResponse->assertOk()
            ->assertJson([
                'id' => $charge->id,
                'txid' => $charge->txid,
                'status' => 'pendente',
            ]);
    }

    public function test_unverified_user_cannot_access_card_or_checkout(): void
    {
        $unverified = User::factory()->unverified()->create();

        $this->actingAs($unverified)
            ->get(route('card.show'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($unverified)
            ->post(route('orders.checkout'), ['shipping_address' => 'Rua X'])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_cvv_is_not_printed_in_html_and_can_only_be_revealed_with_valid_pin(): void
    {
        $user = User::factory()->create();
        $card = $user->virtualCard;
        $card->update([
            'cvv' => '789',
            'security_pin' => Hash::make('4321'),
        ]);

        // Na visualização padrão, nem o número completo nem o CVV aparecem no HTML
        $response = $this->actingAs($user)->get(route('card.show'));
        $response->assertOk();
        $response->assertDontSee($card->formatted_number);
        $response->assertSee($card->masked_number);

        $this->actingAs($user)
            ->post(route('card.reveal-cvv'), ['pin' => '0000'])
            ->assertSessionHasErrors('pin');

        $this->actingAs($user)
            ->postJson(route('card.reveal-cvv'), ['pin' => '4321'])
            ->assertOk()
            ->assertJson([
                'card_number' => $card->formatted_number,
                'cvv' => '789',
            ]);
    }

    public function test_simulated_pix_gateway_generates_valid_crc16_brcode_and_idempotent_webhook(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('card.pix.generate'), ['amount' => 320.50])
            ->assertSessionHas('success');

        $recharge = $user->virtualCard->recharges()->where('payment_method', 'pix_dynamic')->firstOrFail();
        $this->assertNotEmpty($recharge->txid);
        $this->assertStringContainsString('br.gov.bcb.pix', (string) $recharge->pix_payload);

        $payloadWithoutCrc = substr((string) $recharge->pix_payload, 0, -4);
        $crcInPayload = substr((string) $recharge->pix_payload, -4);
        $this->assertEquals(SimulatedPixGateway::crc16Ccitt($payloadWithoutCrc), $crcInPayload);

        $this->actingAs($user)
            ->post(route('webhooks.pix.simulate'), ['txid' => $recharge->txid])
            ->assertSessionHas('success');

        $this->assertEquals(320.50, (float) $user->virtualCard->fresh()->balance);

        $this->actingAs($user)
            ->post(route('webhooks.pix.simulate'), ['txid' => $recharge->txid])
            ->assertSessionHas('success');

        $this->assertEquals(320.50, (float) $user->virtualCard->fresh()->balance);
        $this->assertEquals(1, $user->virtualCard->transactions()->count());
    }
}
