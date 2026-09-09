# ADR-0013 — Módulo de reportes y exportación

- **Estado:** Aceptado
- **Fecha:** 2026-09-09
- **Contexto de decisión:** Fase 10
- **Relacionado con:** ADR-0007, ADR-0009 (analítica), sección 10 del prompt maestro

## Contexto

La Fase 6 dio analítica **exploratoria y visual** (gráficas en pantalla). La
Fase 10 pide **reportes formales**: tablas completas, con contexto y resumen,
que además se puedan **exportar**. Son documentos, no dashboards.

## Decisión

### Un contrato común para todos los reportes

`App\Services\Reports\Contracts\Report`:

```
key(): string        // nombre de fichero
title(): string
meta(): array         // etiqueta => valor (curso, fechas…)
headings(): array     // columnas
rows(): array          // filas en el mismo orden que headings()
summary(): array       // indicadores agregados (puede estar vacío)
```

Un mismo objeto `Report` se **renderiza** (vista genérica `reports/show.blade.php`)
y se **exporta** (`ReportExporter`). No hay lógica duplicada por formato.

### Reportes concretos (`app/Services/Reports/`)

| Reporte | Alcance | Para |
|---|---|---|
| `InstitutionalReport` | plataforma | admin / coordinación |
| `CourseAcademicReport` | un curso | admin / coordinación |
| `SubjectPerformanceReport` | una asignatura (matriz estudiante × actividad) | docente titular / coordinación |
| `StudentTranscriptReport` | un estudiante | coordinación (cualquiera) · estudiante (el suyo) |

Todos reutilizan `ProgressCalculator` / `CourseAnalyticsService` de fases
anteriores; no reimplementan cálculos.

### Exportación sin dependencias

`ReportExporter::csv(Report): StreamedResponse` escribe con `fputcsv` a
`php://output` con **BOM UTF-8** (para que Excel respete los acentos) y
`Content-Disposition: attachment`. El formato es: título, meta, línea en blanco,
cabeceras, filas, línea en blanco, resumen.

No se añade ninguna librería (PhpSpreadsheet, `league/csv`…): CSV cubre el
requisito («exportación cuando sea necesaria») y `fputcsv` ya escapa comillas y
separadores.

### Enrutado y autorización

Un `ReportController` con una acción por reporte. `?export=csv` en la misma ruta
descarga en vez de renderizar. La autorización **reutiliza las policies**:

- `/reports/institutional`, `/reports/courses/{course}` → `role:admin,coordinator`
  (+ `CoursePolicy::view`).
- `/reports/subjects/{subject}` → `role:admin,coordinator,teacher` +
  `SubjectPolicy::view` (el docente sólo sus asignaturas).
- `/reports/students/{user}/transcript` → `role:admin,coordinator`.
- `/student/transcript` → el estudiante, sólo el suyo.

Sin permisos nuevos.

## Consecuencias

- Añadir un reporte = una clase que implementa `Report` + una acción y una ruta;
  la vista y la exportación ya funcionan.
- `InstitutionalReport` y `CourseAcademicReport` iteran a los estudiantes del
  curso (coste O(estudiantes·asignaturas·ítems)); a escala institucional grande
  convendrá cachear o precalcular (job nocturno), fuera del alcance actual.
- Sólo CSV. PDF o XLSX serían otro método en `ReportExporter` (o un decorador)
  sin tocar los reportes.
- El CSV se genera en streaming pero recorre las filas en memoria al construirse
  el `Report`; para reportes muy grandes habría que hacer `rows()` perezoso.

## Alternativas descartadas

- **Reutilizar las vistas de analítica de la Fase 6**: distinta finalidad
  (gráficas vs. tabla completa y exportable) y distinta audiencia.
- **PhpSpreadsheet / exportación a Excel nativa**: dependencia pesada para un
  requisito que el CSV cumple.
- **Un permiso `reports.view`**: el gating por rol + policies de curso/asignatura
  ya es suficiente y evita otra dimensión de configuración.
