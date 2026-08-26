# ==============================================================================
# CALLSHIFT HR CLIENT — MULTI-STAGE DOCKERFILE
# ==============================================================================

# STAGE 1: Builder
FROM node:20-alpine AS builder

WORKDIR /app

# Instalar dependencias con caché de capas
COPY package.json package-lock.json ./
RUN npm ci

# Copiar código fuente
COPY . ./

# Build de producción optimizado
ENV NODE_ENV=production
RUN npm run build

# ==============================================================================
# STAGE 2: Production Nginx Server
# ==============================================================================
FROM nginx:1.27-alpine AS production

LABEL maintainer="CallShift HR Architecture Team"
LABEL description="CallShift HR Enterprise Web Application"

# Copiar configuración optimizada de Nginx para SPA
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Copiar bundle estático generado
COPY --from=builder /app/dist /usr/share/nginx/html

# Puerto expuesto
EXPOSE 80

# Healthcheck nativo
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget -qO- http://localhost/health || exit 1

CMD ["nginx", "-g", "daemon off;"]
