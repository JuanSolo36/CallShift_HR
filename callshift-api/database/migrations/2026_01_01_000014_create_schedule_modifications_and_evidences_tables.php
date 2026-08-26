<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro formal de modificaciones sobre horarios publicados
        Schema::create('schedule_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_assignment_id')->constrained('schedule_assignments')->cascadeOnDelete();
            $table->foreignId('schedule_version_id')->constrained('schedule_versions')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('modification_type', 50)->default('TIME_CHANGE')->index();
            $table->json('previous_data'); // Snapshot del estado anterior
            $table->json('new_data');      // Snapshot del nuevo estado asignado
            $table->text('reason');        // Justificación obligatoria
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['schedule_version_id', 'employee_id']);
        });

        // Archivos adjuntos y evidencias probatorias
        Schema::create('modification_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_modification_id')->constrained('schedule_modifications')->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_filename', 255);
            $table->string('storage_path', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('sha256_hash', 64);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modification_evidences');
        Schema::dropIfExists('schedule_modifications');
    }
};
