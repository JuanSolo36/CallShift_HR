<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_version_id')->constrained('schedule_versions')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('conflict_key', 64)->index();
            $table->date('date')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->enum('severity', ['HARD_CONFLICT', 'SOFT_WARNING'])->default('HARD_CONFLICT')->index();
            $table->string('rule_violated', 80); // OVERLAPPING_SHIFTS, MIN_REST_BETWEEN_SHIFTS, etc.
            $table->string('description', 255);
            $table->text('suggested_resolution')->nullable();
            $table->foreignId('primary_assignment_id')->nullable()->constrained('schedule_assignments')->nullOnDelete();
            $table->foreignId('conflicting_assignment_id')->nullable()->constrained('schedule_assignments')->nullOnDelete();
            $table->enum('status', ['ACTIVE', 'RESOLVED', 'AUTO_CLEARED'])->default('ACTIVE')->index();
            $table->boolean('is_resolved')->default(false)->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_reason')->nullable();
            $table->timestamps();

            $table->unique(['schedule_version_id', 'conflict_key'], 'conflicts_version_key_unique');
            $table->index(['schedule_version_id', 'status', 'severity']);
            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_conflicts');
    }
};
