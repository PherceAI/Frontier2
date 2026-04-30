<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('area_user', function (Blueprint $table) {
            $table->boolean('is_lead')->default(false)->after('is_active')->index();
        });

        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->foreignId('validated_by')->nullable()->after('completed_by')->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable()->after('completed_at');
            $table->text('validation_notes')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('operational_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['validated_at', 'validation_notes']);
        });

        Schema::table('area_user', function (Blueprint $table) {
            $table->dropColumn('is_lead');
        });
    }
};
