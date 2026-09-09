# FASE 11 — Auditoría y seguridad avanzada

- **Fecha:** 2026-09-09
- **Estado:** ✅ Completada

## 1. Objetivo

Dejar un rastro persistente y consultable de "quién hizo qué y cuándo", afinar
el control de acceso con permisos concedidos directamente al usuario, endurecer
las respuestas HTTP, permitir la descarga de los datos personales y automatizar
las copias de seguridad de la base de datos. Cambios de esquema mínimos
(`audit_logs`, `permission_user`).

## 2. Decisión de arquitectura

- **ADR-0014** — Auditoría append-only (`AuditLogger` + trait `Auditable` +
  llamadas explícitas en los Services); pivote aditivo `permission_user` sin
  romper el contrato de `hasPermission()`; middleware `SecurityHeaders` sin CSP
  estricta; `PersonalDataReport` y comando `dsle:backup` con rotación.

## 3. Archivos creados

```
database/migrations/2026_09_09_130001_create_audit_logs_table.php
database/migrations/2026_09_09_130002_create_permission_user_table.php
app/Models/AuditLog.php
app/Models/Concerns/Auditable.php
app/Services/Security/AuditLogger.php
app/Services/Reports/AuditLogReport.php
app/Services/Reports/PersonalDataReport.php
app/Http/Controllers/Admin/AuditController.php
app/Http/Controllers/Student/DataExportController.php
app/Http/Middleware/SecurityHeaders.php
app/Console/Commands/BackupDatabase.php
resources/views/admin/audit/index.blade.php
tests/Unit/AuditLoggerTest.php
tests/Feature/Security/ModelAuditTest.php
tests/Feature/Security/AuthAuditTest.php
tests/Feature/Security/DirectPermissionsTest.php
tests/Feature/Security/AuditViewerTest.php
tests/Feature/Security/SecurityHeadersTest.php
tests/Feature/Security/BackupCommandTest.php
tests/Feature/Security/PersonalDataExportTest.php
docs/architecture/ADR-0014-auditoria-y-seguridad-avanzada.md
docs/fases/FASE-11-auditoria-y-seguridad-avanzada.md
```

## 4. Archivos modificados

```
config/dsle.php                                  # bloques audit / security / backups
bootstrap/app.php                                # SecurityHeaders en el grupo web
routes/web.php                                   # GET /admin/audit ; GET /student/my-data
routes/console.php                               # Schedule dsle:backup dailyAt 02:30
database/seeders/RolePermissionSeeder.php        # permiso audit.view (grupo security)
app/Models/User.php                              # trait Auditable
app/Models/Role.php, Course.php, Enrollment.php, ImmersiveExperience.php   # trait Auditable
app/Models/Concerns/HasRoles.php                 # directPermissions(), unión en permissionSlugs(), syncDirectPermissions()
app/Listeners/AuthEventSubscriber.php            # persiste los eventos de auth en audit_logs
app/Services/Security/UserService.php            # inyecta AuditLogger; permisos directos + auditoría
app/Services/Security/RoleService.php            # inyecta AuditLogger; audita cambios de permisos
app/Http/Requests/Admin/StoreUserRequest.php, UpdateUserRequest.php   # reglas direct_permissions
app/Http/Controllers/Admin/UserController.php    # permissionGroups en create()/edit()
resources/views/components/admin/user-form.blade.php   # fieldset "Permisos directos"
resources/views/components/navigation/sidebar.blade.php   # enlace "Auditoría" y "Descargar mis datos"
resources/css/components/tables.css              # .code-block (detalle JSON)
resources/css/utilities/helpers.css             # .u-mb-3
```

## 5. Eventos de auditoría

| Origen | Eventos |
|---|---|
| Trait `Auditable` (modelo) | `user.*`, `role.*`, `course.*`, `enrollment.*`, `immersiveexperience.*` con sufijo `created` / `updated` / `deleted` (+ diff `old`/`new`) |
| `UserService` / `RoleService` | `user.roles_assigned`, `user.roles_changed`, `user.permissions_changed`, `role.permissions_set`, `role.permissions_changed` |
| `AuthEventSubscriber` | `auth.login`, `auth.logout`, `auth.failed`, `auth.lockout`, `auth.password_reset` |
| `dsle:backup` | `backup.created` |

