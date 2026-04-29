<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->timestamp('generated_at')->nullable()->index();
            $table->string('timezone')->nullable();
            $table->unsignedInteger('total_products')->default(0);
            $table->decimal('inventory_value', 12, 2)->default(0);
            $table->decimal('payables_total', 12, 2)->default(0);
            $table->decimal('payables_overdue', 12, 2)->default(0);
            $table->unsignedInteger('pending_documents')->default(0);
            $table->decimal('hotel_inventory_value', 12, 2)->default(0);
            $table->decimal('restaurant_inventory_value', 12, 2)->default(0);
            $table->json('payload');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_inventory_snapshots');
    }
};
