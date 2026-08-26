<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solicitudes formales de permiso / vacaciones por parte del empleado
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', [
                'SICK_LEAVE',
                'VACATION',
                'PERMISSION',
                'BEREAVEMENT',
                'UNEXCUSED',
                'MATERNITY_PATERNITY',
                'OTHER'
            ])->default('PERMISSION');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_full_day')->default(true);
            $table->text('reason');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('PENDING')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'employee_id', 'status']);
        });

        // Ausencias e incapacidades consolidadas en el calendario
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $table->enum('type', [
                'SICK_LEAVE',
                'VACATION',
                'PERMISSION',
                'BEREAVEMENT',
                'UNEXCUSED',
                'MATERNITY_PATERNITY',
                'OTHER'
            ])->default('PERMISSION');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_full_day')->default(true);
            $table->text('reason')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('APPROVED')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'employee_id', 'start_date', 'end_date'], 'idx_abs_comp_emp_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
        Schema::dropIfExists('leave_requests');
    }
};
