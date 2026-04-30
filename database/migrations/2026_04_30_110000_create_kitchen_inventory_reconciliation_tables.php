<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('source_id')->unique();
            $table->date('movement_date')->index();
            $table->foreignId('kitchen_daily_stock_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->index();
            $table->string('normalized_product_name')->index();
            $table->string('type', 40)->nullable()->index();
            $table->string('area')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('unit', 40)->nullable();
            $table->decimal('value', 13, 2)->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['movement_date', 'type']);
            $table->index(['movement_date', 'kitchen_daily_stock_item_id']);
        });

        Schema::create('kitchen_inventory_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('replenished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('operating_date')->unique();
            $table->string('status')->default('pending_count')->index();
            $table->timestamp('count_submitted_at')->nullable();
            $table->timestamp('replenishment_confirmed_at')->nullable();
            $table->boolean('has_negative_discrepancy')->default(false)->index();
            $table->boolean('has_replenishment_alert')->default(false)->index();
            $table->json('pending_mappings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('kitchen_inventory_closing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_inventory_closing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kitchen_daily_stock_item_id')->constrained()->cascadeOnDelete();
            $table->string('category_snapshot');
            $table->string('product_name_snapshot');
            $table->string('unit_snapshot', 40);
            $table->string('unit_detail_snapshot')->nullable();
            $table->decimal('target_stock_snapshot', 12, 4)->default(0);
            $table->decimal('initial_quantity', 12, 4)->default(0);
            $table->decimal('transfer_quantity', 12, 4)->default(0);
            $table->decimal('theoretical_consumption', 12, 4)->default(0);
            $table->decimal('theoretical_final', 12, 4)->nullable();
            $table->decimal('physical_count', 12, 4)->nullable();
            $table->decimal('waste_quantity', 12, 4)->default(0);
            $table->decimal('discrepancy', 12, 4)->nullable();
            $table->decimal('replenishment_required', 12, 4)->nullable();
            $table->decimal('replenishment_actual', 12, 4)->nullable();
            $table->decimal('next_initial_quantity', 12, 4)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('has_negative_discrepancy')->default(false)->index();
            $table->boolean('has_replenishment_alert')->default(false)->index();
            $table->timestamps();

            $table->unique(['kitchen_inventory_closing_id', 'kitchen_daily_stock_item_id'], 'kitchen_closing_item_unique');
        });

        Schema::create('kitchen_inventory_daily_starts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_daily_stock_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kitchen_inventory_closing_id')->nullable()->constrained()->nullOnDelete();
            $table->date('inventory_date')->index();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('source')->default('closing');
            $table->timestamps();

            $table->unique(['kitchen_daily_stock_item_id', 'inventory_date'], 'kitchen_daily_start_unique');
        });

        Schema::create('kitchen_inventory_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_standard_recipe_item_id')->constrained('restaurant_standard_recipe_items')->cascadeOnDelete();
            $table->foreignId('kitchen_daily_stock_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('conversion_factor', 12, 6)->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('restaurant_standard_recipe_item_id', 'kitchen_mapping_recipe_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_inventory_product_mappings');
        Schema::dropIfExists('kitchen_inventory_daily_starts');
        Schema::dropIfExists('kitchen_inventory_closing_items');
        Schema::dropIfExists('kitchen_inventory_closings');
        Schema::dropIfExists('kitchen_inventory_movements');
    }
};
