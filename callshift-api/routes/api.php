<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\EmploymentTypeController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\ShiftTypeController;
use App\Http\Controllers\Api\V1\WorkPeriodController;
use App\Http\Controllers\Api\V1\ScheduleEditorController;
use App\Http\Controllers\Api\V1\ShiftPatternController;
use App\Http\Controllers\Api\V1\ShiftTemplateController;
use App\Http\Controllers\Api\V1\PatternApplicationController;
use App\Http\Controllers\Api\V1\BusinessRuleController;
use App\Http\Controllers\Api\V1\ScheduleConflictController;
use App\Http\Controllers\Api\V1\ScheduleVersionController;
use App\Http\Controllers\Api\V1\ScheduleModificationController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Responses\ApiResponse;

/*
|--------------------------------------------------------------------------
| CallShift HR API v1 Routes
|--------------------------------------------------------------------------
| Prefix: /api/v1
*/

// Health Check Endpoint
Route::get('/health', function () {
    return ApiResponse::success([
        'name'        => 'CallShift HR API',
        'version'     => '1.0.0',
        'status'      => 'healthy',
        'timestamp'   => now()->toIso8601String(),
        'php_version' => PHP_VERSION,
        'environment' => config('app.env', 'local'),
    ], 'Servicio CallShift HR API operativo.');
});

