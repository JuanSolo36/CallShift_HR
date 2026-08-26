# CALLSHIFT HR — PRODUCTION RELEASE NOTES (v1.0.0)

**Versión**: `v1.0.0` (Enterprise Golden Master)  
**Fecha de Release**: 2026-08-23  
**Arquitectura**: Multi-tenant, Concurrencia Optimista, Auditoría Inmutable, Docker Multi-stage, CI/CD Automatizado.

---

## 1. Resumen Ejecutivo del Producto

CallShift HR es una solución integral para la gestión empresarial del talento humano, estructuración de turnos y planificación avanzada de horarios con validación automatizada de normativas laborales.

### Módulos Principales Certificados

1. **Gestión de Identidad y Multi-tenancy**:
   - Aislamiento multi-tenant por `company_id`.
   - Autenticación segura mediante Laravel Sanctum con tokens de sesión y hashing Argon2id/Bcrypt.
   - Control de acceso basado en roles (RBAC) con 6 roles estándar (`SUPER_ADMIN`, `HR_ADMIN`, `MANAGER`, `SUPERVISOR`, `EMPLOYEE`, `VIEWER`) y catálogo granular de permisos.
2. **Estructura Organizacional**:
   - Catálogos empresariales de Departamentos, Cargos, Tipos de Contrato y Jerarquías de Supervisión.
3. **Catálogo y Motor de Turnos**:
   - Turnos rotativos, diurnos, mixtos y nocturnos con cálculo exacto de horas y cruce de medianoche (`crosses_midnight`).
   - Patrones recurrentes y plantillas reutilizables.
4. **Matriz de Planificación y Control de Concurrencia**:
   - Editor matricial de turnos con protección optimista de concurrencia mediante `lock_version` (HTTP 409 Conflict).
   - Transiciones de ciclo de vida: `DRAFT` $\to$ `REVIEW` $\to$ `PUBLISHED` $\to$ `ARCHIVED`.
5. **Motor de Reglas Laborales y Detección de Conflictos**:
   - Validación de 11 reglas de negocio críticas (jornada máxima/mínima diaria y semanal, descanso mínimo entre turnos de 12h, límite de 6 días continuos, colisiones con ausencias/permisos, descansos compensatorios y rotación de fines de semana).
   - Bloqueo estricto de publicación ante conflictos severos (`HARD`).
6. **Modificaciones Controladas y Evidencias SHA-256**:
   - Flujo de modificación sobre versiones publicadas derivando un nuevo borrador $V_{n+1}$.
   - Custodia criptográfica de documentos de respaldo con hash SHA-256 y almacenamiento privado aislado.
7. **Auditoría Forense Inmutable**:
   - Trazabilidad empresarial completa (`AuditLog`) registrando actor, IP, acción, payload anterior y nuevo, con enmascaramiento automático de credenciales sensibles (`[REDACTED]`).
8. **Reportes Empresariales y Exportación**:
   - 6 reportes analíticos con filtrado multidimensional, paginación estricta y exportación streaming a CSV en memoria acotada (`php://temp`).
9. **Infraestructura Docker y CI/CD**:
   - Multi-stage Dockerfiles para API (PHP 8.3 FPM + Nginx) y Client (React + Vite + Nginx Alpine).
   - Orquestación con PostgreSQL 16, Redis 7 y Mailpit.
   - Pipelines automatizados en GitHub Actions (`ci.yml`, `staging.yml`, `production.yml`).

---

## 2. Parámetros de Configuración de Producción

| Variable | Valor Recomendado | Descripción |
| :--- | :--- | :--- |
| `APP_ENV` | `production` | Modo de ejecución de Laravel |
| `APP_DEBUG` | `false` | Desactiva trazas de error en respuestas |
| `APP_KEY` | *(Secret seguro de 32 bytes)* | Clave criptográfica para tokens y cookies |
| `DB_CONNECTION` | `pgsql` | Driver PostgreSQL |
| `DB_HOST` | `postgres` | Host de base de datos |
| `DB_PORT` | `5432` | Puerto estándar PostgreSQL |
| `CACHE_STORE` | `redis` | Almacén de caché de alto rendimiento |
| `SESSION_DRIVER` | `redis` | Almacén de sesiones de usuario |
| `QUEUE_CONNECTION` | `redis` | Gestor de colas asíncronas |

---

## 3. Manual de Despliegue en Producción (Go-Live)

### Paso 1: Clonar y Configurar Entorno
```bash
git clone https://github.com/callshift/callshift-hr.git
cd callshift-hr
cp .env.docker.example .env
# Configurar APP_KEY, contraseñas de BD y Redis en .env
```

### Paso 2: Construir e Iniciar Contenedores
```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### Paso 3: Ejecutar Migraciones y Poblado Inicial
```bash
docker compose exec api php artisan migrate --force --isolated
docker compose exec api php artisan db:seed --force
```

### Paso 4: Optimizar Caches de Laravel
```bash
docker compose exec api php artisan config:cache
docker compose exec api php artisan route:cache
docker compose exec api php artisan view:cache
```

### Paso 5: Verificación de Salud
- API Healthcheck: `GET http://localhost:8000/api/v1/health` $\to$ `200 OK`
- Frontend SPA: `GET http://localhost:3000/` $\to$ `200 OK`

---

## 4. Manual de Respaldos, Restauración y Rollback

### A. Respaldo de Base de Datos PostgreSQL
```bash
docker compose exec postgres pg_dump -U callshift_user callshift_hr > backup_callshift_$(date +%Y%m%d_%H%M%S).sql
```

### B. Restauración de Base de Datos
```bash
cat backup_callshift_YYYYMMDD_HHMMSS.sql | docker compose exec -T postgres psql -U callshift_user -d callshift_hr
```

### C. Respaldo de Archivos y Evidencias
```bash
docker run --rm --volumes-from callshift-api -v $(pwd):/backup alpine tar czvf /backup/storage_backup_$(date +%Y%m%d).tar.gz /var/www/html/storage/app
```

### D. Procedimiento de Rollback de Versión
En caso de detectar una anomalía crítica tras un despliegue:
1. Revertir la imagen de contenedor a la versión anterior:
   ```bash
   IMAGE_TAG=v0.9.9 docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
   ```
2. Restaurar el snapshot de base de datos previo si se aplicaron migraciones destructivas.
3. Limpiar caches de aplicación:
   ```bash
   docker compose exec api php artisan optimize:clear
   ```