Claves sensibles (`password`, `*token*`, `secret`, `launch_token`, …) se sustituyen
por `••••` antes de guardar (`config('dsle.audit.redact')`).

## 6. Rutas y acceso

| Acción | Ruta | Acceso |
|---|---|---|
| Visor de auditoría | `GET /admin/audit` (`?export=csv`) | `permission:audit.view` (admin por `*`) |
| Descargar mis datos | `GET /student/my-data` | `role:admin,student` (sólo los propios) |

## 7. Configuración (`.env`)

```
DSLE_HSTS_MAX_AGE=31536000        # antigüedad de HSTS (sólo se envía sobre HTTPS)
DSLE_BACKUP_KEEP=7                # copias .sql.gz a conservar
DSLE_MYSQLDUMP_PATH=mysqldump     # ruta al binario mysqldump si no está en PATH
```

## 8. Cómo probar

### Automático
```bash
php artisan migrate:fresh --seed
php artisan test          # 187 pruebas (160 previas + 27 de Fase 11)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
1. **Auditoría** (`admin@diskover.test`): *Administración → Auditoría*. Inicia y
   cierra sesión, edita un usuario y sus roles/permisos, crea un curso: cada
   acción aparece con actor, IP y detalle JSON. Filtra por evento/actor/fechas;
   **Exportar CSV** descarga el registro.
2. **Permisos directos**: edita un usuario coordinador, marca *Ver auditoría* en
   "Permisos directos", guarda. Ese usuario ya entra a `/admin/audit`; se
   registra `user.permissions_changed`.
3. **Cabeceras**: `curl -I http://localhost:8000/login` muestra
   `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y
   `X-Permitted-Cross-Domain-Policies`; `Strict-Transport-Security` **no**
   aparece sobre HTTP.
4. **Mis datos** (`estudiante@diskover.test`): *Descargar mis datos* baja un CSV
   con cuenta, matrículas, calificaciones y actividad.
5. **Backup**: `php artisan dsle:backup` deja
   `storage/app/backups/dsle-*.sql.gz`; ejecútalo 8+ veces y comprueba que sólo
   quedan las 7 más recientes (con `mysqldump` en PATH o `DSLE_MYSQLDUMP_PATH`).

## 9. Resultado esperado

`audit_logs` recoge accesos y cambios sobre entidades sensibles, consultables y
exportables por quien tenga `audit.view`. Los permisos directos amplían el
acceso sin tocar roles ni Gates. Las respuestas web llevan las cabeceras de
seguridad base. Cualquiera puede descargar sus datos. `dsle:backup` produce y
rota copias comprimidas. `php artisan test` → 187/187.

## 10. Checklist

- [x] Backend — `AuditLogger` único punto de escritura; trait `Auditable`; Services auditan los pivotes; controladores delgados
- [x] Base de datos — `audit_logs` (append-only, índices por evento/actor/fecha) y `permission_user` (pivote aditivo)
- [x] Frontend — visor `admin/audit/index` con filtros y detalle JSON; "Permisos directos" en el formulario de usuario; enlaces de sidebar
- [x] Validaciones — `direct_permissions[]` con `exists:permissions,slug` en los Form Requests
- [x] Seguridad — permiso `audit.view`; middleware `SecurityHeaders` (HSTS sólo HTTPS); redacción de secretos; backup sin contraseña en línea de comandos; export de datos sólo propios
- [x] Responsive — tablas con scroll horizontal; `.code-block` acotado
- [x] Pruebas — 27 nuevas (redacción/registro, auditoría de modelo y de auth, permisos directos, visor HTTP + CSV, cabeceras, rotación de backup, export de datos)
- [x] Organización — `app/Services/Security/`, `app/Models/Concerns/`, `app/Http/Middleware/`, `app/Console/Commands/`, `tests/Feature/Security/`; conforme a ADR-0014

## 11. Notas para la siguiente fase

- **Fase 12**: pendiente según el prompt maestro.
- Mejoras futuras: retención/particionado y archivado de `audit_logs`; CSP con
  nonces; `rows()` perezoso en `AuditLogReport` para exportaciones muy grandes;
  cifrado en reposo de las copias de seguridad y subida a almacenamiento remoto.
