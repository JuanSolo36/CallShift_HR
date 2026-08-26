# CHANGELOG — CALLSHIFT HR

Todos los cambios notables en la plataforma CallShift HR se documentan en este archivo.

---

## [1.0.0] - 2026-08-23

### Agregado
- **Módulo de Identidad y Roles**: Sistema multi-tenant con RBAC granular, soporte para 6 roles del sistema, autenticación vía Sanctum y rate limiting.
- **Estructura Organizacional**: Gestión de departamentos, cargos, tipos de contratación y jerarquía de supervisores.
- **Catálogo de Turnos y Patrones**: Modelado de turnos rotativos, mixtos y nocturnos con cálculo de cruce de medianoche, patrones cíclicos y plantillas.
- **Matriz de Edición y Planificación**: Editor de celdas con control de concurrencia optimista (`lock_version`), cálculo de horas en tiempo real y asignación masiva.
- **Motor de Conflictos y Reglas Laborales**: Detección de 11 reglas laborales con severidades `HARD` y `SOFT`, bloqueo de publicación ante colisiones severas y resolución justificada.
- **Versionamiento Inmutable de Horarios**: Transiciones `DRAFT` $\to$ `REVIEW` $\to$ `PUBLISHED` $\to$ `ARCHIVED`, unicidad de versión publicada y derivación no destructiva.
- **Modificaciones de Horarios y Evidencias**: Derivación de versiones ante cambios en horarios publicados con custodia criptográfica de archivos adjuntos (SHA-256).
- **Auditoría Forense Inmutable**: Registro de eventos de negocio (`AuditLog`) con enmascaramiento automático de credenciales sensibles.
- **Reportes Analíticos y Exportación**: 6 reportes empresariales con streaming acotado a CSV (`php://temp`).
- **Endurecimiento QA e Integración E2E**: Batería transversal de integración de 8 pruebas backend y 5 flujos frontend pasando al 100%.
- **Optimización de Consultas**: Eliminación de cuellos de botella N+1 en motor de conflictos y caching de React Query.
- **Infraestructura Docker**: Multi-stage Dockerfiles para API y Client, Docker Compose con PostgreSQL 16 y Redis 7.
- **Automatización CI/CD**: Workflows de GitHub Actions para CI, Staging y Producción.
