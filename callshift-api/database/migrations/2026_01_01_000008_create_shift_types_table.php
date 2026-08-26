<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('code', 30);
            $table->string('color_hex', 7)->default('#3B82F6'); // Color en la malla
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_duration_minutes')->default(60);
            $table->decimal('total_work_hours', 4, 2); // Horas efectivas computables
            $table->boolean('crosses_midnight')->default(false); // 22:00 -> 06:00
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_types');
    }
};
