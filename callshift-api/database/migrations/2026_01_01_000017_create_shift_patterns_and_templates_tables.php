<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Patrones de Turno (Cabecera)
        Schema::create('shift_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('name', 100);
            $table->string('code', 30);
            $table->unsignedSmallInteger('cycle_length_days')->default(7);
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'patterns_company_code_unique');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'department_id']);
        });

        // 2. Entradas/Días del Patrón Cíclico
        Schema::create('shift_pattern_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_pattern_id')->constrained('shift_patterns')->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number'); // Día del ciclo (1 .. cycle_length_days)
            $table->string('day_type', 20)->default('WORK'); // WORK, REST, OFF, HOLIDAY, PERMISSION, ABSENCE
            $table->foreignId('shift_type_id')->nullable()->constrained('shift_types')->nullOnDelete();
            $table->time('start_time_override')->nullable();
            $table->time('end_time_override')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['shift_pattern_id', 'day_number'], 'pattern_entries_pattern_day_unique');
            $table->index(['shift_pattern_id', 'day_number']);
        });

        // 3. Plantillas de Planificación
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('shift_pattern_id')->nullable()->constrained('shift_patterns')->nullOnDelete();
            $table->string('name', 100);
            $table->string('code', 30);
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->index();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'templates_company_code_unique');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
        Schema::dropIfExists('shift_pattern_entries');
        Schema::dropIfExists('shift_patterns');
    }
};
