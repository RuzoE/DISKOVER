# FASE 8 — Motor de recomendaciones

- **Fecha:** 2026-09-09
- **Estado:** ✅ Completada

## 1. Objetivo

Motor de **reglas deterministas** (separado de la IA generativa, sección 17) que
genera recomendaciones personalizadas para el estudiante a partir de su
progreso, notas e intentos, con **seguimiento** de su estado
(pendiente → en curso / descartada / resuelta).

## 2. Decisión de arquitectura

- **ADR-0011** — `RecommendationService → RecommendationEngine → RecommendationRule[]`.
  6 reglas, deduplicación por firma, *upsert* idempotente, respeta descartes.

## 3. Archivos creados

### Base de datos
```
database/migrations/2026_09_09_110001_create_recommendations_table.php
database/factories/RecommendationFactory.php
```

### Dominio / módulo Recommendations
```
app/Enums/RecommendationType.php
app/Enums/RecommendationPriority.php
app/Enums/RecommendationStatus.php
app/Models/Recommendation.php
app/DTOs/Recommendations/RecommendationDraft.php
app/Services/Recommendations/Contracts/RecommendationRule.php
app/Services/Recommendations/Rules/OverdueActivitiesRule.php
app/Services/Recommendations/Rules/UpcomingActivitiesRule.php
app/Services/Recommendations/Rules/WeakSubjectsRule.php
app/Services/Recommendations/Rules/RetryEvaluationRule.php
app/Services/Recommendations/Rules/UnreadContentRule.php
app/Services/Recommendations/Rules/PositiveReinforcementRule.php
app/Services/Recommendations/RecommendationEngine.php
app/Services/Recommendations/RecommendationService.php
app/Policies/RecommendationPolicy.php
```

### HTTP / vistas
```
app/Http/Requests/Recommendations/RespondRecommendationRequest.php
app/Http/Controllers/Student/RecommendationController.php
resources/views/student/recommendations/index.blade.php
resources/views/components/recommendations/card.blade.php
resources/css/components/recommendations.css
```

### Pruebas
```
tests/Feature/Recommendations/RecommendationEngineTest.php
tests/Feature/Recommendations/RecommendationServiceTest.php
tests/Feature/Recommendations/RecommendationHttpTest.php
```

### Documentación
```
docs/architecture/ADR-0011-motor-de-recomendaciones.md
docs/fases/FASE-8-motor-de-recomendaciones.md
```

## 4. Archivos modificados

```
app/Models/User.php                     # relación recommendations()
app/Providers/AuthServiceProvider.php   # RecommendationPolicy
app/Services/Analytics/DashboardService.php  # panel de recomendaciones en el dashboard del estudiante
routes/web.php                          # student/recommendations (index + respond)
resources/views/student/dashboard/index.blade.php   # tarjeta "Recomendaciones para ti"
resources/views/components/navigation/sidebar.blade.php  # enlace "Recomendaciones"; "Próximamente" -> Fase 9
resources/css/app.css                   # import recommendations.css
database/seeders/DemoAcademicSeeder.php # actividad vencida + quiz 50 % + generación de recomendaciones demo
```

## 5. Reglas del motor

| Regla | Condición | Prioridad |
|---|---|---|
| `OverdueActivitiesRule` | Actividad publicada, vencida y sin calificar | Alta |
| `WeakSubjectsRule` | Promedio de la asignatura < `pass_threshold` (60 %) | Alta |
| `RetryEvaluationRule` | Quiz por debajo del aprobado con intentos restantes | Media |
| `UpcomingActivitiesRule` | Entrega en ≤ 7 días, sin nota | Media |
| `UnreadContentRule` | Contenido sin completar en asignatura floja (máx. 5) | Baja |
| `PositiveReinforcementRule` | Progreso y promedio ≥ 80 %, sin vencidas | Baja |

Umbrales: `config('dsle.analytics.*)` (reutilizados de la Fase 6).

## 6. Seguimiento

- Firma `tipo:objetivo` (`overdue:activity:12`, `focus:subject:3`…) con
  `unique(user_id, signature)` → nunca se duplica.
- Al regenerar: una recomendación **abierta** cuya condición ya no aplica pasa a
  `completed` (`responded_at`). Una `dismissed`/`completed` no se recrea.
- El estudiante marca cada recomendación **En curso**, **Hecho** o **Descartar**.
- Generación perezosa al abrir la sección o el dashboard (idempotente).

## 7. Cómo probar

### Automático
```bash
php artisan test            # 133 pruebas (116 previas + 17 de Fase 8)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
El estudiante demo (`estudiante@diskover.test`) queda con **2 recomendaciones**:
«Actividad vencida sin entregar» (alta) y «Vuelve a intentar el cuestionario»
(media, 50 % con 1 intento restante).

1. *Recomendaciones* (sidebar). Ver las tarjetas con prioridad, tipo y acción
   sugerida. Pulsar *Ir a la actividad*, *Marcar en curso*, *Hecho* o *Descartar*.
2. El *Panel* del estudiante muestra hasta 3 recomendaciones y el contador.
3. Entregar/repetir la actividad y recargar: la recomendación pasa a *Resuelta*
   en el historial.
4. Un docente o coordinación no puede abrir la sección → **403**. Un estudiante
   no puede responder recomendaciones de otro → **403**.

## 8. Resultado esperado

El estudiante recibe recomendaciones claras y explicables, actúa sobre ellas y
el sistema registra el estado. Sin IA, sin no-determinismo. `php artisan test` → 133/133.

## 9. Checklist

- [x] Backend — `Engine` + `Rules` + `Service`; controlador delgado; reutiliza analítica
- [x] Base de datos — `recommendations` con `unique(user_id, signature)` e índices por estado/prioridad
- [x] Frontend — `x-recommendations.card`, panel en el dashboard; CSS modular
- [x] Validaciones — `RespondRecommendationRequest` (acción en accept/dismiss/complete)
- [x] Seguridad — `role:admin,student`; `RecommendationPolicy` (sólo el dueño); `generateFor` ignora no-estudiantes
- [x] Responsive — tarjetas y acciones apilan en móvil
- [x] Pruebas — 17 nuevas (cada regla, idempotencia, resolución, descartes, HTTP)
- [x] Organización — `app/Services/Recommendations/`, `app/Http/Controllers/Student/`; conforme a ADR-0011

## 10. Notas para la siguiente fase

- **Fase 9 (experiencias inmersivas)**: módulo separado (`app/Services/Immersive/`,
  `resources/views/immersive/`), integración Laravel + Unity.
- Mejora futura: generación en *job* tras eventos de calificación/entrega y caché;
  re-emergencia de recomendaciones descartadas tras N días.
