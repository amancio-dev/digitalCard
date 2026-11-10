<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona flag de administrador na tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email')->index();
        });

        // 1. Cartões Virtuais
        Schema::create('virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('card_number', 19)->unique();
            $table->string('cvv', 3);
            $table->string('expiration_date', 5);
            $table->string('security_pin')->nullable();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->decimal('cashback_earned', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Recargas via Comprovante
        Schema::create('recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_card_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('receipt_path');
            $table->string('receipt_hash', 64)->unique();
            $table->string('status')->default('pendente')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // 3. Extrato / Ledger do Cartão Virtual
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_card_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description');
            $table->timestamps();
        });

        // 4. Categorias de Produtos (Suporta subcategorias)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // 5. Produtos
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cashback_percent', 5, 2)->default(2.00);
            $table->unsignedInteger('stock')->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Pedidos (Status exigidos: "Produto solicitado" e "Produto entregue")
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('cashback_total', 12, 2)->default(0.00);
            $table->string('status')->default('Produto solicitado')->index();
            $table->string('shipping_address');
            $table->string('tracking_code')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        // 7. Itens do Pedido
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->string('item_status')->default('Produto solicitado')->index();
            $table->timestamps();
        });

        // 8. Avaliações de Produtos
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('card_transactions');
        Schema::dropIfExists('recharges');
        Schema::dropIfExists('virtual_cards');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
