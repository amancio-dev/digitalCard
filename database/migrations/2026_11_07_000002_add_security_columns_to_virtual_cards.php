<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserva histórico financeiro quando contas ou registros são removidos
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('virtual_cards', function (Blueprint $table) {
            $table->string('card_last_four', 4)->default('0000')->after('user_id');
            $table->string('card_hash', 64)->nullable()->unique()->after('card_last_four');
            $table->softDeletes();
        });

        Schema::table('recharges', function (Blueprint $table) {
            $table->string('payment_method', 30)->default('receipt')->after('virtual_card_id');
            $table->string('txid', 64)->nullable()->unique()->after('payment_method');
            $table->text('pix_payload')->nullable()->after('txid');
            $table->timestamp('expires_at')->nullable()->after('pix_payload');
            $table->string('receipt_path')->nullable()->change();
            $table->string('receipt_hash', 64)->nullable()->change();
            $table->softDeletes();
        });

        Schema::table('card_transactions', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['virtual_card_id', 'created_at'], 'idx_card_tx_card_created');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('user_id');
            $table->decimal('subtotal_amount', 12, 2)->default(0.00)->after('idempotency_key');
            $table->decimal('discount_amount', 12, 2)->default(0.00)->after('subtotal_amount');
            $table->string('coupon_code', 50)->nullable()->after('discount_amount');
            $table->decimal('shipping_cost', 12, 2)->default(0.00)->after('coupon_code');
            $table->string('shipping_cep', 15)->nullable()->after('shipping_address');
            $table->timestamp('cashback_credited_at')->nullable()->after('delivered_at');
            $table->softDeletes();

            $table->index(['user_id', 'status'], 'idx_orders_user_status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'is_active', 'stock'], 'idx_products_cat_active_stock');
            $table->index(['is_active', 'stock'], 'idx_products_active_stock');
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->boolean('is_verified_purchase')->default(false)->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn('is_verified_purchase');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_cat_active_stock');
            $table->dropIndex('idx_products_active_stock');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_status');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'idempotency_key',
                'subtotal_amount',
                'discount_amount',
                'coupon_code',
                'shipping_cost',
                'shipping_cep',
                'cashback_credited_at',
            ]);
        });

        Schema::table('card_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_card_tx_card_created');
            $table->dropSoftDeletes();
        });

        Schema::table('recharges', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['payment_method', 'txid', 'pix_payload', 'expires_at']);
        });

        Schema::table('virtual_cards', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['card_last_four', 'card_hash']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
