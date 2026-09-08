# FASE 2 — Gestión académica

- **Fecha:** 2026-09-08
- **Estado:** ✅ Completada

## 1. Objetivo

Cursos, asignaturas, inscripciones y contenidos. Gestión por coordinación/administración
(estructura académica), por el docente (contenidos de sus asignaturas) y consumo por el
estudiante (cursos y asignaturas en los que está inscrito).

## 2. Decisiones de arquitectura

- **ADR-0005** — Modelo de dominio académico: `Course 1─N Subject 1─N Content`,
  `Enrollment` a nivel de curso, áreas protegidas por `role:` + policies por permiso.

## 3. Archivos creados

### Base de datos
```
database/migrations/2026_09_08_110001_create_courses_table.php
database/migrations/2026_09_08_110002_create_subjects_table.php
database/migrations/2026_09_08_110003_create_enrollments_table.php
database/migrations/2026_09_08_110004_create_contents_table.php
database/factories/{Course,Subject,Enrollment,Content}Factory.php
database/seeders/DemoAcademicSeeder.php
```

### Dominio
```
app/Enums/AcademicStatus.php      (sustituye a la idea de CourseStatus; la usan Course y Subject)
app/Enums/EnrollmentStatus.php
app/Enums/ContentType.php
app/Models/{Course,Subject,Enrollment,Content}.php
app/Rules/IsTeacher.php
app/Services/Academic/{Course,Subject,Enrollment,Content}Service.php
app/Policies/{Course,Subject,Enrollment,Content}Policy.php
```

### HTTP
```
app/Http/Requests/Academic/StoreCourseRequest.php      UpdateCourseRequest.php
app/Http/Requests/Academic/StoreSubjectRequest.php     UpdateSubjectRequest.php
app/Http/Requests/Academic/StoreEnrollmentRequest.php  UpdateEnrollmentRequest.php
app/Http/Requests/Academic/StoreContentRequest.php     UpdateContentRequest.php
app/Http/Controllers/Coordinator/{Course,Subject,Enrollment}Controller.php
app/Http/Controllers/Teacher/{Subject,Content}Controller.php
app/Http/Controllers/Student/{Course,Subject}Controller.php
```

### Vistas y componentes
```
resources/views/components/ui/textarea.blade.php
resources/views/components/academic/{course-form,subject-form,content-form}.blade.php
resources/views/coordinator/courses/{index,create,edit,show}.blade.php
resources/views/coordinator/subjects/{index,create,edit,show}.blade.php
resources/views/coordinator/enrollments/index.blade.php
resources/views/teacher/subjects/{index,show}.blade.php
resources/views/teacher/contents/{index,create,edit}.blade.php
resources/views/student/courses/{index,show}.blade.php
resources/views/student/subjects/show.blade.php
```

### CSS / JS
```
resources/css/components/content.css
resources/css/pages/coordinator/courses.css
resources/css/pages/teacher/subjects.css
resources/css/pages/student/courses.css
resources/js/modules/teacher/content-form.js
```

### Pruebas
```
tests/Feature/Academic/CourseManagementTest.php
tests/Feature/Academic/SubjectManagementTest.php
tests/Feature/Academic/EnrollmentTest.php
tests/Feature/Academic/ContentManagementTest.php
tests/Feature/Academic/StudentLearningTest.php
```

### Documentación
```
docs/architecture/ADR-0005-modelo-de-dominio-academico.md
docs/fases/FASE-2-gestion-academica.md
```

## 4. Archivos modificados

```
app/Models/User.php                     # relaciones enrollments/enrolledCourses/subjectsTeaching, isEnrolledIn()
app/Providers/AuthServiceProvider.php   # registra Course/Subject/Enrollment/Content policies
routes/web.php                          # grupos coordinator/ (role:admin,coordinator), teacher/, student/
database/seeders/RolePermissionSeeder.php  # permisos grupo "academic"; coordinator y teacher los reciben
database/seeders/DatabaseSeeder.php     # llama a DemoAcademicSeeder fuera de producción
resources/views/components/navigation/navbar.blade.php  # enlaces Cursos / Mis asignaturas / Mis cursos según rol
resources/views/dashboard.blade.php     # tarjeta de accesos rápidos
resources/css/app.css                   # imports de content.css y páginas nuevas
resources/js/app.js                     # init de content-form.js
```

