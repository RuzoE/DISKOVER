# ADR-0005 — Modelo de dominio académico

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 2
- **Relacionado con:** ADR-0001, ADR-0003

## Contexto

La Fase 2 introduce la estructura académica: cursos, asignaturas, inscripciones
y contenidos. Hay que decidir cómo se relacionan y quién gestiona cada cosa.

## Decisión

### Entidades y relaciones

```
Course 1───N Subject 1───N Content
  │                │
  │ 1              │ N..1 (nullable)
  N                └────────► User (teacher_id)
Enrollment N──1 User (student_id)
```

- **Course** (`courses`): unidad académica con `code` único, `status`
  (draft/active/archived — enum `AcademicStatus`) y periodo opcional.
- **Subject** (`subjects`): pertenece a un curso (`course_id`, cascade). Tiene
  `code` único global, `position` para ordenar, `status` y un **único docente**
  opcional (`teacher_id`, `nullOnDelete`). Un docente con más asignaturas se
  modela con varias filas; múltiples docentes por asignatura se pospone.
- **Enrollment** (`enrollments`): vincula un estudiante a un **curso** (no a una
  asignatura). `unique(course_id, student_id)`. Estado enum `EnrollmentStatus`
  (active/withdrawn/completed). Estar inscrito en el curso da acceso a todas sus
  asignaturas activas.
- **Content** (`contents`): recurso de una asignatura (`subject_id`, cascade).
  `type` enum `ContentType` (document/video/link/text); los tres primeros usan
  `url`, `text` usa `body`. `is_published` controla la visibilidad para el
  estudiante. `created_by` (`nullOnDelete`).

### Responsabilidades (áreas y permisos)

| Área (prefijo ruta) | Rol (middleware) | Permiso (policy) | Puede |
|---|---|---|---|
| `coordinator/*` | `admin,coordinator` | `courses.manage`, `subjects.manage`, `enrollments.manage` | CRUD de cursos, asignaturas e inscripciones |
| `teacher/*` | `admin,teacher` | `contents.manage` + titularidad de la asignatura | Ver sus asignaturas; CRUD de contenidos de las que imparte |
| `student/*` | `admin,student` | — (se filtra por inscripción) | Ver sus cursos y las asignaturas activas con contenido publicado |

- El **área** se protege con `role:` (grano grueso) y la **acción concreta** con
  policies que consultan permisos (grano fino). Ver ADR-0003.
- El estudiante no tiene permisos académicos: sus controladores acotan la
  consulta a `enrolledCourses()` / `isEnrolledIn()` y devuelven 403/404.
- El administrador entra en las tres áreas (aparece en cada lista `role:`) y
  supera las policies vía `Gate::before`.

### Servicios

Toda la escritura pasa por `app/Services/Academic/{Course,Subject,Enrollment,Content}Service`
(normalización de datos, `position` autoincremental, inscripción idempotente,
coherencia `type`↔`url`/`body`). Los controladores quedan delgados.

## Consecuencias

- Inscripción a nivel de curso simplifica el modelo y las comprobaciones de
  acceso; si en el futuro se necesita matrícula por asignatura, se añadirá una
  tabla `subject_student` sin romper lo existente.
- El borrado de un curso arrastra asignaturas, contenidos e inscripciones por FK
  `cascade`: es una acción deliberadamente destructiva y se confirma en la UI.
- Un docente sólo ve/gestiona lo suyo; la titularidad se comprueba en
  `Subject::isTaughtBy()` desde las policies.

## Alternativas descartadas

- **Enrollment por asignatura:** más flexible pero innecesario ahora; multiplica
  filas y complica el acceso del estudiante.
- **Varios docentes por asignatura (pivote):** se pospone hasta que exista el
  requisito real.
