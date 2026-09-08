# FASE 3 — Actividades y evaluaciones

- **Fecha:** 2026-09-08
- **Estado:** ✅ Completada

## 1. Objetivo

Actividades evaluables por asignatura (entregas y evaluaciones en línea),
preguntas, intentos, corrección automática, revisión del docente y un cuaderno de
calificaciones único por actividad.

## 2. Decisión de arquitectura

- **ADR-0006** — Modelo de evaluación y motor de calificación.

## 3. Archivos creados (58)

### Base de datos
```
database/migrations/2026_09_08_120001_create_activities_table.php
database/migrations/2026_09_08_120002_create_evaluations_table.php
database/migrations/2026_09_08_120003_create_questions_table.php     (+ question_options)
database/migrations/2026_09_08_120004_create_attempts_table.php      (+ attempt_answers)
database/migrations/2026_09_08_120005_create_grades_table.php
database/factories/{Activity,Evaluation,Question,Attempt,Grade}Factory.php
```

### Dominio
```
app/Enums/{ActivityType,QuestionType,AttemptStatus,GradeSource}.php
app/Models/{Activity,Evaluation,Question,QuestionOption,Attempt,AttemptAnswer,Grade}.php
app/Services/Academic/{Activity,Question,Attempt,Grade}Service.php
app/Policies/{Activity,Attempt,Grade}Policy.php
```

### HTTP
```
app/Http/Requests/Academic/StoreActivityRequest.php     UpdateActivityRequest.php
app/Http/Requests/Academic/UpdateEvaluationRequest.php
app/Http/Requests/Academic/QuestionRequest.php
app/Http/Requests/Academic/SaveAttemptRequest.php
app/Http/Requests/Academic/StoreGradeRequest.php        ReviewAttemptRequest.php
app/Http/Controllers/Teacher/{Activity,Evaluation,Question,Grade,AttemptReview}Controller.php
app/Http/Controllers/Student/{Activity,Attempt}Controller.php
```

### Vistas y componentes
```
resources/views/components/academic/{activity-form,question-form}.blade.php
resources/views/teacher/activities/{index,create,edit,show}.blade.php
resources/views/teacher/evaluations/edit.blade.php
resources/views/teacher/questions/{create,edit}.blade.php
resources/views/teacher/grades/index.blade.php
resources/views/teacher/attempts/review.blade.php
resources/views/student/activities/show.blade.php
resources/views/student/attempts/{take,result}.blade.php
```

### CSS / JS
```
resources/css/components/assessment.css
resources/js/modules/teacher/question-form.js
resources/js/modules/student/quiz.js
```

### Pruebas
```
tests/Feature/Assessment/ActivityManagementTest.php
tests/Feature/Assessment/QuestionManagementTest.php
tests/Feature/Assessment/AttemptFlowTest.php
tests/Feature/Assessment/GradingTest.php
tests/Feature/Assessment/StudentActivityAccessTest.php
```

### Documentación
```
docs/architecture/ADR-0006-modelo-de-evaluacion-y-calificacion.md
docs/fases/FASE-3-actividades-y-evaluaciones.md
```

## 4. Archivos modificados

```
app/Models/Subject.php                  # relación activities()
app/Models/User.php                     # relaciones attempts(), grades()
app/Providers/AuthServiceProvider.php   # Activity/Attempt/Grade policies
routes/web.php                          # rutas teacher/* (actividades, evaluación,
                                        #   preguntas, cuaderno, revisión) y student/*
                                        #   (actividad, intentos, guardar, enviar)
database/seeders/RolePermissionSeeder.php  # permisos activities.manage, grades.manage (rol docente)
database/seeders/DemoAcademicSeeder.php # 1 trabajo + 1 evaluación con 3 preguntas
resources/views/teacher/subjects/{index,show}.blade.php   # enlace "Actividades"
resources/views/student/subjects/show.blade.php           # lista de actividades publicadas
app/Http/Controllers/Student/SubjectController.php         # pasa activities a la vista
resources/css/app.css · resources/js/app.js               # nuevos imports
```

