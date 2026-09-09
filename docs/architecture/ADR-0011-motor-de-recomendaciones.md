# ADR-0011 — Motor de recomendaciones (reglas, sin IA generativa)

- **Estado:** Aceptado
- **Fecha:** 2026-09-09
- **Contexto de decisión:** Fase 8
- **Relacionado con:** ADR-0007, ADR-0009, ADR-0010, sección 17 del prompt maestro

## Contexto

DSLE necesita recomendaciones personalizadas para el estudiante (qué repasar,
qué entregar, qué reintentar) con **seguimiento** de si actúa sobre ellas. El
prompt maestro (sección 17) exige que este motor esté **separado de la IA
generativa** de la Fase 7 y que se base en reglas sobre resultados y progreso.

## Decisión

### Motor de reglas determinista

```
Controlador → RecommendationService → RecommendationEngine → RecommendationRule[]
```

- **`App\Services\Recommendations\Contracts\RecommendationRule`** —
  `evaluate(User $student): iterable<RecommendationDraft>`.
- **6 reglas** en `Rules/`, cada una consulta datos reales (progreso, notas,
  intentos, contenidos) reutilizando `ProgressCalculator` / `StudentAnalyticsService`
  (fases 4–6). **Ninguna llama a IA.**
  1. `OverdueActivitiesRule` — vencidas sin calificar → prioridad alta.
  2. `WeakSubjectsRule` — promedio de asignatura < `pass_threshold` → alta.
  3. `RetryEvaluationRule` — quiz por debajo del aprobado con intentos restantes → media.
  4. `UpcomingActivitiesRule` — entrega en ≤ 7 días sin nota → media.
  5. `UnreadContentRule` — contenidos sin completar en asignaturas flojas → baja (máx. 5).
  6. `PositiveReinforcementRule` — todo en verde (progreso y promedio ≥ 80, sin vencidas) → baja.
- **`RecommendationEngine`** ejecuta todas, deduplica por **firma** (`tipo:objetivo`)
  y ordena por prioridad.
- **`RecommendationService`** persiste y hace el **seguimiento**:
  - *upsert* por `(user_id, signature)` — `unique` en la tabla, nunca duplica.
  - una recomendación **abierta** (pending/accepted) cuya condición ya no aplica
    → se marca `completed` con `responded_at` (el estudiante la resolvió).
  - **respeta las decisiones**: una `dismissed` o `completed` no se resucita
    aunque la condición siga vigente.
  - `respond()` fija `status` (accepted | dismissed | completed) + `responded_at`.

### Datos

`recommendations`: `user_id`, `type`, `priority`, `title`, `body`,
`reason` (JSON con las métricas que la dispararon — transparencia y base para
un futuro modelo), `subject_id?`/`activity_id?`/`content_id?`, `status`,
`signature`, `generated_at`, `responded_at`. `unique(user_id, signature)`.

### Generación

Perezosa: al abrir `/student/recommendations` o el panel del estudiante se llama
a `generateFor()` (idempotente). No hay job ni cron en esta fase.

### Autorización

Área `/student/recommendations` bajo `role:admin,student`. `RecommendationPolicy`:
sólo el dueño responde. `generateFor()` ignora a quien no tenga rol de estudiante.

## Consecuencias

- Recomendaciones explicables (regla + `reason`), auditables y testeables una a una.
- El motor y la IA (Fase 7) son módulos independientes; se pueden combinar más
  adelante (p. ej. la IA redactando el cuerpo) sin fusionar responsabilidades.
- `generateFor()` recorre reglas en cada carga: O(cursos·asignaturas·ítems).
  Aceptable a escala de estudiante; se moverá a job/caché si hace falta.
- Una recomendación descartada no reaparece aunque el problema persista; si se
  quisiera re-emerger tras N días, sería un cambio acotado en `RecommendationService`.

## Alternativas descartadas

- **Generar las recomendaciones con la IA generativa**: lo prohíbe la sección 17;
  además serían no deterministas, difíciles de testear y con coste por token.
- **Tabla de reglas configurable en BD**: sobreingeniería ahora; las reglas en
  código son claras y versionadas. Se puede evolucionar.
