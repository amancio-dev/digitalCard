<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 60)->default('Principal');
            $table->string('recipient_name', 120)->nullable();
            $table->string('cep', 9);
            $table->string('street', 180);
            $table->string('number', 30)->default('S/N');
            $table->string('complement', 120)->nullable();
            $table->string('neighborhood', 120)->nullable();
            $table->string('city', 120);
            $table->char('state', 2);
            $table->boolean('is_default')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
