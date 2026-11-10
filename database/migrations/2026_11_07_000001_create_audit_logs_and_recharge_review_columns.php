<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recharges', function (Blueprint $table) {
            $table->decimal('approved_amount', 12, 2)->nullable()->after('amount');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('admin_notes')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('auditable_type', 150);
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
        });

        if (Storage::disk('public')->exists('receipts')) {
            foreach (Storage::disk('public')->files('receipts') as $file) {
                if (! Storage::disk('local')->exists($file)) {
                    Storage::disk('local')->put($file, Storage::disk('public')->get($file));
                }
                Storage::disk('public')->delete($file);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::table('recharges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('approved_amount');
        });
    }
};
