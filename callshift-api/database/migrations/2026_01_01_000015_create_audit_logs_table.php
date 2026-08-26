<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', [
                'LOGIN',
                'LOGOUT',
                'CREATE',
                'UPDATE',
                'DELETE',
                'GENERATE',
                'PUBLISH',
                'MODIFY',
                'EXPORT',
                'RESTORE'
            ])->index();
            $table->string('auditable_type', 100)->index(); // App\Models\Employee, ScheduleVersion, etc.
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->string('description', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->index(); // Append-only inmutable

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
