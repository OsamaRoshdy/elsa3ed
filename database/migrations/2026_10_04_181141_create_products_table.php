<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daftra_id')->unique();
            $table->string('sku')->nullable()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('image', 1024)->nullable();
            $table->string('category')->nullable()->index();
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('stock_quantity', 14, 2)->nullable();
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