// Rutas Públicas de Autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Rutas Protegidas por Sanctum & Tenant Activo
Route::middleware(['auth:sanctum', 'company.active'])->group(function () {

    // Autenticación de Sesión (FASE 4)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/password', [AuthController::class, 'changePassword'])->name('auth.password');
    });

    // Gestión de Usuarios (FASE 6)
    Route::patch('users/{id}/status', [UserController::class, 'changeStatus'])->name('users.status');
    Route::apiResource('users', UserController::class);

    // Roles y Permisos (FASE 6)
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{id}', [RoleController::class, 'show'])->name('roles.show');
    Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');

    // Empresa y Configuración del Sistema (FASE 7)
    Route::prefix('company')->group(function () {
        Route::get('/', [CompanyController::class, 'show'])->name('company.show');
        Route::put('/', [CompanyController::class, 'update'])->name('company.update');
        Route::patch('/settings', [CompanyController::class, 'updateSettings'])->name('company.settings');
    });

    // Estructura Organizacional (FASE 8)
    Route::get('departments/compact', [DepartmentController::class, 'compact'])->name('departments.compact');
    Route::apiResource('departments', DepartmentController::class);

    Route::get('positions/compact', [PositionController::class, 'compact'])->name('positions.compact');
    Route::apiResource('positions', PositionController::class);

    // Tipos de Contrato y Empleo (FASE 9)
    Route::get('employment-types/compact', [EmploymentTypeController::class, 'compact'])->name('employment-types.compact');
    Route::apiResource('employment-types', EmploymentTypeController::class);

    // Empleados y Expedientes Laborales (FASE 10)
    Route::patch('employees/{id}/status', [EmployeeController::class, 'changeStatus'])->name('employees.status');
    Route::get('employees/compact', [EmployeeController::class, 'compact'])->name('employees.compact');
    Route::apiResource('employees', EmployeeController::class);

    // Tipos de Turno (FASE 11)
    Route::get('shift-types/compact', [ShiftTypeController::class, 'compact'])->name('shift-types.compact');
    Route::apiResource('shift-types', ShiftTypeController::class);

    // Periodos Laborales (FASE 12)
    Route::patch('work-periods/{id}/status', [WorkPeriodController::class, 'changeStatus'])->name('work-periods.status');
    Route::get('work-periods/compact', [WorkPeriodController::class, 'compact'])->name('work-periods.compact');
    Route::apiResource('work-periods', WorkPeriodController::class);

    // Editor Manual de Horarios (FASE 13)
    Route::get('work-periods/{workPeriodId}/schedule', [ScheduleEditorController::class, 'getScheduleByPeriod'])->name('work-periods.schedule');
    Route::post('work-periods/{workPeriodId}/schedule/assignments', [ScheduleEditorController::class, 'upsertByWorkPeriod'])->name('work-periods.schedule.assignments.store');
    Route::put('work-periods/{workPeriodId}/schedule/assignments/{assignmentId}', [ScheduleEditorController::class, 'upsertByWorkPeriod'])->name('work-periods.schedule.assignments.update');
    Route::delete('work-periods/{workPeriodId}/schedule/assignments/{assignmentId}', [ScheduleEditorController::class, 'destroyByWorkPeriod'])->name('work-periods.schedule.assignments.destroy');

    Route::get('schedule-versions/{versionId}/grid', [ScheduleEditorController::class, 'getGridByVersion'])->name('schedule-versions.grid');
    Route::get('schedule-versions/{versionId}/assignments', [ScheduleEditorController::class, 'listAssignments'])->name('schedule-versions.assignments.index');
    Route::post('schedule-versions/{versionId}/assignments', [ScheduleEditorController::class, 'upsertAssignment'])->name('schedule-versions.assignments.store');
    Route::put('schedule-versions/{versionId}/assignments/{assignmentId}', [ScheduleEditorController::class, 'updateAssignment'])->name('schedule-versions.assignments.update');
    Route::delete('schedule-versions/{versionId}/assignments/{assignmentId}', [ScheduleEditorController::class, 'destroyAssignment'])->name('schedule-versions.assignments.destroy');

    // Patrones de Turno y Plantillas (FASE 14)
    Route::apiResource('shift-patterns', ShiftPatternController::class);
    Route::apiResource('shift-templates', ShiftTemplateController::class);
    Route::post('schedule-versions/{versionId}/apply-pattern/preview', [PatternApplicationController::class, 'preview'])->name('schedule-versions.apply-pattern.preview');
    Route::post('schedule-versions/{versionId}/apply-pattern', [PatternApplicationController::class, 'apply'])->name('schedule-versions.apply-pattern');

    // Reglas de Negocio y Restricciones Laborales (FASE 15)
    Route::apiResource('business-rules', BusinessRuleController::class);

    // Motor de Detección y Resolución de Conflictos (FASE 15)
    Route::post('schedule-versions/{versionId}/validate', [ScheduleConflictController::class, 'validateVersion'])->name('schedule-versions.validate');
    Route::get('schedule-versions/{versionId}/conflicts', [ScheduleConflictController::class, 'indexByVersion'])->name('schedule-versions.conflicts.index');
    Route::patch('schedule-conflicts/{conflict}/resolve', [ScheduleConflictController::class, 'resolve'])->name('schedule-conflicts.resolve');

    // Versionamiento de Horarios (FASE 16)
    Route::get('work-periods/{workPeriodId}/versions', [ScheduleVersionController::class, 'index'])->name('work-periods.versions.index');
    Route::post('work-periods/{workPeriodId}/versions', [ScheduleVersionController::class, 'store'])->name('work-periods.versions.store');
    Route::post('work-periods/{workPeriodId}/versions/restore', [ScheduleVersionController::class, 'restore'])->name('work-periods.versions.restore');

    Route::get('schedule-versions/{id}', [ScheduleVersionController::class, 'show'])->name('schedule-versions.show');
    Route::patch('schedule-versions/{id}/review', [ScheduleVersionController::class, 'review'])->name('schedule-versions.review');
    Route::patch('schedule-versions/{id}/return-to-draft', [ScheduleVersionController::class, 'returnToDraft'])->name('schedule-versions.return-to-draft');
    Route::post('schedule-versions/{id}/publish', [ScheduleVersionController::class, 'publish'])->name('schedule-versions.publish');
    Route::get('schedule-versions/{id}/compare/{otherVersionId}', [ScheduleVersionController::class, 'compare'])->name('schedule-versions.compare');

    // Modificaciones de Horarios y Evidencias Documentales (FASE 17)
    Route::get('schedule-versions/{versionId}/modifications', [ScheduleModificationController::class, 'index'])->name('schedule-versions.modifications.index');
    Route::post('schedule-versions/{versionId}/modifications', [ScheduleModificationController::class, 'store'])->name('schedule-versions.modifications.store');
    Route::get('schedule-modifications/{id}', [ScheduleModificationController::class, 'show'])->name('schedule-modifications.show');
    Route::post('schedule-modifications/{id}/evidences', [ScheduleModificationController::class, 'attachEvidence'])->name('schedule-modifications.evidences.store');
    Route::get('schedule-modifications/{id}/evidences/{evidenceId}/download', [ScheduleModificationController::class, 'downloadEvidence'])->name('schedule-modifications.evidences.download');
    Route::delete('schedule-modifications/{id}/evidences/{evidenceId}', [ScheduleModificationController::class, 'destroyEvidence'])->name('schedule-modifications.evidences.destroy');

    // Trazabilidad y Bitácora Forense de Auditoría (FASE 18)
    Route::get('audit-logs', [AuditController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/export', [AuditController::class, 'export'])->name('audit-logs.export');
    Route::get('audit-logs/{id}', [AuditController::class, 'show'])->name('audit-logs.show');

    // Reportes Empresariales y Exportación (FASE 19)
    Route::get('reports/employees', [ReportController::class, 'employees'])->name('reports.employees');
    Route::get('reports/employees/export', [ReportController::class, 'exportEmployees'])->name('reports.employees.export');

    Route::get('reports/schedules', [ReportController::class, 'schedules'])->name('reports.schedules');
    Route::get('reports/schedules/export', [ReportController::class, 'exportSchedules'])->name('reports.schedules.export');

    Route::get('reports/hours', [ReportController::class, 'hours'])->name('reports.hours');
    Route::get('reports/hours/export', [ReportController::class, 'exportHours'])->name('reports.hours.export');

    Route::get('reports/absences', [ReportController::class, 'absences'])->name('reports.absences');
    Route::get('reports/absences/export', [ReportController::class, 'exportAbsences'])->name('reports.absences.export');

    Route::get('reports/modifications', [ReportController::class, 'modifications'])->name('reports.modifications');
    Route::get('reports/modifications/export', [ReportController::class, 'exportModifications'])->name('reports.modifications.export');

    Route::get('reports/audit', [ReportController::class, 'audit'])->name('reports.audit');
    Route::get('reports/audit/export', [ReportController::class, 'exportAudit'])->name('reports.audit.export');
});
