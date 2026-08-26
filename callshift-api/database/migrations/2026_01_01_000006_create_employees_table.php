<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('employee_code', 30);
            $table->string('document_type', 20)->default('CC');
            $table->string('document_number', 40);
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable();
            $table->string('last_name', 60);
            $table->string('second_last_name', 60)->nullable();
            $table->string('email', 120);
            $table->string('personal_email', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            $table->foreignId('employment_type_id')->constrained('employment_types')->restrictOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'ON_LEAVE', 'TERMINATED'])->default('ACTIVE')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_code']);
            $table->unique(['company_id', 'document_number']);
            $table->index(['company_id', 'email']);
            $table->index(['company_id', 'department_id', 'status']);
            $table->index(['company_id', 'supervisor_id']);
        });

        // Add FK to manager_id in departments
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
        Schema::dropIfExists('employees');
    }
};
