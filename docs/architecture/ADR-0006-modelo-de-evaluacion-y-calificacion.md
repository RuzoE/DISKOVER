# ADR-0006 — Modelo de evaluación y motor de calificación

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 3
- **Relacionado con:** ADR-0005

## Contexto

La Fase 3 añade actividades evaluables a las asignaturas: entregas manuales y
evaluaciones en línea con preguntas, intentos y calificaciones. Hace falta un
modelo que sirva tanto para calificación manual como automática y que deje el
"cuaderno" listo para el seguimiento (Fase 4) y la analítica (Fase 6).

## Decisión

### Entidades

```
Subject 1─N Activity ──1:1 (solo quiz)── Evaluation 1─N Question 1─N QuestionOption
                │                              │
                │ 1─N                          │ 1─N
              Grade                          Attempt 1─N AttemptAnswer
                └────────── attempt_id (nullable) ──────────┘
```

- **Activity** (`activities`): `type` = `task` (entrega, nota manual) o `quiz`
  (evaluación en línea). Ventana `opens_at`/`due_at`, `max_score`, `is_published`.
  El tipo es **inmutable** tras crearse.
- **Evaluation** (`evaluations`, 1:1 con la actividad `quiz`): `time_limit_minutes`,
  `max_attempts`, `shuffle_questions`, `pass_score` (%).
- **Question** / **QuestionOption**: tipo `single` | `multiple` | `boolean` | `open`.
  Las tres primeras se autocorrigen; `open` la califica el docente.
- **Attempt** / **AttemptAnswer**: un intento por `(evaluation, student, number)`.
  `AttemptAnswer` guarda `selected_option_ids` (JSON) para cerradas y `text_answer`
  para abiertas, más `is_correct` y `score_awarded`.
- **Grade** (`grades`): **una fila por `(activity, student)`** — el cuaderno único.
  `source` = `manual` | `auto`; `attempt_id` apunta al intento que la originó.

### Motor de calificación (`app/Services/Academic`)

- **Envío de intento** (`AttemptService::submit`): corrige cada pregunta cerrada
  (acierto = conjunto de opciones elegidas == conjunto correcto, **todo o nada**;
  la corrección parcial de `multiple` queda pendiente). `attempt.score` = puntos
  obtenidos, `attempt.max_score` = suma de `question.score`.
  - Sin preguntas abiertas → `status = graded` y se consolida la nota.
  - Con preguntas abiertas → `status = submitted` (a la espera del docente).
- **Revisión** (`AttemptService::gradeOpenAnswers`): el docente asigna puntos a
  cada respuesta abierta (acotados a `question.score`); recalcula y pasa a `graded`.
- **Consolidación** (`GradeService::syncFromBestAttempt`): toma el intento
  `graded` de mayor `score`, lo escala a `activity.max_score`
  (`score/max_score * activity.max_score`) y hace *upsert* del `Grade` (`source=auto`).
  **No pisa** un `Grade` `manual` existente.
- **Nota manual** (`GradeService::setManual`): *upsert* directo para las entregas.

### Autorización

- Área `teacher/*` (`role:admin,teacher`). Permisos nuevos: `activities.manage`,
  `grades.manage` (rol docente).
- `ActivityPolicy` / `AttemptPolicy` / `GradePolicy` exigen el permiso **y** la
  titularidad de la asignatura (`Subject::isTaughtBy`). Admin pasa por `Gate::before`.
- El estudiante: `AttemptPolicy::create` comprueba inscripción + actividad abierta;
  los límites de intentos y de tiempo se validan en `AttemptService`. El plazo se
  revalida en el servidor aunque el temporizador del cliente falle.

## Consecuencias

- El `Grade` único por actividad simplifica el cuaderno y el cálculo de promedios
  de las siguientes fases.
- `multiple` es todo-o-nada; introducir corrección parcial será un cambio aislado
  en `AttemptService::isClosedAnswerCorrect` + almacenamiento del parcial.
- Cambiar de `task` a `quiz` no está permitido; si se necesita, se crea otra
  actividad.
- Barajado de preguntas (`shuffle_questions`) se guarda pero aún no se aplica en
  la vista de resolución.

## Alternativas descartadas

- **Evaluation independiente de Activity:** obligaría a dos "cosas calificables"
  en paralelo y complicaría el cuaderno.
- **Nota derivada siempre del último intento:** se elige *mejor intento*; es lo más
  habitual y evita penalizar reintentos.
