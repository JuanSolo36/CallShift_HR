<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reglas de negocio y restricciones laborales configurables
        Schema::create('business_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->unsignedBigInteger('department_scope_id')->default(0)->index(); // 0 = regla global de empresa, >0 = department_id
            $table->decimal('max_daily_hours', 4, 1)->nullable()->default(10.0);
            $table->decimal('min_daily_hours', 4, 1)->nullable()->default(4.0);
            $table->decimal('max_weekly_hours', 4, 1)->nullable()->default(48.0);
            $table->decimal('min_weekly_hours', 4, 1)->nullable()->default(20.0);
            $table->decimal('min_rest_hours_between_shifts', 4, 1)->nullable()->default(12.0); // Mínimo legal descanso
            $table->unsignedTinyInteger('max_consecutive_work_days')->nullable()->default(6);
            $table->boolean('allow_night_shifts')->nullable()->default(true);
            $table->enum('weekend_rotation_policy', ['STRICT_ROTATION', 'FAIR_SHARE', 'NONE'])->nullable()->default('FAIR_SHARE');
            $table->timestamps();

            $table->unique(['company_id', 'department_scope_id'], 'business_rules_company_dept_unique');
        });

        // Días festivos oficiales
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('name', 100);
            $table->boolean('is_mandatory_rest')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'date']);
        });

        // Ajustes y parámetros del sistema
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('key', 80);
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string'); // string, json, boolean, integer
            $table->timestamps();

            $table->unique(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('business_rules');
    }
};
