# CALLSHIFT HR — GUÍA DE DESPLIEGUE CON DOCKER & CONTAINERIZACIÓN

Guía completa para la inicialización, orquestación, administración y monitoreo de CallShift HR mediante contenedores Docker y Docker Compose.

---

## 1. Arquitectura de Contenedores

La plataforma se despliega como una infraestructura multi-servicio totalmente aislada y conectada por la red puente `callshift-network`:

```
                           +------------------------+
                           |  Host Browser / Client |
                           +-----------+------------+
                                       |
                 +---------------------+---------------------+
                 | Port 3000                                 | Port 8000
                 v                                           v
       +--------------------+                      +--------------------+
       |  callshift-client  |                      |   callshift-api    |
       |  (React 18 + Vite  |  Reverse Proxy /api  | (Laravel 11 + PHP  |
       |   Nginx Alpine)    | -------------------> |  8.3 FPM + Nginx)  |
       +--------------------+                      +---------+----------+
                                                             |
                                           +-----------------+-----------------+
                                           |                                   |
                                           v                                   v
                                 +--------------------+             +--------------------+
                                 | callshift-postgres |             |  callshift-redis   |
                                 |  (PostgreSQL 16)   |             |     (Redis 7)      |
                                 +--------------------+             +--------------------+
```

---

## 2. Requisitos Previos

- Docker Engine `>= 24.0.0`
- Docker Compose v2 `>= 2.20.0`
- Puertos libres en el host: `3000` (Frontend), `8000` (API), `5432` (PostgreSQL), `6379` (Redis), `8025` (Mailpit UI).

---

## 3. Inicio Rápido (Quick Start)

### Paso 1: Configurar variables de entorno
```bash
cp .env.docker.example .env
```

### Paso 2: Construir e iniciar contenedores
```bash
docker compose up -d --build
```

### Paso 3: Verificar estado de salud (Healthchecks)
```bash
docker compose ps
```

### Paso 4: Acceder a los servicios
- **Frontend Web SPA**: [http://localhost:3000](http://localhost:3000)
- **Backend API REST**: [http://localhost:8000/api/v1/health](http://localhost:8000/api/v1/health)
- **Mailpit Web UI**: [http://localhost:8025](http://localhost:8025)

---

## 4. Comandos de Operación y Mantenimiento

### Ejecutar Migraciones y Seeds
```bash
docker compose exec api php artisan migrate --force
docker compose exec api php artisan db:seed --force
```

### Ejecutar Pruebas Automatizadas dentro del Contenedor
```bash
docker compose exec api php artisan test
```

### Ver Logs en Tiempo Real
```bash
# Todos los servicios
docker compose logs -f

# Solo API Backend
docker compose logs -f api

# Solo Base de Datos
docker compose logs -f postgres
```

### Detener los Contenedores
```bash
# Detener sin eliminar volúmenes
docker compose down

# Detener eliminando volúmenes de datos
docker compose down -v
```

---

## 5. Despliegue en Producción

Para entornos de producción, utilizar el archivo de overlay con límites de recursos:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```
