<?php

namespace Database\Seeders;

use App\Models\CardTransaction;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\VirtualCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Catálogo base de Categorias e Produtos (45 produtos)
        $this->call(CatalogSeeder::class);

        // 2. Contas de demonstração e saldos fictícios APENAS em ambiente local ou de testes (S4)
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('Ambiente não-local detectado: contas de demonstração e saldos fictícios foram ignorados por segurança.');

            return;
        }

        $adminPlainPassword = (string) env('ADMIN_INITIAL_PASSWORD', '');
        $generatedAdminPassword = false;

        if ($adminPlainPassword === '') {
            $adminPlainPassword = Str::password(16);
            $generatedAdminPassword = true;
        }

        $clientPlainPassword = (string) env('DEMO_CLIENT_PASSWORD', '');
        $generatedClientPassword = false;

        if ($clientPlainPassword === '') {
            $clientPlainPassword = Str::password(16);
            $generatedClientPassword = true;
        }

        // 3. Usuário Administrador (Demonstração Local/Testing)
        $admin = User::updateOrCreate(
            ['email' => 'admin@digitalcard.com'],
            [
                'name' => 'Administrador DigitalCard',
                'password' => Hash::make($adminPlainPassword),
            ]
        );
        $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        $adminCard = $admin->virtualCard()->firstOrCreate([], [
            'card_number' => VirtualCard::generateUniqueCardNumber(),
            'cvv' => VirtualCard::generateCvv(),
            'expiration_date' => VirtualCard::DEFAULT_EXPIRATION_DATE,
            'balance' => 0.00,
            'cashback_earned' => 0.00,
        ]);

        $adminCard->update([
            'balance' => 2500.00,
            'cashback_earned' => 120.00,
            'security_pin' => Hash::make('1234'),
        ]);

        CardTransaction::firstOrCreate(
            [
                'virtual_card_id' => $adminCard->id,
                'description' => 'Saldo inicial de demonstração (Administrador)',
            ],
            [
                'type' => CardTransaction::TYPE_RECHARGE,
                'amount' => 2500.00,
                'balance_after' => 2500.00,
            ]
        );

        // 4. Usuário Cliente (Demonstração Local/Testing)
        $client = User::updateOrCreate(
            ['email' => 'cliente@digitalcard.com'],
            [
                'name' => 'Cliente Demonstração',
                'password' => Hash::make($clientPlainPassword),
            ]
        );
        $client->forceFill(['is_admin' => false, 'email_verified_at' => now()])->save();

        $clientCard = $client->virtualCard()->firstOrCreate([], [
            'card_number' => VirtualCard::generateUniqueCardNumber(),
            'cvv' => VirtualCard::generateCvv(),
            'expiration_date' => VirtualCard::DEFAULT_EXPIRATION_DATE,
            'balance' => 0.00,
            'cashback_earned' => 0.00,
        ]);

        $clientCard->update([
            'balance' => 7500.00,
            'cashback_earned' => 185.50,
            'security_pin' => Hash::make('1234'),
        ]);

        CardTransaction::firstOrCreate(
            [
                'virtual_card_id' => $clientCard->id,
                'description' => 'Saldo inicial de demonstração (Cliente)',
            ],
            [
                'type' => CardTransaction::TYPE_RECHARGE,
                'amount' => 7500.00,
                'balance_after' => 7500.00,
            ]
        );

        if ($generatedAdminPassword) {
            $this->command?->warn("[SEED LOCAL] Senha gerada para admin@digitalcard.com (exibida uma única vez): {$adminPlainPassword}");
        }

        if ($generatedClientPassword) {
            $this->command?->warn("[SEED LOCAL] Senha gerada para cliente@digitalcard.com (exibida uma única vez): {$clientPlainPassword}");
        }

        // 5. Avaliações Iniciais de Demonstração
        $sampleReviews = [
            'smartphone-galaxy-s24-ultra' => '[Avaliação de demonstração] Aparelho espetacular! Comprei com o saldo do Cartão Virtual, entrou na hora como Produto solicitado e quando foi entregue já recebi o cashback direto na carteira.',
            'playstation-5-slim-1tb' => '[Avaliação de demonstração] Entrega muito rápida! Console lacrado e o desconto com o cupom DIGITAL10 valeu muito a pena.',
            'headphone-sony-wh-1000xm5-bluetooth' => '[Avaliação de demonstração] Ótimo cancelamento de ruído. Pagamento pelo DigitalCard aprovou no mesmo segundo.',
            'air-fryer-philips-walita-essential-xl' => '[Avaliação de demonstração] Muito espaçosa e silenciosa, além de dar 6% de cashback de volta no cartão virtual!',
        ];

        foreach ($sampleReviews as $slug => $comment) {
            if ($prod = Product::where('slug', $slug)->first()) {
                ProductReview::updateOrCreate(
                    ['product_id' => $prod->id, 'user_id' => $client->id],
                    ['rating' => 5, 'comment' => $comment]
                );
            }
        }
    }
}
