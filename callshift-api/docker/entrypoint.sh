#!/bin/sh
set -e

echo "=== Iniciando CallShift HR API Container ==="

# Crear carpetas requeridas si no existen
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/storage/app/private/evidences \
         /var/www/html/bootstrap/cache

# Ajustar permisos para www-data
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Enlace simbólico de storage
php artisan storage:link --force || true

# Si DB_HOST está definido, esperar disponibilidad de la base de datos
if [ -n "$DB_HOST" ]; then
    echo "Esperando conexión a la base de datos en $DB_HOST:${DB_PORT:-5432}..."
    timeout=60
    while ! nc -z "$DB_HOST" "${DB_PORT:-5432}"; do
        timeout=$((timeout - 1))
        if [ "$timeout" -le 0 ]; then
            echo "ADVERTENCIA: Timeout esperando conexión a la base de datos. Continuando..."
            break
        fi
        sleep 1
    done
    echo "Base de datos disponible."
fi

# Ejecutar migraciones automáticas si AUTO_MIGRATE=true
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Ejecutando migraciones de base de datos..."
    php artisan migrate --force --isolated || true
fi

# Caché de configuración en entorno de producción
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizando caches de Laravel para producción..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "=== CallShift HR API inicializada exitosamente ==="

exec "$@"
