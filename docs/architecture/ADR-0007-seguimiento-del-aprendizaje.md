# ADR-0007 — Seguimiento del aprendizaje (progreso, eventos, perfil)

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 4
- **Relacionado con:** ADR-0005, ADR-0006

## Contexto

Con la estructura académica (Fase 2) y las evaluaciones (Fase 3) ya existen los
datos crudos. La Fase 4 los convierte en indicadores de seguimiento: progreso por
curso/asignatura, historial de actividad del estudiante y un perfil académico.
La Fase 6 (Learning Analytics) añadirá gráficas y análisis institucional sobre
esta misma base.

## Decisión

### Datos nuevos

- **`content_user`** (pivote `content_id` + `user_id` + `completed_at`): el
  estudiante marca cada contenido como completado. Es la señal explícita de
  avance en contenidos (no se infiere de "abrir la página").
- **`learning_events`**: histórico pedagógico. Columnas `user_id`, `course_id?`,
  `subject_id?`, `activity_id?`, `type` (enum `LearningEventType`), `description`,
  `payload` (JSON), `occurred_at`. **Distinto** de la auditoría de
  seguridad/administración de la Fase 11.

### Eventos de dominio → registro

`app/Events/Academic/*` se disparan desde los servicios:

| Evento | Se dispara en | Tipo registrado |
|---|---|---|
| `StudentEnrolled` | `EnrollmentService::enroll` (solo alta nueva) | `enrolled` |
| `ContentCompleted` | `Student\ContentController::complete` | `content_completed` |
| `AttemptSubmitted` | `AttemptService::submit` | `attempt_submitted` |
| `ActivityGraded` | `GradeService::setManual` y `syncFromBestAttempt` | `activity_graded` |

`LearningEventSubscriber` (registrado con `Event::subscribe`) los traduce a filas
vía `LearningEventRecorder`, el **único** punto de escritura de `learning_events`.

### Cálculo de progreso

`app/Services/Analytics/ProgressCalculator` (sin estado, sin caché por ahora):

- **Ítems de una asignatura** = contenidos publicados + actividades publicadas.
- **Ítems hechos** = contenidos completados + actividades con `Grade`.
- `percentage = hechos / total * 100` (0 si no hay ítems; sin división por cero).
- `average` = media de `grade.score / activity.max_score * 100` de las
  actividades calificadas.
- `pending` / `overdue` = actividades sin nota, según `due_at`.
- **Curso** = agregación de sus asignaturas **activas**; el promedio del curso
  pondera por número de actividades calificadas de cada asignatura.

`StudentAnalyticsService` (perfil + progreso de un curso + historial) y
`CourseAnalyticsService` (visión por curso para coordinación y por asignatura
para el docente) usan el calculador.

### Autorización

- `student/*`: el estudiante solo ve **lo suyo** (controladores acotan por
  `isEnrolledIn` / `$request->user()`), sin permisos nuevos.
- `teacher/subjects/{subject}/progress`: `SubjectPolicy::view` (titular o
  coordinación).
- `coordinator/courses/{course}/progress`: `CoursePolicy::view`.

## Consecuencias

- El progreso se calcula **en cada request**. Es O(asignaturas × (contenidos +
  actividades + notas)); para catálogos grandes se cacheará en Fase 6
  (invalidando en `LearningEventSubscriber`).
- La compleción de contenido es manual y binaria; no hay "% de lectura" ni
  tiempo dedicado (se podrá añadir con más eventos sin cambiar el modelo).
- `content_completed` puede alternarse (marcar/desmarcar); solo el marcado
  genera evento, el desmarcado no.

## Alternativas descartadas

- **Tabla `progress` materializada**: se pospone hasta tener medida de coste
  real; recalcular es simple y siempre consistente.
- **Inferir contenido visto al abrir la asignatura**: ruidoso y poco fiable como
  señal de aprendizaje.
