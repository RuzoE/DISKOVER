# ADR-0009 — Learning Analytics y gráficas propias

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 6
- **Relacionado con:** ADR-0004, ADR-0007

## Contexto

La Fase 6 añade análisis de desempeño y gráficas sobre los datos de progreso y
calificaciones (fases 3–4): evolución en el tiempo, promedio por asignatura,
distribución de notas, estudiantes en riesgo y preguntas más difíciles.

Dos decisiones abiertas: **cómo dibujar las gráficas** y **dónde vive el cálculo**.

## Decisión

### Gráficas sin dependencias

No se añade ninguna librería de gráficos (Chart.js, apexcharts…). Se crean
**componentes Blade propios**, coherentes con el sistema de diseño (ADR-0004) y
sin coste de bundle:

- `x-chart.bars` — barras horizontales en CSS (ancho por `width: %`), con color
  por umbral (verde ≥80, azul ≥60, ámbar <60, gris sin datos). Sirve para
  categóricas y para la distribución.
- `x-chart.line` — **SVG inline** generado en el servidor: `viewBox` fijo,
  `polyline` + puntos con `<title>` para tooltip nativo, rejilla y ejes.
  Escala a 0–100. Para series temporales (evolución).

Ambos degradan a un *empty-state* si no hay datos. Sin JS.

### Capa de cálculo en `app/Services/Analytics/`

- `PerformanceAnalyticsService` — `studentReport(User)` y `courseReport(Course)`:
  evolución, desglose por asignatura, distribución, actividades/asignaturas
  débiles, estudiantes en riesgo. Método público `distribution(Collection)` para
  el histograma en tramos de 10 puntos (`0–59 · 60–69 · 70–79 · 80–89 · 90–100`).
- `AssessmentAnalyticsService` — `evaluationBreakdown(Evaluation)` y
  `difficultQuestions(Subject)`: por pregunta, tasa de dominio sobre intentos
  enviados/calificados. **Cerradas** = aciertos / respondidas; **abiertas** =
  puntos medios / puntuación máxima (partial credit justo).
- Reutilizan `ProgressCalculator` / `CourseAnalyticsService` (ADR-0007).

### Umbrales configurables

`config/dsle.php` → `analytics`:
`pass_threshold` (60), `at_risk_threshold` (60), `weak_question_rate` (0.5).

### Rutas y autorización

`/analytics/student` (`role:admin,student`, informe propio),
`/analytics/subjects/{subject}` (`role:admin,teacher,coordinator` + `SubjectPolicy::view`),
`/analytics/courses/{course}` (`role:admin,coordinator` + `CoursePolicy::view`).
Controladores en `app/Http/Controllers/Analytics/`, vistas en `resources/views/analytics/`.

## Consecuencias

- Cero dependencias front; las gráficas son server-side, imprimibles y
  accesibles (SVG con `role="img"` y `<title>`).
- El cálculo es **en cada request** (como el progreso de Fase 4). Para
  `courseReport` es O(estudiantes × asignaturas × ítems); aceptable a escala de
  curso, se cacheará si hace falta (invalidando desde `LearningEventSubscriber`).
- Interactividad limitada (sin zoom/hover avanzado). Si el producto lo pide más
  adelante, se podrá adoptar una librería en un ADR nuevo sin tocar la capa de
  servicios.
- La "dificultad" de una pregunta abierta se mide por puntos medios, no por
  acierto/fallo binario.

## Alternativas descartadas

- **Chart.js vía npm/cdnjs**: dependencia y JS por una necesidad que el SVG
  server-side cubre; rompería la línea de ADR-0004 (sin framework de front).
- **Tabla `analytics` materializada**: prematura; recalcular es simple y siempre
  consistente.
