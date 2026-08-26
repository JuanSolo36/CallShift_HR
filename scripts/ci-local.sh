#!/usr/bin/env bash
# ==============================================================================
# CALLSHIFT HR — SCRIPT DE SIMULACIÓN LOCAL DE CI (BASH)
# ==============================================================================

set -e

echo -e "\033[1;36m======================================================\033[0m"
echo -e "\033[1;36m  CALLSHIFT HR — LOCAL CI PIPELINE SIMULATION (BASH)  \033[0m"
echo -e "\033[1;36m======================================================\033[0m"

# 1. Backend Testing
echo -e "\n\033[1;33m[1/3] Ejecutando Backend Tests (PHP 8.3 & Artisan)...\033[0m"
cd callshift-api
php artisan test
cd ..
echo -e "\033[1;32m✓ Backend tests completados exitosamente.\033[0m"

# 2. Frontend Testing
echo -e "\n\033[1;33m[2/3] Ejecutando Frontend Test Battery (7 Suites)...\033[0m"
cd callshift-client
npx tsx src/test/frontend.test.ts
npx tsx src/test/scheduleEditor.test.ts
npx tsx src/test/conflicts.test.ts
npx tsx src/test/modifications.test.ts
npx tsx src/test/audit.test.ts
npx tsx src/test/reports.test.ts
npx tsx src/test/systemIntegration.test.ts
echo -e "\033[1;32m✓ Frontend tests completados exitosamente.\033[0m"

# 3. TypeScript Typecheck & Production Build
echo -e "\n\033[1;33m[3/3] Ejecutando Typecheck y Build de Producción (tsc -b && vite build)...\033[0m"
npm run build
cd ..
echo -e "\033[1;32m✓ Frontend build completado exitosamente.\033[0m"

echo -e "\n\033[1;32m======================================================\033[0m"
echo -e "\033[1;32m  LOCAL CI VALIDATION PASSED (100% READY FOR PUSH)    \033[0m"
echo -e "\033[1;32m======================================================\033[0m"
