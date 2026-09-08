# ADR-0008 — Dashboards por rol

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 5
- **Relacionado con:** ADR-0003, ADR-0007

## Contexto

Tras el inicio de sesión todos los usuarios aterrizaban en un panel provisional.
La Fase 5 pide un dashboard por rol (estudiante, docente, coordinación,
administración) con los indicadores ya disponibles de fases anteriores.

## Decisión

- **Una sola ruta** `dashboard` (`DashboardController@index`). El controlador
  resuelve el **rol prioritario** del usuario y devuelve la vista correspondiente:

  `admin > coordinator > teacher > student` (primer rol que el usuario posea).
  Sin rol → vista de reserva `dashboard.blade.php` con aviso.

- **Vistas** en `resources/views/{admin,teacher,student,coordinator}/dashboard/index.blade.php`,
  conforme a la estructura de carpetas de la sección 5 del prompt maestro.

- **Agregación** en `App\Services\Analytics\DashboardService`, con un método por
  rol (`forStudent`, `forTeacher`, `forCoordinator`, `forAdmin`) que reutiliza
  `StudentAnalyticsService` / `ProgressCalculator` (ADR-0007) y añade las
  consultas propias de cada panel (próximas entregas, intentos por revisar,
  asignaturas sin docente, usuarios por rol…).

- **Componentes**: `x-dashboard.stat-card` (icono + etiqueta + valor + pista) y
  reutilización de `x-ui.progress`, `x-analytics.timeline`.

## Consecuencias

- No hay rutas nuevas ni cambios de esquema: la Fase 5 es solo presentación y
  agregación.
- El panel del docente calcula `pending_review` y contadores por asignatura; para
  un docente con muchas asignaturas son consultas acotadas (`limit`, `withCount`),
  no el cálculo completo de progreso por estudiante (ese vive en la página de
  progreso de la asignatura).
- Un usuario con varios roles ve **un** panel (el prioritario); si en el futuro
  se necesita alternar, se añadirá un selector sin cambiar el enrutado.
- El coordinador ve el panel de coordinación; para inspeccionar el de un
  estudiante concreto usará las páginas de progreso, no el dashboard.

## Alternativas descartadas

- **Una ruta por rol** (`/admin/dashboard`, …): multiplica enlaces y middleware
  sin beneficio; el enrutado por rol prioritario es transparente para el sidebar.
- **Un único dashboard con secciones condicionales**: mezcla responsabilidades y
  crece sin control; peor que cuatro vistas pequeñas y enfocadas.
