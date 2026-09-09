# Documentación técnica — DISKOVER Smart Learning Ecosystem (DSLE)

Esta carpeta concentra la documentación viva del proyecto.

## Estructura

| Carpeta | Contenido |
|---|---|
| `architecture/` | Architecture Decision Records (ADR). Una decisión arquitectónica relevante = un ADR numerado e inmutable. |
| `fases/` | Registro de cada fase de desarrollo: objetivo, archivos creados/modificados, cómo probar y checklist. |

## Convenciones

- Los ADR se numeran de forma correlativa (`ADR-0001`, `ADR-0002`, …) y **no se editan** una vez aceptados; si una decisión se revierte, se crea un ADR nuevo que la supersede.
- Cada fase deja su documento `FASE-N-*.md` al terminar.
- Todo módulo importante documenta: objetivo, funcionalidades, dependencias, rutas, modelos, relaciones, servicios, APIs, variables de configuración y pruebas.

## Estado del proyecto

| Fase | Estado |
|---|---|
| Fase 0 — Preparación | ✅ Completada |
| Fase 1 — Autenticación y seguridad | ✅ Completada |
| Fase 2 — Gestión académica | ✅ Completada |
| Fase 3 — Actividades y evaluaciones | ✅ Completada |
| Fase 4 — Seguimiento del aprendizaje | ✅ Completada |
| Fase 5 — Dashboards | ✅ Completada |
| Fase 6 — Learning Analytics | ✅ Completada |
| Fase 7 — Inteligencia artificial | ✅ Completada |
| Fases 8–13 | ⏳ Pendientes |

## ADR

| ADR | Título |
|---|---|
| [0001](architecture/ADR-0001-monolito-modular.md) | Arquitectura de monolito modular |
| [0002](architecture/ADR-0002-estructura-de-carpetas-modular.md) | Estructura de carpetas modular |
| [0003](architecture/ADR-0003-rbac-propio.md) | RBAC propio (roles + permisos) |
| [0004](architecture/ADR-0004-css-modular-sin-framework-utilidades.md) | CSS modular sin framework de utilidades |
| [0005](architecture/ADR-0005-modelo-de-dominio-academico.md) | Modelo de dominio académico |
| [0006](architecture/ADR-0006-modelo-de-evaluacion-y-calificacion.md) | Modelo de evaluación y motor de calificación |
| [0007](architecture/ADR-0007-seguimiento-del-aprendizaje.md) | Seguimiento del aprendizaje (progreso, eventos, perfil) |
| [0008](architecture/ADR-0008-dashboards-por-rol.md) | Dashboards por rol |
| [0009](architecture/ADR-0009-learning-analytics-y-graficas.md) | Learning Analytics y gráficas propias |
| [0010](architecture/ADR-0010-integracion-de-ia.md) | Integración de IA (asistente educativo) |