## 5. Rutas nuevas

```
teacher/subjects/{subject}/activities         (resource shallow)
teacher/activities/{activity}                 (show, edit, update, destroy)
teacher/activities/{activity}/evaluation      (GET, PUT)
teacher/activities/{activity}/questions/create · POST questions
teacher/questions/{question}                  (edit, update, destroy)
teacher/activities/{activity}/gradebook       · POST grades
teacher/attempts/{attempt}/review             (GET, PUT)
student/activities/{activity}                 (show)
student/activities/{activity}/attempts        (POST, iniciar)
student/attempts/{attempt}                    (show: resolver o resultado)
student/attempts/{attempt}/save · /submit     (POST)
```

## 6. Motor de calificación (resumen)

- **Trabajo (`task`)**: el docente introduce nota + comentario en el cuaderno →
  `Grade` `source=manual`.
- **Evaluación (`quiz`)**: el estudiante inicia un intento (respeta
  `max_attempts` y ventana), responde y envía.
  - Preguntas cerradas → corrección automática (todo o nada).
  - Sin preguntas abiertas → intento `graded`, `Grade` `source=auto` escalada a
    `activity.max_score` (mejor intento).
  - Con preguntas abiertas → intento `submitted`; el docente puntúa cada respuesta
    abierta en *Revisar intento* → `graded` + `Grade`.
  - Una `Grade` `manual` nunca se sobrescribe por la sincronización automática.
- El plazo se revalida en el servidor al enviar (el temporizador del cliente es
  solo ayuda visual).

## 7. Cómo probar

### Automático
```bash
php artisan test            # 68 pruebas (46 previas + 22 de Fase 3)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
El seeder deja en la asignatura demo (`DSLE-101-A`): un **trabajo** y una
**evaluación** con 3 preguntas (única, V/F y abierta).

1. **Docente** (`docente@diskover.test`): *Mis asignaturas → Actividades*.
   Crear actividad; en un `quiz` entrar en *Configurar y preguntas*, añadir
   preguntas (el formulario cambia según el tipo; se pueden añadir/quitar
   opciones). *Cuaderno*: poner nota a un trabajo; *Revisar intento* de una
   evaluación con respuesta abierta.
2. **Estudiante** (`estudiante@diskover.test`): *Mis cursos → asignatura →
   actividad*. Comenzar intento, responder, *Enviar*. Ver el resultado con la
   corrección por pregunta. Reintentar si quedan intentos.
3. Un docente ajeno a la asignatura recibe **403**; un estudiante no inscrito,
   **403**; una actividad no publicada, **404**.

## 8. Resultado esperado

- El docente crea y publica actividades; construye evaluaciones con preguntas;
  califica trabajos y revisa intentos.
- El estudiante resuelve evaluaciones y ve su nota y corrección; las evaluaciones
  100 % cerradas se califican solas.
- `php artisan test` → 68/68.

## 9. Checklist

- [x] Backend — controladores delgados; motor de calificación en `Services/Academic`
- [x] Base de datos — 7 tablas, FK `cascade`/`nullOnDelete`, unicidad `(activity,student)` y `(evaluation,student,number)`
- [x] Frontend — formularios dinámicos (pregunta, cuestionario), cuaderno, revisión, resultado; CSS modular
- [x] Validaciones — 7 Form Requests + reglas condicionales de pregunta
- [x] Seguridad — `role:` por área + policies con titularidad; plazo e intentos revalidados en servidor
- [x] Responsive — tablas con scroll; opciones y bloques apilan en móvil
- [x] Pruebas — 22 nuevas (actividades, preguntas, intentos, calificación, acceso)
- [x] Organización — conforme a ADR-0002/0005/0006; documentación al día

## 10. Notas para la siguiente fase

- **Fase 4 (seguimiento)** consumirá `grades` + `attempts` + `enrollments` para el
  progreso y el perfil académico.
- Pendientes menores (documentados en ADR-0006): corrección parcial de `multiple`,
  aplicar `shuffle_questions` en la resolución, reordenación drag-and-drop.
