# FASE 10 — Reportes

- **Fecha:** 2026-09-09
- **Estado:** ✅ Completada

## 1. Objetivo

Reportes formales y **exportables** sobre los datos de las fases anteriores:
académicos por curso, de desempeño por asignatura, indicadores institucionales y
expediente del estudiante. Sin cambios de esquema.

## 2. Decisión de arquitectura

- **ADR-0013** — Contrato común `Report` (title/meta/headings/rows/summary); un
  mismo objeto se renderiza y se exporta. `ReportExporter::csv()` sin dependencias.

## 3. Archivos creados

```
app/Services/Reports/Contracts/Report.php
app/Services/Reports/Fmt.php
app/Services/Reports/ReportExporter.php
app/Services/Reports/InstitutionalReport.php
app/Services/Reports/CourseAcademicReport.php
app/Services/Reports/SubjectPerformanceReport.php
app/Services/Reports/StudentTranscriptReport.php
app/Http/Controllers/Reports/ReportController.php
resources/views/reports/index.blade.php
resources/views/reports/show.blade.php
tests/Feature/Reports/ReportExporterTest.php
tests/Feature/Reports/ReportContentTest.php
tests/Feature/Reports/ReportHttpTest.php
docs/architecture/ADR-0013-modulo-de-reportes.md
docs/fases/FASE-10-reportes.md
```

## 4. Archivos modificados

```
routes/web.php                          # grupo /reports (index, institutional, course, subject, transcript)
                                        #   y student/transcript
resources/views/components/navigation/sidebar.blade.php   # "Reportes" (admin/coord/docente); "Mi expediente" (estudiante)
resources/views/coordinator/courses/show.blade.php        # botón "Reporte"
resources/views/coordinator/dashboard/index.blade.php     # botón "Indicadores institucionales"
resources/views/teacher/subjects/show.blade.php           # botón "Reporte"
resources/views/admin/users/show.blade.php                # enlace "Expediente" (si es estudiante)
```

## 5. Reportes y acceso

| Reporte | Ruta | Rol / policy |
|---|---|---|
| Indicadores institucionales | `GET /reports/institutional` | `admin,coordinator` |
| Académico por curso | `GET /reports/courses/{course}` | `admin,coordinator` + `CoursePolicy::view` |
| Desempeño por asignatura | `GET /reports/subjects/{subject}` | `admin,coordinator,teacher` + `SubjectPolicy::view` |
| Expediente de un estudiante | `GET /reports/students/{user}/transcript` | `admin,coordinator` |
| Mi expediente | `GET /student/transcript` | `admin,student` (el suyo) |

Añadir `?export=csv` a cualquiera de ellas descarga el reporte
(`text/csv; charset=UTF-8`, con BOM, `Content-Disposition: attachment`).

## 6. Contenido

- **Institucional**: KPIs de plataforma (cursos, asignaturas, estudiantes,
  docentes, actividades, calificaciones, recomendaciones abiertas, sesiones
  inmersivas, conversaciones IA, usuarios activos) + fila por curso activo con
  avance medio y promedio.
- **Curso**: por estudiante — progreso %, promedio %, actividades calificadas,
  contenidos completados, pendientes/vencidas, aprobado/no. Resumen del curso.
- **Asignatura**: matriz estudiante × actividad publicada con la nota de cada
  una y el promedio de la asignatura.
- **Expediente**: una fila por asignatura de cada curso inscrito con promedio y
  estado (aprobada / no superada / en curso). Promedio global.

## 7. Cómo probar

### Automático
```bash
php artisan test            # 160 pruebas (150 previas + 10 de Fase 10)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
1. **Coordinación** (`coordinacion@diskover.test`): *Reportes* (sidebar) →
   *Indicadores institucionales*, *Reporte académico por curso* (selector),
   *Reporte de desempeño por asignatura*. En cada uno, **Exportar CSV** descarga
   el fichero (ábrelo en Excel/Calc: acentos correctos).
2. **Docente** (`docente@diskover.test`): sólo ve *Reporte por asignatura* de las
   suyas; una asignatura ajena → **403**; institucional/curso → **403**.
3. **Estudiante** (`estudiante@diskover.test`): *Mi expediente*; el índice de
   reportes le da **403**; el expediente de otro → **403**.
4. Desde la ficha del curso (coordinación) o de la asignatura (docente) hay un
   botón *Reporte* directo; en la ficha de un estudiante, *Expediente*.

## 8. Resultado esperado

Cada rol accede a los reportes que le corresponden, los ve en pantalla y los
exporta a CSV correctamente formado. `php artisan test` → 160/160.

## 9. Checklist

- [x] Backend — reportes en `app/Services/Reports/` tras un contrato común; controlador delgado
- [x] Base de datos — sin cambios (sólo lecturas)
- [x] Frontend — vista genérica `reports/show` + índice; reutiliza `x-dashboard.stat-card` y `x-ui.card`
- [x] Validaciones — N/A (lecturas); acceso comprobado en backend
- [x] Seguridad — `role:` + `CoursePolicy`/`SubjectPolicy`; estudiante sólo su expediente
- [x] Responsive — tablas anchas con scroll horizontal
- [x] Exportación — CSV con BOM UTF-8, escape de comillas/separadores, descarga como adjunto
- [x] Pruebas — 10 nuevas (exportador, contenido de 3 reportes, control de acceso HTTP)
- [x] Organización — `app/Services/Reports/`, `app/Http/Controllers/Reports/`, `resources/views/reports/`; conforme a ADR-0013

## 10. Notas para la siguiente fase

- **Fase 11 (auditoría y seguridad avanzada)**: tabla de auditoría persistente
  (enganchada en los subscribers existentes), permisos avanzados, protección de
  datos, backups.
- Mejora futura: PDF/XLSX como método adicional de `ReportExporter`; `rows()`
  perezoso y/o precálculo nocturno para el reporte institucional a gran escala.
