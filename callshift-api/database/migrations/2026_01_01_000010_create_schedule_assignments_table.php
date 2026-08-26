<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_version_id')->constrained('schedule_versions')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date')->index();
            $table->enum('day_type', ['WORK', 'REST', 'OFF', 'HOLIDAY', 'PERMISSION', 'ABSENCE'])->default('WORK')->index();
            $table->foreignId('shift_type_id')->nullable()->constrained('shift_types')->nullOnDelete();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->dateTime('starts_at')->nullable()->index(); // Intervalo exacto con fecha/hora
            $table->dateTime('ends_at')->nullable()->index();
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->decimal('total_hours', 4, 2)->default(0.00);
            $table->boolean('is_custom')->default(false);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['schedule_version_id', 'employee_id', 'date'], 'uniq_version_emp_date');
            $table->index(['schedule_version_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_assignments');
    }
};
