<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 150);
            $table->string('type', 20)->default('percent');
            $table->decimal('value', 12, 2);
            $table->decimal('min_total', 12, 2)->default(0.00);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->decimal('discount_amount', 12, 2);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
        });

        $now = now();
        DB::table('coupons')->insertOrIgnore([
            [
                'code' => 'DIGITAL10',
                'label' => '10% OFF no DigitalCard',
                'type' => 'percent',
                'value' => 10.00,
                'min_total' => 0.00,
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addYear(),
                'max_uses' => 1000,
                'used_count' => 0,
                'per_user_limit' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'BEMVINDO50',
                'label' => 'R$ 50,00 OFF em compras acima de R$ 500',
                'type' => 'fixed',
                'value' => 50.00,
                'min_total' => 500.00,
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addYear(),
                'max_uses' => 500,
                'used_count' => 0,
                'per_user_limit' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'CASHBACKTURBO',
                'label' => '5% OFF Imediato + Cashback Proporcional',
                'type' => 'percent',
                'value' => 5.00,
                'min_total' => 0.00,
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addYear(),
                'max_uses' => 1000,
                'used_count' => 0,
                'per_user_limit' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
