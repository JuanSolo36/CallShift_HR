# 📅 CallShift HR

**Sistema web para la gestión, planificación y administración de turnos laborales.**

CallShift HR es una plataforma diseñada para facilitar la administración de horarios y turnos de empleados, permitiendo a las organizaciones centralizar la planificación laboral, gestionar colaboradores y mantener un control organizado sobre las asignaciones de trabajo.

El sistema está diseñado bajo un enfoque **multiempresa (Multi-Tenant)**, permitiendo que diferentes organizaciones utilicen la plataforma manteniendo sus datos aislados y protegidos.

---

## 🚀 Características principales

### 👥 Gestión de empleados

Permite administrar la información de los colaboradores de cada organización:

* Registro de empleados.
* Actualización de información.
* Activación y desactivación de empleados.
* Consulta y búsqueda de colaboradores.
* Asociación de empleados con una empresa.
* Gestión de información relacionada con los horarios.

### 🏢 Gestión multiempresa

CallShift HR está diseñado para soportar múltiples organizaciones dentro de la misma plataforma.

Cada empresa mantiene sus propios:

* Empleados.
* Turnos.
* Horarios.
* Períodos de trabajo.
* Asignaciones.
* Configuraciones.

El sistema implementa controles de autorización para evitar que usuarios de una empresa puedan acceder a información perteneciente a otra organización.

### 🕐 Gestión de turnos

Permite definir diferentes tipos de turnos de trabajo, incluyendo:

* Nombre del turno.
* Hora de inicio.
* Hora de finalización.
* Duración.
* Configuración del turno.
* Estado.

Esto permite reutilizar tipos de turnos al momento de construir los horarios.

### 📆 Planificación de horarios

La aplicación permite crear y administrar horarios laborales para los empleados.

Entre sus funcionalidades se contempla:

* Asignación de turnos.
* Visualización de horarios.
* Organización por períodos.
* Gestión de asignaciones.
* Modificación de horarios.
* Control de versiones.

### 🔄 Versionado de horarios

Los horarios pueden manejarse mediante versiones para conservar la trazabilidad de los cambios realizados.

Esto permite reducir problemas relacionados con modificaciones accidentales y facilita la recuperación o consulta de versiones anteriores.

### 🔐 Roles y permisos

CallShift HR contempla un sistema de autorización basado en roles y permisos.

Los permisos determinan qué funcionalidades puede utilizar cada usuario, evitando que un usuario con privilegios limitados pueda ejecutar operaciones administrativas.

### 📊 Administración centralizada

La plataforma busca centralizar la gestión de la información relacionada con empleados y horarios, reduciendo procesos manuales y facilitando la toma de decisiones administrativas.

---

# 🏗️ Arquitectura

CallShift HR utiliza una arquitectura desacoplada basada en un frontend y un backend independientes.

```text
┌─────────────────────────────┐
│        CallShift HR         │
│         Frontend            │
│      React + TypeScript     │
└──────────────┬──────────────┘
               │
               │ REST API
               ▼
┌─────────────────────────────┐
│          Backend            │
│       Laravel + PHP         │
│                             │
│ Authentication              │
│ Authorization               │
│ Business Logic              │
│ Validation                  │
│ Services / Actions          │
└──────────────┬──────────────┘
               │
               │ SQL
               ▼
┌─────────────────────────────┐
│          MySQL              │
│                             │
│ Companies                   │
│ Employees                   │
│ Shifts                      │
│ Work Periods                │
│ Assignments                 │
│ Users / Roles / Permissions │
└─────────────────────────────┘
```

---

# 💻 Tecnologías utilizadas

## Frontend

* **React**
* **TypeScript**
* **Vite**
* **Tailwind CSS**
* **TanStack Query**
* **React Hook Form**
* **Zod**

## Backend

* **PHP 8.3+**
* **Laravel 12+**
* **Laravel Eloquent**
* **REST API**
* **MySQL**

## Herramientas de desarrollo

* Git
* GitHub
* Visual Studio Code
* Postman
* npm
* Composer

---

# 📂 Estructura del proyecto

La estructura general del proyecto está organizada de manera separada entre frontend y backend:

```text
CallShift_HR/
│
├── frontend/
│   ├── src/
│   ├── public/
│   ├── package.json
│   └── vite.config.ts
│
├── backend/
│   ├── app/
│   ├── routes/
│   ├── database/
│   ├── config/
│   ├── resources/
│   ├── storage/
│   ├── tests/
│   ├── composer.json
│   └── artisan
│
├── .gitignore
└── README.md
```

> La estructura puede variar dependiendo de la versión actual del proyecto.

---

# 🔐 Seguridad

La seguridad es uno de los aspectos principales considerados en el desarrollo de CallShift HR.

El sistema contempla controles orientados a prevenir:

