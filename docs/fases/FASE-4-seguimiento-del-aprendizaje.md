# FASE 4 — Seguimiento del aprendizaje

- **Fecha:** 2026-09-08
- **Estado:** ✅ Completada

## 1. Objetivo

Convertir los datos académicos (inscripciones, contenidos, actividades, notas,
intentos) en seguimiento: progreso por curso/asignatura, compleción de contenidos,
registro de eventos de aprendizaje (historial) y perfil académico del estudiante.
Vistas de progreso también para docente (su asignatura) y coordinación (su curso).

## 2. Decisión de arquitectura

- **ADR-0007** — Seguimiento del aprendizaje: `content_user`, `learning_events`,
  eventos de dominio → `LearningEventSubscriber`, `ProgressCalculator`.

## 3. Archivos creados

### Base de datos
```
database/migrations/2026_09_08_130001_create_content_user_table.php
database/migrations/2026_09_08_130002_create_learning_events_table.php
database/factories/LearningEventFactory.php
```

### Dominio
```
app/Enums/LearningEventType.php
app/Models/LearningEvent.php
app/Events/Academic/{StudentEnrolled,ContentCompleted,AttemptSubmitted,ActivityGraded}.php
app/Listeners/LearningEventSubscriber.php
app/Services/Analytics/LearningEventRecorder.php
app/Services/Analytics/ProgressCalculator.php
app/Services/Analytics/StudentAnalyticsService.php
app/Services/Analytics/CourseAnalyticsService.php
```

### HTTP
```
app/Http/Controllers/Student/ProfileController.php
app/Http/Controllers/Student/ProgressController.php
app/Http/Controllers/Student/ContentController.php
app/Http/Controllers/Teacher/ProgressController.php
app/Http/Controllers/Coordinator/ProgressController.php
```

### Vistas y componentes
```
resources/views/components/ui/progress.blade.php
resources/views/components/analytics/{progress-summary,timeline}.blade.php
resources/views/student/profile/show.blade.php
resources/views/student/progress/show.blade.php
resources/views/teacher/progress/show.blade.php
resources/views/coordinator/progress/show.blade.php
resources/css/components/analytics.css
```

### Pruebas
```
tests/Feature/Tracking/ProgressTest.php
tests/Feature/Tracking/LearningEventTest.php
tests/Feature/Tracking/ContentCompletionTest.php
tests/Feature/Tracking/ProgressAccessTest.php
```

### Documentación
```
docs/architecture/ADR-0007-seguimiento-del-aprendizaje.md
docs/fases/FASE-4-seguimiento-del-aprendizaje.md
```

## 4. Archivos modificados

```
app/Models/User.php                     # completedContents(), learningEvents()
app/Models/Content.php                  # completedBy(), isCompletedBy()
app/Providers/AppServiceProvider.php    # Event::subscribe(LearningEventSubscriber)
app/Services/Academic/EnrollmentService.php  # dispara StudentEnrolled (alta nueva)
app/Services/Academic/AttemptService.php     # dispara AttemptSubmitted
app/Services/Academic/GradeService.php       # dispara ActivityGraded (manual y auto)
app/Http/Controllers/Student/CourseController.php   # progreso por curso en index/show
app/Http/Controllers/Student/SubjectController.php  # progreso + contenidos completados
routes/web.php                          # rutas de progreso (student/teacher/coordinator)
                                        #   y student.contents.complete, student.profile
database/seeders/DemoAcademicSeeder.php # contenido completado + nota manual + eventos demo
resources/views/components/navigation/sidebar.blade.php   # "Mi progreso" (estudiante)
resources/views/dashboard.blade.php     # acceso rápido "Mi progreso"
resources/views/student/{courses/index,courses/show,subjects/show}.blade.php  # barras de progreso, toggle de contenido
resources/views/teacher/subjects/{index,show}.blade.php   # enlace "Progreso"
resources/views/coordinator/courses/{index,show}.blade.php # enlace "Progreso"
resources/css/app.css                   # import analytics.css
```

