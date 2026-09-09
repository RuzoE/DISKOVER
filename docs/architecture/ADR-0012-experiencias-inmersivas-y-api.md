# ADR-0012 — Experiencias inmersivas e integración Laravel ↔ Unity

- **Estado:** Aceptado
- **Fecha:** 2026-09-09
- **Contexto de decisión:** Fase 9
- **Relacionado con:** ADR-0001, ADR-0005, ADR-0006, sección 19 del prompt maestro

## Contexto

DSLE debe registrar experiencias inmersivas (VR/AR/Unity), vincularlas a
asignaturas y actividades, controlar su disponibilidad, identificar al
estudiante, registrar sesiones y **recibir resultados**. El prompt maestro
(sección 19) exige que Unity **no se mezcle** con el código de Laravel: Laravel
orquesta y persiste; Unity ejecuta la experiencia.

## Decisión

### Modelo

- **`ImmersiveExperience`** (catálogo): `slug`, `title`, `provider`
  (`unity_webgl` | `unity_standalone` | `webxr` | `external` | `simulator`),
  `launch_url`, `config` (JSON que se envía tal cual al cliente), `max_score`,
  `status` (`AcademicStatus`), `activity_id?` (si se vincula, el resultado
  califica esa actividad). Pivote **`experience_subject`** (`is_required`): en qué
  asignaturas aparece.
- **`ImmersiveSession`**: una ejecución de un estudiante. `launch_token` (64
  chars, único), `status` (`started` | `completed` | `abandoned` | `expired`),
  `score`, `max_score`, `payload` (telemetría/resultados de Unity),
  `started_at` / `ended_at`.
- `GradeSource::Immersive` (nuevo): las notas derivadas de una experiencia.

### Integración Laravel ↔ cliente (Unity)

El navegador del estudiante lanza la experiencia desde la web → Laravel crea una
`ImmersiveSession` con `launch_token` → abre el cliente (iframe para WebGL/WebXR,
enlace para escritorio/externo, o el **simulador** integrado si no hay build).

El cliente habla con una **API REST versionada y autenticada por token**
(`routes/api.php`, `/api/v1/immersive/*`, `throttle:immersive`):

| Método | Ruta | Para qué |
|---|---|---|
| `GET` | `/api/v1/immersive/sessions/{token}` | configuración de la experiencia + datos del estudiante + estado |
| `POST` | `/api/v1/immersive/sessions/{token}/complete` | `{ score, payload }` → cierra la sesión y registra el resultado |
| `POST` | `/api/v1/immersive/sessions/{token}/abandon` | cierre sin resultado |

- El `launch_token` **es** la credencial: identifica sesión → estudiante →
  experiencia. No requiere Sanctum ni cookie. Un token expira a
  `started_at + session_ttl_minutes` (config, 6 h por defecto); pasada esa
  ventana la sesión pasa a `expired` y `complete` devuelve 422.
- `complete` es efectivamente de un solo uso (si la sesión no está `started`,
  422).

### Resultado → calificación

`ImmersiveSessionService::complete()`:
1. acota `score` a `[0, experience.max_score]`, marca la sesión `completed` y
   guarda `payload`.
2. si `experience.activity_id` apunta a una actividad **publicada**,
   `GradeService::setFromImmersive()` escala la nota a `activity.max_score`
   (`source = immersive`, `graded_by = null`) y dispara `ActivityGraded` →
   evento de aprendizaje + refresco de recomendaciones (fases 4 y 8). No pisa una
   nota `manual`.

### Autorización

- Catálogo: `role:admin,coordinator` + permiso **`immersive.manage`**
  (`ImmersiveExperiencePolicy`); rol coordinación lo recibe en el seeder.
- Estudiante: `role:admin,student`; `launch` sólo si la experiencia está activa y
  el estudiante está inscrito en un curso de alguna asignatura vinculada; sólo
  ve/cierra **sus** sesiones.

## Consecuencias

- Unity y Laravel quedan desacoplados: contrato = 3 endpoints + un token. Se
  puede cambiar de motor sin tocar el dominio.
- El **simulador** (`provider = simulator`, sin `launch_url`) hace toda la
  función demostrable y testeable en el navegador con la misma API que usaría
  Unity.
- El `launch_token` va en la URL del cliente: es de un solo resultado y de vida
  corta, pero conviene servir el cliente por HTTPS y no registrarlo en logs.
- Sin heartbeat/streaming de progreso: sólo inicio y fin. Se puede añadir un
  endpoint de progreso sin romper el contrato.

## Alternativas descartadas

- **Autenticar el cliente con Sanctum/OAuth**: sobreingeniería para una sesión
  efímera; el token de un solo uso es suficiente y más simple para Unity.
- **Guardar los resultados por webhook firmado del proveedor**: útil para SaaS de
  terceros; se puede añadir como otro `provider` sin cambiar el modelo.
- **Añadir `ActivityType::Immersive`**: propagaría cambios por todo el módulo de
  actividades; basta con `activity_id` en la experiencia.