* Accesos no autorizados.
* Escalada de privilegios.
* Acceso entre diferentes empresas.
* IDOR (Insecure Direct Object References).
* Inyección SQL.
* Cross-Site Scripting (XSS).
* Ataques de fuerza bruta.
* Exposición de información sensible.
* Dependencias vulnerables.
* Configuraciones inseguras de API.

## Controles de seguridad

Entre las medidas consideradas se encuentran:

* Autenticación de usuarios.
* Autorización basada en roles y permisos.
* Middleware de protección.
* Validación de datos.
* Políticas de Laravel.
* Consultas parametrizadas.
* Rate limiting.
* Validación de pertenencia de recursos.
* Protección de variables de entorno.
* Gestión segura de credenciales.
* Auditoría de acciones críticas.

---

# 🧪 Pruebas de seguridad

Como parte del proceso de control de calidad se contemplan diferentes pruebas de seguridad.

### Autenticación

Se verifican:

* Inicio de sesión.
* Credenciales incorrectas.
* Tokens inválidos.
* Tokens expirados.
* Acceso a rutas protegidas.
* Cierre de sesión.

### Autorización

Se verifica que los usuarios solamente puedan ejecutar las acciones permitidas por su rol.

### Aislamiento multiempresa

Se realizan pruebas para verificar que un usuario perteneciente a una empresa no pueda consultar, modificar o eliminar información de otra organización.

### IDOR

Se prueban modificaciones de identificadores en las solicitudes HTTP para comprobar que el backend valide la propiedad del recurso.

### Inyección SQL

Se analizan los puntos de entrada de datos para garantizar que las consultas a la base de datos sean seguras.

### XSS

Se prueban campos de entrada para comprobar que contenido malicioso no pueda ejecutarse dentro de la aplicación.

### API

Se realizan pruebas sobre los endpoints REST para comprobar:

* Autenticación.
* Autorización.
* Validación.
* Métodos HTTP.
* Códigos de respuesta.
* Manejo de errores.
* Exposición de información.

---

# ⚡ Rendimiento

CallShift HR está diseñado considerando escenarios con una cantidad considerable de empleados y asignaciones de horarios.

Entre las estrategias de optimización consideradas se encuentran:

* Consultas optimizadas.
* Índices de base de datos.
* Eager Loading.
* Paginación.
* Caché.
* Reducción de consultas innecesarias.
* Procesamiento eficiente de información.
* Optimización de tablas con grandes cantidades de registros.

La aplicación busca mantener tiempos de respuesta adecuados incluso cuando aumenta el volumen de empleados y asignaciones.

---

# 🔄 Consistencia de datos

La gestión de horarios requiere garantizar que las operaciones sean consistentes.

Para esto se utilizan mecanismos como:

* Transacciones de base de datos.
* Validación de reglas de negocio.
* Restricciones de integridad.
* Control de concurrencia.
* Versionado de horarios.

Las operaciones críticas deben ejecutarse de manera atómica para evitar estados inconsistentes.

---

# 🧩 Principios de desarrollo

El proyecto busca aplicar buenas prácticas de ingeniería de software, incluyendo:

* Separación de responsabilidades.
* Código mantenible.
* Reutilización de componentes.
* Validación de datos.
* Principios SOLID.
* Arquitectura desacoplada.
* Servicios y acciones para la lógica de negocio.
* Manejo adecuado de errores.
* Control de versiones.
* Pruebas automatizadas.

---

# 🌐 API REST

El backend expone una API REST utilizada por el frontend para interactuar con la información del sistema.

Ejemplos conceptuales de endpoints:

```text
/api/auth/login
/api/auth/logout

/api/companies
/api/employees

/api/shift-types
/api/work-periods

/api/schedules
/api/schedule-assignments
```

Los endpoints protegidos requieren autenticación y validación de permisos.

---

# 📋 Modelo funcional

El flujo principal de trabajo puede representarse de la siguiente manera:

```text
Empresa
   │
   ├── Usuarios
   │      │
   │      └── Roles y permisos
   │
   ├── Empleados
   │
   ├── Tipos de turno
   │
   ├── Períodos de trabajo
   │
   └── Horarios
           │
           └── Asignaciones
                  │
                  └── Empleado
```

---

# 🛠️ Instalación

## Requisitos

Antes de instalar el proyecto se recomienda contar con:

* PHP 8.3 o superior.
* Composer.
* Node.js.
* npm.
* MySQL.
* Git.

---

## Clonar el repositorio

```bash
git clone https://github.com/JuanSolo36/CallShift_HR.git
```

Entrar al proyecto:

```bash
cd CallShift_HR
```

---

# ⚙️ Configuración del Backend

Entrar a la carpeta correspondiente:

```bash
cd backend
```

Instalar dependencias:

```bash
composer install
```

Copiar el archivo de configuración:

```bash
cp .env.example .env
```

En Windows PowerShell también puede utilizarse:

```powershell
Copy-Item .env.example .env
```

Generar la clave de Laravel:

```bash
php artisan key:generate
```

