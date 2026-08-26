<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Periodos de Planificación
        Schema::create('work_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name', 100);
            $table->enum('period_type', ['WEEKLY', 'BIWEEKLY', 'MONTHLY', 'CUSTOM'])->default('WEEKLY');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->enum('status', ['DRAFT', 'GENERATED', 'REVIEW', 'PUBLISHED', 'CLOSED'])->default('DRAFT')->index();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'department_id', 'status']);
        });

        // Versiones Inmutables de Horarios
        Schema::create('schedule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_period_id')->constrained('work_periods')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT')->index();
            $table->unsignedBigInteger('parent_version_id')->nullable();
            $table->text('change_summary')->nullable();
            $table->decimal('score', 5, 2)->nullable(); // Puntuación de calidad 0.00 a 100.00
            $table->unsignedInteger('hard_conflicts_count')->default(0);
            $table->unsignedInteger('soft_conflicts_count')->default(0);
            $table->unsignedInteger('lock_version')->default(1); // Control de concurrencia optimista
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['work_period_id', 'version_number']);
            $table->foreign('parent_version_id')->references('id')->on('schedule_versions')->nullOnDelete();
        });

        // Add FK from work_periods to schedule_versions
        Schema::table('work_periods', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('schedule_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_periods', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('schedule_versions');
        Schema::dropIfExists('work_periods');
    }
};
