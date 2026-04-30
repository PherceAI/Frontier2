<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_daily_stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('product_name');
            $table->decimal('target_stock', 12, 4)->default(0);
            $table->string('unit', 40);
            $table->string('unit_detail')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['category', 'product_name']);
            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_daily_stock_items');
    }
};
