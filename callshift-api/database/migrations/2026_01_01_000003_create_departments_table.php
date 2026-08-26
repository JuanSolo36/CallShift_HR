<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 30);
            $table->string('cost_center_code', 30)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable(); // FK to employees
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
