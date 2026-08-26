<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['RECURRING', 'SPECIFIC_DATE'])->default('RECURRING');
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 1=Lunes ... 7=Domingo
            $table->date('specific_date')->nullable()->index();
            $table->boolean('is_available')->default(true); // true=Disponible, false=No disponible
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('priority', ['PREFERENCE', 'STRICT_RESTRICTION'])->default('PREFERENCE');
            $table->string('notes', 255)->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->index();
            $table->timestamps();

            $table->index(['employee_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
