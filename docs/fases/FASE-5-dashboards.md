# FASE 5 — Dashboards

- **Fecha:** 2026-09-08
- **Estado:** ✅ Completada

## 1. Objetivo

Sustituir el panel provisional por un **dashboard específico por rol**
(estudiante, docente, coordinación, administración) con los indicadores ya
disponibles de las fases 1–4. Sin cambios de esquema.

## 2. Decisión de arquitectura

- **ADR-0008** — Dashboards por rol: una ruta `dashboard`, el controlador deriva
  según rol prioritario (`admin > coordinator > teacher > student`).

## 3. Archivos creados

```
app/Services/Analytics/DashboardService.php
resources/views/admin/dashboard/index.blade.php
resources/views/teacher/dashboard/index.blade.php
resources/views/student/dashboard/index.blade.php
resources/views/coordinator/dashboard/index.blade.php
resources/views/components/dashboard/stat-card.blade.php
resources/css/components/dashboard.css
tests/Feature/Dashboard/DashboardTest.php
docs/architecture/ADR-0008-dashboards-por-rol.md
docs/fases/FASE-5-dashboards.md
```

## 4. Archivos modificados

```
app/Http/Controllers/DashboardController.php   # deriva a la vista por rol vía DashboardService
resources/views/dashboard.blade.php            # ahora solo reserva (usuario sin rol)
resources/css/app.css                          # import dashboard.css
```

## 5. Contenido de cada panel

| Rol | Tarjetas | Paneles |
|---|---|---|
| **Estudiante** | Cursos · Progreso global · Promedio · Pendientes/Vencidas | Próximas entregas · Progreso por curso · Actividad reciente |
| **Docente** | Asignaturas · Estudiantes · Actividades publicadas · Intentos por revisar | Pendiente de calificar · Mis asignaturas · Actividad reciente en sus asignaturas |
| **Coordinación** | Cursos (activos) · Asignaturas · Estudiantes inscritos · Docentes con asignatura | Cursos activos (tabla) · Asignaturas sin docente · Actividad reciente |
| **Administración** | Usuarios (activos/suspendidos) · Roles · Cursos · Asignaturas · Inscripciones · Actividades · Calificaciones | Usuarios por rol · Últimos usuarios · Actividad reciente del sistema |

Datos: `DashboardService` reutiliza `StudentAnalyticsService` / `ProgressCalculator`
(Fase 4) y añade consultas acotadas (`limit`, `withCount`, `whereDoesntHave`).

## 6. Cómo probar

### Automático
```bash
php artisan test            # 90 pruebas (83 previas + 7 de Fase 5)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
Entrar con cada usuario demo (`password`) y abrir *Panel principal*:

- `admin@diskover.test` → «Panel de administración» con contadores del sistema.
- `coordinacion@diskover.test` → «Panel de coordinación», tabla de cursos activos
  y lista de asignaturas sin docente.
- `docente@diskover.test` → tarjetas de sus asignaturas y bloque «Pendiente de
  calificar» (si el estudiante demo ha enviado un intento con pregunta abierta).
- `estudiante@diskover.test` → «Próximas entregas», barras de progreso por curso
  e historial reciente.

Un usuario sin rol ve la ficha de cuenta con el aviso de «Sin roles asignados».

## 7. Resultado esperado

Cada rol aterriza en un panel propio y accionable; los enlaces llevan a las
secciones ya existentes (progreso, revisión de intentos, gestión de cursos…).
`php artisan test` → 90/90.

## 8. Checklist

- [x] Backend — agregación en `DashboardService`; controlador delgado con `match` por rol
- [x] Base de datos — sin cambios (solo lecturas)
- [x] Frontend — `x-dashboard.stat-card`, rejillas `dashboard-grid` / `dashboard-cols`; CSS modular
- [x] Validaciones — N/A
- [x] Seguridad — la ruta ya está tras `auth` + `active`; cada panel solo consulta lo que el rol puede ver
- [x] Responsive — rejillas con `auto-fit`; tablas con scroll
- [x] Pruebas — 7 nuevas (enrutado por rol, prioridad, paneles clave, reserva sin rol)
- [x] Organización — vistas en `resources/views/<rol>/dashboard/`; conforme a ADR-0008

## 9. Notas para la siguiente fase

- **Fase 6 (Learning Analytics)** añadirá gráficas (evolución, rendimiento por
  tema, identificación de dificultades) y podrá enriquecer estos paneles con
  visualizaciones; la agregación puede pasar a cachearse.