## 5. Rutas nuevas

```
GET  student/profile                          student.profile.show
GET  student/courses/{course}/progress        student.courses.progress
POST student/contents/{content}/complete      student.contents.complete  (toggle)
GET  teacher/subjects/{subject}/progress      teacher.subjects.progress
GET  coordinator/courses/{course}/progress    coordinator.courses.progress
```

## 6. Modelo de progreso

- **Ítem** = contenido publicado o actividad publicada de una asignatura.
- **Ítem hecho** = contenido marcado como completado por el estudiante, o
  actividad con `Grade`.
- `progreso % = hechos / total × 100`. `promedio %` = media de
  `nota / max_score × 100` de las actividades calificadas.
- `pendientes` / `vencidas` = actividades sin nota según `due_at`.
- Curso = agregación de asignaturas activas (promedio ponderado por nº de notas).

## 7. Registro de aprendizaje

`learning_events` se alimenta desde eventos de dominio disparados por los
servicios y traducidos por `LearningEventSubscriber`:

| Acción | Evento | Tipo |
|---|---|---|
| Inscripción (alta nueva) | `StudentEnrolled` | `enrolled` |
| Marcar contenido completado | `ContentCompleted` | `content_completed` |
| Enviar intento de evaluación | `AttemptSubmitted` | `attempt_submitted` |
| Calificación registrada (manual o automática) | `ActivityGraded` | `activity_graded` |

El perfil del estudiante muestra los últimos 40 eventos como línea de tiempo.

## 8. Cómo probar

### Automático
```bash
php artisan test            # 83 pruebas (68 previas + 15 de Fase 4)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
El seeder deja al estudiante demo con 1 contenido completado, 1 nota (82/100) y
3 eventos de aprendizaje.

1. **Estudiante** (`estudiante@diskover.test`): *Mi progreso* → perfil con
   progreso global, promedio, pendientes/vencidas, tarjeta por curso e historial.
   *Mis cursos* muestra la barra de progreso; entrar en una asignatura permite
   *Marcar como completado* cada contenido (aparece en el historial).
2. **Docente** (`docente@diskover.test`): *Mis asignaturas → Progreso* → tabla de
   estudiantes con barra, contenidos, actividades, promedio y pendientes. Un
   docente ajeno recibe **403**.
3. **Coordinación** (`coordinacion@diskover.test`): *Cursos → Progreso* → tarjetas
   de resumen + tabla por estudiante.

## 9. Resultado esperado

- El estudiante ve su avance real (contenidos + actividades), su promedio y su
  historial. Docente y coordinación ven el mismo cálculo agregado.
- Cada inscripción, contenido completado, envío y calificación deja un evento.
- `php artisan test` → 83/83.

## 10. Checklist

- [x] Backend — cálculo en `Services/Analytics`; eventos de dominio + subscriber
- [x] Base de datos — `content_user` (PK compuesta) y `learning_events` (índices por usuario/curso/fecha)
- [x] Frontend — `x-ui.progress`, `x-analytics.progress-summary`, `x-analytics.timeline`; CSS modular
- [x] Validaciones — N/A (solo lecturas y un toggle); acceso comprobado en backend
- [x] Seguridad — estudiante solo ve lo suyo; docente/coordinación por policy de asignatura/curso
- [x] Responsive — barras y tablas con scroll; rejilla de estadísticas adaptable
- [x] Pruebas — 15 nuevas (progreso, eventos, compleción, acceso)
- [x] Organización — conforme a ADR-0002/0007; documentación al día

## 11. Notas para la siguiente fase

- **Fase 5 (dashboards)** reutilizará `StudentAnalyticsService` / `CourseAnalyticsService`
  para los paneles por rol (hoy `dashboard.blade.php` es provisional).
- **Fase 6 (analytics)** añadirá gráficas y probablemente una tabla `progress`
  materializada con invalidación desde `LearningEventSubscriber`.
- Pendiente: métricas de dedicación/tiempo y "% de lectura" de contenidos.
