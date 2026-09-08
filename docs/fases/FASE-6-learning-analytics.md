# FASE 6 — Learning Analytics

- **Fecha:** 2026-09-08
- **Estado:** ✅ Completada

## 1. Objetivo

Indicadores, estadísticas y **gráficas** de desempeño sobre los datos de las
fases 3–4: evolución en el tiempo, promedio por asignatura, distribución de
notas, identificación de dificultades (actividades/asignaturas débiles,
estudiantes en riesgo, preguntas más falladas).

## 2. Decisión de arquitectura

- **ADR-0009** — Learning Analytics y gráficas propias: componentes Blade
  `x-chart.bars` (CSS) y `x-chart.line` (SVG server-side), sin dependencias;
  cálculo en `app/Services/Analytics/`.

## 3. Archivos creados

```
app/Services/Analytics/PerformanceAnalyticsService.php
app/Services/Analytics/AssessmentAnalyticsService.php
app/Http/Controllers/Analytics/{Student,Subject,Course}AnalyticsController.php
resources/views/analytics/{student,subject,course}.blade.php
resources/views/components/chart/{bars,line}.blade.php
resources/css/components/charts.css
tests/Feature/Analytics/{Student,Subject,Course}AnalyticsTest.php
tests/Unit/GradeDistributionTest.php
docs/architecture/ADR-0009-learning-analytics-y-graficas.md
docs/fases/FASE-6-learning-analytics.md
```

## 4. Archivos modificados

```
config/dsle.php                         # bloque analytics: pass_threshold, at_risk_threshold, weak_question_rate
routes/web.php                          # grupo /analytics (student / subjects/{subject} / courses/{course})
resources/css/app.css                   # import charts.css
resources/views/components/navigation/sidebar.blade.php   # "Mi analítica" (estudiante); "Próximamente" actualizado
resources/views/student/profile/show.blade.php            # botón "Analítica detallada"
resources/views/teacher/subjects/show.blade.php           # botón "Analítica"
resources/views/coordinator/courses/show.blade.php        # botón "Analítica"
resources/views/coordinator/dashboard/index.blade.php     # enlace "Analítica" por curso
database/seeders/DemoAcademicSeeder.php # intento de evaluación resuelto + revisado (datos para gráficas)
```

## 5. Rutas nuevas

```
GET /analytics/student              analytics.student   role:admin,student
GET /analytics/subjects/{subject}   analytics.subject   role:admin,teacher,coordinator + SubjectPolicy::view
GET /analytics/courses/{course}     analytics.course    role:admin,coordinator + CoursePolicy::view
```

## 6. Qué muestra cada informe

| Informe | Gráficas | Listas |
|---|---|---|
| **Estudiante** (`/analytics/student`) | Evolución (línea) · Promedio por asignatura (barras) · Distribución de notas (barras) | Actividades bajo el umbral · Asignaturas a reforzar |
| **Asignatura** (`/analytics/subjects/{id}`) | Promedio por estudiante (barras) · Distribución de promedios (barras) | Estudiantes en riesgo · Preguntas más difíciles |
| **Curso** (`/analytics/courses/{id}`) | Evolución del grupo (línea) · Promedio por asignatura (barras) · Distribución (barras) | Estudiantes en riesgo |

- **Umbrales** (`config/dsle.php`): no superado <60 %, en riesgo <60 %, pregunta
  difícil con tasa de dominio <0,5.
- **Tasa de dominio de una pregunta**: cerradas = aciertos/respondidas;
  abiertas = puntos medios/puntuación máxima.

## 7. Cómo probar

### Automático
```bash
php artisan test            # 102 pruebas (90 previas + 12 de Fase 6)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
El seeder deja al estudiante demo con una nota manual (82) y un intento de
evaluación resuelto y revizado, suficiente para poblar las gráficas.

1. **Estudiante** (`estudiante@diskover.test`): *Mi analítica* (sidebar) o
   *Perfil → Analítica detallada*. Ver la línea de evolución, las barras por
   asignatura y la distribución.
2. **Docente** (`docente@diskover.test`): *Mis asignaturas → una asignatura →
   Analítica*. Barras por estudiante, distribución, estudiantes en riesgo y
   *Preguntas más difíciles*. Un docente ajeno recibe **403**.
3. **Coordinación** (`coordinacion@diskover.test`): *Cursos → un curso →
   Analítica* (o desde su dashboard). Evolución del grupo, promedio por
   asignatura, distribución, estudiantes en riesgo.
4. Acceso cruzado: estudiante → **403** en analítica de asignatura/curso;
   coordinación → **403** en analítica de estudiante.

## 8. Resultado esperado

Cada rol dispone de un informe con gráficas legibles (sin JS, imprimibles) y
listas accionables para identificar dificultades. `php artisan test` → 102/102.

## 9. Checklist

- [x] Backend — `PerformanceAnalyticsService` / `AssessmentAnalyticsService`; controladores delgados
- [x] Base de datos — sin cambios (solo lecturas y agregación)
- [x] Frontend — `x-chart.bars` (CSS) y `x-chart.line` (SVG); CSS modular; degradan a *empty-state*
- [x] Validaciones — N/A
- [x] Seguridad — `role:` por área + `SubjectPolicy` / `CoursePolicy`; estudiante solo su informe
- [x] Responsive — barras con grid adaptable; SVG con `overflow-x` y `min-width`
- [x] Accesibilidad — SVG con `role="img"`, `aria-label` y `<title>` por punto
- [x] Pruebas — 12 nuevas (informes por rol, dificultades, distribución, acceso)
- [x] Organización — `Http/Controllers/Analytics/`, `views/analytics/`; conforme a ADR-0009

## 10. Notas para la siguiente fase

- **Fase 7 (IA)** podrá consumir estos informes como contexto del asistente
  educativo (por ejemplo, «tu punto débil es X»).
- Pendiente si el coste lo exige: cachear `courseReport` invalidando desde
  `LearningEventSubscriber`.
