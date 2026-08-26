# ==============================================================================
# CALLSHIFT HR — SCRIPT DE SIMULACION LOCAL DE CI (POWERSHELL)
# ==============================================================================

$ErrorActionPreference = "Stop"

Write-Host "======================================================" -ForegroundColor Cyan
Write-Host "  CALLSHIFT HR - LOCAL CI PIPELINE SIMULATION" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan

# 1. Backend Testing
Write-Host ""
Write-Host "[1/3] Ejecutando Backend Tests (PHP 8.3 y Artisan)..." -ForegroundColor Yellow
Push-Location "callshift-api"
try {
    $phpExe = "C:\Users\JUANB\.php\php-8.3\php.exe"
    & $phpExe artisan test
    if ($LASTEXITCODE -ne 0) { throw "Backend tests fallaron" }
    Write-Host "Backend tests completados exitosamente (100% PASS)." -ForegroundColor Green
} catch {
    Write-Host "Error en backend tests: $_" -ForegroundColor Red
    Pop-Location
    exit 1
}
Pop-Location

# 2. Frontend Unit & Integration Tests
Write-Host ""
Write-Host "[2/3] Ejecutando Frontend Test Battery (7 Suites)..." -ForegroundColor Yellow
Push-Location "callshift-client"
try {
    cmd /c "npx tsx src/test/frontend.test.ts && npx tsx src/test/scheduleEditor.test.ts && npx tsx src/test/conflicts.test.ts && npx tsx src/test/modifications.test.ts && npx tsx src/test/audit.test.ts && npx tsx src/test/reports.test.ts && npx tsx src/test/systemIntegration.test.ts"
    if ($LASTEXITCODE -ne 0) { throw "Frontend test battery fallo" }
    Write-Host "Frontend tests completados exitosamente (100% PASS)." -ForegroundColor Green

    # 3. TypeScript Typecheck & Production Build
    Write-Host ""
    Write-Host "[3/3] Ejecutando Typecheck y Build de Produccion (tsc -b y vite build)..." -ForegroundColor Yellow
    cmd /c "npm run build"
    if ($LASTEXITCODE -ne 0) { throw "Frontend build fallo" }
    Write-Host "Frontend build completado exitosamente (0 errores de tipado)." -ForegroundColor Green
} catch {
    Write-Host "Error en frontend: $_" -ForegroundColor Red
    Pop-Location
    exit 1
}
Pop-Location

Write-Host ""
Write-Host "======================================================" -ForegroundColor Green
Write-Host "  LOCAL CI VALIDATION PASSED (100% READY FOR PUSH)" -ForegroundColor Green
Write-Host "======================================================" -ForegroundColor Green
