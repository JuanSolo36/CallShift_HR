# ==============================================================================
# CALLSHIFT HR API — MULTI-STAGE DOCKERFILE
# ==============================================================================

# STAGE 1: Base Runtime Environment
FROM php:8.3-fpm-alpine AS base

LABEL maintainer="CallShift HR Architecture Team"
LABEL description="CallShift HR Enterprise API Engine"

# Instalar dependencias del sistema y utilitarios
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    postgresql-dev \
    sqlite-dev \
    nginx \
    supervisor \
    netcat-openbsd

# Configurar e instalar extensiones de PHP requeridas
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        intl \
        zip \
        pcntl \
        opcache \
        gd

# Instalar extensión Redis vía PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Copiar Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# ==============================================================================
# STAGE 2: Vendor Builder
# ==============================================================================
FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ==============================================================================
# STAGE 3: Production Target
# ==============================================================================
FROM base AS production

# Copiar configuración personalizada de PHP, Nginx y Supervisor
COPY docker/php.ini /usr/local/etc/php/conf.d/callshift.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copiar código de la aplicación y dependencias de composer
COPY . /var/www/html
COPY --from=vendor /var/www/html/vendor /var/www/html/vendor

# Crear directorios y configurar permisos para www-data
RUN mkdir -p /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/storage/app/public \
             /var/www/html/storage/app/private/evidences \
             /var/www/html/bootstrap/cache \
             /var/log/supervisor \
             /var/run \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Puerto expuesto por Nginx interno
EXPOSE 8000

# Healthcheck nativo (/up es el endpoint oficial de salud en Laravel 12)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8000/up || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
