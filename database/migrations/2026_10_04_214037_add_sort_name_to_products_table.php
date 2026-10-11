<?php

use App\Models\Product;
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
        Schema::table('products', function (Blueprint $table) {
            // Normalized name used for alphabetical sorting (see Product::sortKey()).
            $table->string('sort_name')->nullable()->after('name')->index();
        });

        // Backfill existing products.
        Product::query()->select(['id', 'name'])->chunkById(500, function ($products) {
            foreach ($products as $product) {
                Product::whereKey($product->id)->update(['sort_name' => Product::sortKey($product->name)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sort_name');
        });
    }
};
