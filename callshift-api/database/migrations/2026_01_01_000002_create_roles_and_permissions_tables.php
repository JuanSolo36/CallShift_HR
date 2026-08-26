<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('code', 40)->index(); // SUPER_ADMIN, HR_ADMIN, MANAGER, SUPERVISOR, EMPLOYEE, VIEWER
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false); // Roles protegidos del sistema
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // Catálogo de Permisos Granulares
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->index(); // employees, schedules, absences, reports, audit, users, settings
            $table->string('action', 50);          // view, create, update, delete, generate, publish, export, restore
            $table->string('code', 100)->unique(); // employees:view, schedules:publish, etc.
            $table->string('description', 255);
            $table->timestamps();
        });

        // Tabla Pivote Roles - Permisos
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
