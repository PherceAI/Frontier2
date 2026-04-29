<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contifico_documents', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 32)->unique();
            $table->string('tipo_registro', 3)->index();
            $table->string('tipo_documento', 8)->nullable()->index();
            $table->string('documento', 32)->nullable();
            $table->string('estado', 8)->nullable()->index();
            $table->boolean('anulado')->default(false)->index();
            $table->date('fecha_emision')->nullable()->index();
            $table->date('fecha_vencimiento')->nullable()->index();
            $table->decimal('total', 13, 2)->default(0);
            $table->decimal('saldo', 13, 2)->default(0);
            $table->decimal('servicio', 13, 2)->default(0);
            $table->string('vendedor_id', 32)->nullable()->index();
            $table->string('vendedor_nombre')->nullable();
            $table->string('persona_nombre')->nullable();
            $table->json('detalles')->nullable();
            $table->json('cobros')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contifico_products', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 32)->unique();
            $table->string('nombre')->nullable();
            $table->json('raw')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contifico_products');
        Schema::dropIfExists('contifico_documents');
    }
};
