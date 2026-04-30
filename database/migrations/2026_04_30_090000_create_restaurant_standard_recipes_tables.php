<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_standard_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('dish_code')->nullable()->unique();
            $table->string('dish_name');
            $table->string('category')->nullable()->index();
            $table->string('subcategory')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index('dish_name');
        });

        Schema::create('restaurant_standard_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_standard_recipe_id')
                ->constrained('restaurant_standard_recipes')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('inventory_product_id')->nullable()->index();
            $table->string('inventory_product_name');
            $table->decimal('quantity_used', 12, 4)->default(0);
            $table->string('unit');
            $table->string('equivalence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_standard_recipe_id', 'sort_order'], 'recipe_items_recipe_sort_unique');
            $table->index(['inventory_product_name', 'unit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_standard_recipe_items');
        Schema::dropIfExists('restaurant_standard_recipes');
    }
};
