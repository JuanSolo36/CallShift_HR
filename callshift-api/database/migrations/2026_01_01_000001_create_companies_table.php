<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('legal_name', 200);
            $table->string('tax_id', 50)->unique();
            $table->string('slug', 100)->nullable()->unique();
            $table->string('email', 120);
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 3)->default('COL'); // Código ISO Alpha-3
            $table->string('timezone', 50)->default('America/Bogota');
            $table->string('currency', 10)->default('COP');
            $table->string('date_format', 20)->default('YYYY-MM-DD');
            $table->string('logo', 255)->nullable();
            $table->string('primary_color', 20)->default('#0284c7');
            $table->string('secondary_color', 20)->default('#0f172a');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
