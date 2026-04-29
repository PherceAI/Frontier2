<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_occupancy_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('occupancy_date');
            $table->string('room_number');
            $table->string('room_type')->nullable();
            $table->unsignedSmallInteger('floor')->nullable();
            $table->string('status')->default('unknown');
            $table->boolean('is_occupied')->default(false);
            $table->string('guest_name')->nullable();
            $table->string('reservation_code')->nullable();
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->unsignedSmallInteger('adults')->nullable();
            $table->unsignedSmallInteger('children')->nullable();
            $table->decimal('balance', 10, 2)->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['occupancy_date', 'room_number']);
            $table->index(['occupancy_date', 'is_occupied']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_occupancy_snapshots');
    }
};
