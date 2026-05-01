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
        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('status')->index();
            $table->string('cleaning_type')->default('cleaning')->after('type')->index();
            $table->string('assignment_source')->default('manual')->after('assigned_to')->index();
            $table->date('generated_for_date')->nullable()->after('scheduled_date')->index();
            $table->foreignId('occupancy_snapshot_id')->nullable()->after('room_id')->constrained('room_occupancy_snapshots')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable()->after('scheduled_at');
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable()->after('notes');

            $table->unique(['room_id', 'scheduled_date', 'cleaning_type'], 'housekeeping_room_date_type_unique');
            $table->index(['assigned_to', 'scheduled_date', 'status']);
        });

        Schema::create('room_cleaning_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('auto_assignment_enabled')->default(true);
            $table->json('working_days');
            $table->time('assignment_time')->default('07:00:00');
            $table->string('assignment_strategy')->default('floor_zone');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('housekeeping_task_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housekeeping_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('severity')->default('normal')->index();
            $table->text('body');
            $table->timestamps();

            $table->index(['housekeeping_task_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('housekeeping_task_notes');
        Schema::dropIfExists('room_cleaning_settings');

        Schema::table('housekeeping_tasks', function (Blueprint $table) {
            $table->dropUnique('housekeeping_room_date_type_unique');
            $table->dropIndex(['assigned_to', 'scheduled_date', 'status']);
            $table->dropConstrainedForeignId('completed_by');
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropConstrainedForeignId('occupancy_snapshot_id');
            $table->dropColumn([
                'scheduled_date',
                'cleaning_type',
                'assignment_source',
                'generated_for_date',
                'started_at',
                'metadata',
            ]);
        });
    }
};