## 5. Rutas (web)

```
coordinator/courses            (resource)                     role:admin,coordinator
coordinator/courses/{course}/subjects      (resource, shallow)
coordinator/subjects/{subject}             (show,edit,update,destroy)
coordinator/courses/{course}/enrollments   (index, store)
coordinator/enrollments/{enrollment}       (update, destroy)
teacher/subjects               (index, show)                  role:admin,teacher
teacher/subjects/{subject}/contents        (index, create, store)
teacher/contents/{content}                 (edit, update, destroy)
student/courses                (index, show)                  role:admin,student
student/subjects/{subject}      (show)
```

## 6. Modelo de permisos añadido

Grupo `academic`: `courses.manage`, `subjects.manage`, `enrollments.manage`, `contents.manage`.
- coordinator: `courses.manage`, `subjects.manage`, `enrollments.manage` (+ los de Fase 1)
- teacher: `contents.manage` (+ titularidad de la asignatura, comprobada en la policy)
- student: ninguno (acceso por inscripción)

## 7. Cómo probar

### Automático
```bash
php artisan test            # 46 pruebas (25 previas + 21 de Fase 2)
```

### Manual
```bash
php artisan migrate:fresh --seed
php artisan serve
```
Usuarios demo (contraseña `password`): `coordinacion@`, `docente@`, `estudiante@diskover.test`.
El seeder crea el curso **DSLE-101** con una asignatura asignada al docente demo,
dos contenidos y al estudiante demo inscrito.

1. **Coordinación** → *Cursos*: crear curso, añadir asignaturas (asignar docente),
   abrir *Inscripciones* de un curso y matricular/retirar estudiantes.
   - Código de curso/asignatura duplicado → error de validación.
   - Asignar como docente a alguien sin rol docente → error.
2. **Docente** → *Mis asignaturas*: ver solo las propias; *Contenidos*: crear texto
   o enlace (el formulario muestra el campo según el tipo; sin JS ambos son válidos),
   publicar/despublicar, editar, eliminar.
   - Intentar abrir contenidos de una asignatura ajena → 403.
3. **Estudiante** → *Mis cursos*: ver solo los inscritos; entrar y abrir una
   asignatura activa → solo aparece el contenido publicado.
   - Abrir un curso no inscrito → 403. Abrir una asignatura en borrador → 404.
4. Cada rol recibe **403** al entrar en el área de otro (`/coordinator`, `/teacher`).

## 8. Resultado esperado

- Coordinación gestiona toda la estructura académica; el docente solo sus contenidos;
  el estudiante solo consume lo que le corresponde.
- Borrar un curso elimina en cascada sus asignaturas, contenidos e inscripciones
  (confirmación en la UI).
- `php artisan test` → 46/46.

## 9. Checklist

- [x] Backend — 3 áreas de controladores delgados, lógica en `Services/Academic`
- [x] Base de datos — 4 tablas, FK con `cascade`/`nullOnDelete`, índices y unicidad (`code`, `course+student`)
- [x] Frontend — vistas por rol, formularios reutilizables (`x-academic.*`), CSS modular
- [x] Validaciones — 8 Form Requests + regla `IsTeacher` + coherencia `type`↔`url`/`body`
- [x] Seguridad — `role:` por área + policies por permiso + titularidad del docente + filtrado por inscripción del estudiante
- [x] Responsive — tablas con scroll, grid de tarjetas para el estudiante
- [x] Pruebas — 21 nuevas (cursos, asignaturas, inscripciones, contenidos, acceso del estudiante)
- [x] Organización — conforme a ADR-0002/0005; documentación al día

## 10. Notas para la siguiente fase

- **Fase 3 (actividades y evaluaciones)** colgará de `Subject`: `Activity`, `Evaluation`,
  `Question`, `Attempt`, `Grade`.
- El seguimiento del aprendizaje (Fase 4) usará `Enrollment` + resultados de Fase 3.
- Aún no hay reordenación drag-and-drop de asignaturas/contenidos; el campo `position`
  ya existe para soportarlo cuando se aborde en UI.
