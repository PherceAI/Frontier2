<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('source')->default('manual');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('severity')->default('normal');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['area_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['starts_at', 'status']);
            $table->index(['source', 'severity']);
        });

        Schema::create('operational_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('task');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('priority')->default('normal');
            $table->boolean('requires_validation')->default(false);
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['assigned_area_id', 'status']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['operational_event_id', 'status']);
            $table->index(['due_at', 'status']);
            $table->index(['requires_validation', 'status']);
        });

        Schema::create('operational_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('context')->default('general');
            $table->string('status')->default('active');
            $table->json('schema');
            $table->timestamps();

            $table->index(['area_id', 'context', 'status']);
        });

        Schema::create('operational_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operational_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operational_task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('submitted');
            $table->json('payload');
            $table->timestamps();

            $table->index(['area_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['operational_task_id', 'status']);
            $table->index(['operational_event_id', 'status']);
        });

        Schema::create('operational_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operational_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operational_task_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('channel')->default('webpush');
            $table->string('status')->default('pending');
            $table->string('title');
            $table->text('body')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['area_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_notifications');
        Schema::dropIfExists('operational_entries');
        Schema::dropIfExists('operational_forms');
        Schema::dropIfExists('operational_tasks');
        Schema::dropIfExists('operational_events');
    }
};
