# CALLSHIFT HR — GUÍA DE CI/CD & AUTOMATIZACIÓN DE PIPELINES

Manual técnico que describe los flujos de integración continua (CI), entrega continua (CD), matriz de secrets y políticas de despliegue para CallShift HR.

---

## 1. Arquitectura de Pipelines (GitHub Actions)

Los workflows automatizados se encuentran en `.github/workflows/`:

```
  Git Push / PR (feature/*, develop, main)
                   │
                   ▼
       ┌───────────────────────┐
       │   Workflow: ci.yml    │
       └───────────┬───────────┘
                   │
         ┌─────────┴─────────┐
         ▼                   ▼
┌─────────────────┐ ┌─────────────────┐
│ Backend Tests   │ │ Frontend Tests  │
│ (PHP 8.3 + Pg)  │ │ (React + Vite)  │
└────────┬────────┘ └────────┬────────┘
         │                   │
         └─────────┬─────────┘
                   ▼
        ┌─────────────────────┐
        │  Docker Validation  │
        │  & Compose Config   │
        └─────────────────────┘
```

---

## 2. Descripción de Workflows

### A. Integración Continua (`ci.yml`)
- **Triggers**: `push` y `pull_request` a las ramas `main`, `develop` y `release/**`.
- **Jobs**:
  1. `backend`:
     - Provisiona servicios reales de **PostgreSQL 16** y **Redis 7** en el runner de GitHub.
     - Configura PHP 8.3 con extensiones (`pdo_pgsql`, `pdo_mysql`, `pdo_sqlite`, `bcmath`, `intl`, `zip`, `pcntl`, `opcache`, `redis`, `gd`).
     - Ejecuta `composer install` con caché de dependencias.
     - Ejecuta migraciones de base de datos (`php artisan migrate`).
     - Ejecuta suite completa de pruebas backend (`php artisan test`).
  2. `frontend`:
     - Configura Node.js 20 con caché de NPM.
     - Ejecuta `npm ci`.
     - Ejecuta batería completa de 7 suites de pruebas frontend (`npx tsx src/test/*.test.ts`).
     - Ejecuta typecheck TypeScript y compilación de producción (`npm run build`).
  3. `docker`:
     - Valida sintaxis y estructura de `docker-compose.yml` y `docker-compose.prod.yml`.
     - Construye imágenes Docker de Backend y Frontend con caché GitHub Actions.

### B. Despliegue en Staging (`staging.yml`)
- **Triggers**: `push` a `develop` o ejecución manual `workflow_dispatch`.
- **Acciones**:
  - Construye y publica imágenes etiquetadas con `staging` y SHA del commit en GitHub Container Registry (`ghcr.io`).
  - Despliega en ambiente Staging con migraciones automáticas.
  - Verifica salud del endpoint `/api/v1/health`.

### C. Despliegue en Producción (`production.yml`)
- **Triggers**: Creación de tags semánticos `v*.*.*` (ej. `v1.0.0`) o `workflow_dispatch`.
- **Protección**: Requiere aprobación manual en el entorno `production` de GitHub.
- **Acciones**:
  - Genera snapshot de respaldo de base de datos previo al despliegue.
  - Actualización controlada de contenedores (Zero Downtime).
  - Ejecución de migraciones aisladas (`php artisan migrate --force --isolated`).
  - Verificación de salud y smoke tests.

---

## 3. Matriz de Secrets y Variables de Entorno

| Variable / Secret | Propósito | Entorno |
| :--- | :--- | :--- |
| `APP_KEY` | Clave simétrica de cifrado de Laravel | CI / Staging / Prod |
| `DB_DATABASE` | Nombre de la base de datos PostgreSQL | Staging / Prod |
| `DB_USERNAME` | Usuario de base de datos | Staging / Prod |
| `DB_PASSWORD` | Contraseña de base de datos | Staging / Prod |
| `REDIS_PASSWORD` | Contraseña del cluster Redis | Staging / Prod |
| `STAGING_HOST` | Host SSH / IP del servidor Staging | Staging |
| `PROD_HOST` | Host SSH / IP del cluster de Producción | Prod |
| `GITHUB_TOKEN` | Token automático para GHCR | Todos |

---

## 4. Ejecución y Simulación Local

Antes de realizar `git push`, cualquier desarrollador puede ejecutar la verificación completa localmente:

### En Windows (PowerShell):
```powershell
.\scripts\ci-local.ps1
```

### En Linux / macOS (Bash):
```bash
chmod +x ./scripts/ci-local.sh
./scripts/ci-local.sh
```