Configurar en `.env` los datos de conexión a MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=callshift
DB_USERNAME=root
DB_PASSWORD=
```

Ejecutar migraciones:

```bash
php artisan migrate
```

Si existen seeders:

```bash
php artisan db:seed
```

Iniciar el servidor:

```bash
php artisan serve
```

---

# 🎨 Configuración del Frontend

Entrar a la carpeta:

```bash
cd frontend
```

Instalar dependencias:

```bash
npm install
```

Ejecutar el servidor de desarrollo:

```bash
npm run dev
```

El frontend estará disponible en la dirección indicada por Vite en la terminal.

---

# 🧪 Ejecución de pruebas

Backend:

```bash
php artisan test
```

Frontend:

```bash
npm run lint
```

Análisis de dependencias:

```bash
composer audit
```

```bash
npm audit
```

---

# 🔒 Variables de entorno

Las variables de entorno contienen información que no debe publicarse en el repositorio.

Nunca se deben subir al repositorio:

```text
.env
```

Ni credenciales como:

```text
DB_PASSWORD
API_KEYS
APP_SECRET
ACCESS_TOKEN
```

El archivo `.env.example` debe utilizarse como plantilla para configurar el entorno local.

---

# 📈 Objetivos del proyecto

CallShift HR tiene como objetivos principales:

1. Facilitar la planificación de horarios.
2. Reducir errores en la asignación de turnos.
3. Centralizar la información laboral.
4. Mejorar la gestión de empleados.
5. Garantizar aislamiento entre organizaciones.
6. Proporcionar controles de acceso adecuados.
7. Mantener la integridad de los datos.
8. Proporcionar una arquitectura escalable.
9. Mejorar el rendimiento de las consultas.
10. Facilitar el mantenimiento y evolución del sistema.

---

# 🎓 Contexto académico

CallShift HR también se utiliza como proyecto de estudio dentro del proceso de formación en **Ingeniería de Software**, permitiendo aplicar conceptos relacionados con:

* Ingeniería de software.
* Bases de datos.
* Desarrollo web.
* Arquitectura de software.
* APIs REST.
* Seguridad informática.
* Control de calidad.
* Gestión de riesgos.
* Pruebas de software.
* DevOps.
* Control de versiones.

El proyecto permite aplicar conceptos teóricos sobre un sistema práctico y evaluar aspectos relacionados con seguridad, rendimiento, fiabilidad y mantenibilidad.

---

# 🗺️ Gestión de riesgos

Dentro del proceso de control de calidad se identifican riesgos asociados principalmente a:

| Riesgo                     | Prioridad  |
| -------------------------- | ---------- |
| Acceso entre empresas      | 🔴 Crítico |
| Escalada de privilegios    | 🟠 Alto    |
| IDOR                       | 🟠 Alto    |
| Inyección SQL              | 🟠 Alto    |
| Exposición de datos        | 🟠 Alto    |
| XSS                        | 🟠 Alto    |
| Fuerza bruta               | 🟠 Alto    |
| Dependencias vulnerables   | 🟠 Alto    |
| CORS inseguro              | 🟡 Medio   |
| Falta de auditoría         | 🟡 Medio   |
| Inconsistencia de horarios | 🟠 Alto    |
| Rendimiento                | 🟡 Medio   |

La gestión de estos riesgos forma parte del proceso de mejora continua de la aplicación.

---

# 📚 Estándares y referencias

El desarrollo y evaluación del proyecto toma como referencia buenas prácticas y estándares relacionados con calidad y seguridad:

* **ISO/IEC 25010** — Modelo de calidad de sistemas y software.
* **ISO 31000** — Gestión de riesgos.
* **OWASP Top 10** — Principales riesgos de seguridad en aplicaciones web.
* Buenas prácticas de desarrollo seguro.
* Principios de arquitectura de software.

---

# 🚧 Estado del proyecto

**Estado:** En desarrollo.

Las funcionalidades pueden encontrarse en diferentes niveles de implementación y validación.

### Actualmente se trabaja en:

* Gestión de empleados.
* Gestión de turnos.
* Planificación de horarios.
* Asignaciones.
* Autenticación.
* Autorización.
* Seguridad multiempresa.
* Optimización del rendimiento.
* Pruebas de seguridad.
* Pruebas de calidad.

---

# 🔮 Próximas funcionalidades

Entre las funcionalidades que pueden incorporarse al proyecto se encuentran:

* Dashboard administrativo.
* Reportes de horarios.
* Exportación de información.
* Notificaciones.
* Auditoría avanzada.
* Métricas de utilización.
* Pruebas automatizadas adicionales.
* Monitoreo de rendimiento.
* Integración con servicios externos.

---

# 👨‍💻 Autor

**Juan Bahos Lugo**

Ingeniería de Software
Colombia

---


## ⭐ CallShift HR

**Planifica. Organiza. Gestiona.**

Sistema orientado a mejorar la administración de turnos y horarios mediante una solución web segura, organizada y escalable.
