# ADR-0014 — Auditoría, permisos directos, cabeceras de seguridad y backups

- **Estado:** Aceptado
- **Fecha:** 2026-09-09
- **Contexto de decisión:** Fase 11
- **Relacionado con:** ADR-0003 (RBAC propio), ADR-0007 (eventos de aprendizaje),
  ADR-0013 (módulo de reportes), sección 11 del prompt maestro

## Contexto

Las fases anteriores ya emiten eventos de autenticación y de aprendizaje, pero
no dejan un **rastro persistente y consultable** de "quién hizo qué y cuándo"
sobre las entidades sensibles. La Fase 11 pide: registro de auditoría, permisos
más finos, endurecimiento HTTP, portabilidad de datos personales y copias de
seguridad.

## Decisión

### 1. Registro de auditoría append-only (`audit_logs`)

Una única tabla, **sólo se inserta y se lee** (`AuditLog::UPDATED_AT = null`;
la aplicación nunca hace `UPDATE`/`DELETE`). Columnas: `user_id` (actor, nulo si
sistema/anónimo), `event` (p. ej. `role.updated`, `auth.login`), morph
`auditable_type` + `auditable_id`, `description`, `properties` (JSON con el
diff), `ip_address`, `user_agent`, `created_at` (indexado).

- **`App\Services\Security\AuditLogger`** es el único punto de escritura.
  Redacta claves sensibles (`password`, `*token*`, `secret`, `launch_token`…)
  antes de persistir (`config('dsle.audit.redact')`).
- **`App\Models\Concerns\Auditable`** (trait de modelo): engancha
  `created/updated/deleted` y registra el diff. Se aplica sólo a entidades
  sensibles: `User`, `Role`, `Course`, `Enrollment`, `ImmersiveExperience`.
- Las operaciones sobre **pivotes** (roles ↔ permisos, permisos directos) no
  disparan eventos de modelo, así que los **Services** (`UserService`,
  `RoleService`) llaman a `AuditLogger` explícitamente
  (`role.permissions_changed`, `user.permissions_changed`, …).
- **Autenticación**: `AuthEventSubscriber` (ya existente) pasa a persistir
  `auth.login` / `logout` / `failed` / `lockout` / `password_reset` además de
  escribir en el log de la aplicación.

El **rastro pedagógico** (`learning_events`, ADR-0007) es independiente y no se
mezcla con la auditoría: distinta finalidad, distinta audiencia y distinta
retención.

### 2. Permisos concedidos directamente al usuario

Pivote **aditivo** `permission_user`. `HasRoles::permissionSlugs()` une los
permisos que llegan por rol con los directos; `hasPermission()` no cambia su
contrato, de modo que **todos los `Gate` por slug y los middleware `permission:`
existentes siguen funcionando** sin tocarlos. `syncDirectPermissions()` los
reemplaza a partir de sus slugs. Se editan desde el formulario de usuario
(sección "Permisos directos", agrupada por `group`).

### 3. Visor de auditoría y nuevo permiso `audit.view`

`GET /admin/audit` (`permission:audit.view`) — sólo lectura, con filtros
(evento, actor, rango de fechas, texto) y `?export=csv` reutilizando el
`ReportExporter` de la Fase 10 vía un `AuditLogReport` que implementa el
contrato `Report`. Es el **único permiso nuevo**; el rol `admin` lo obtiene por
su comodín `*`.

### 4. Cabeceras de seguridad HTTP (`SecurityHeaders`)

Middleware añadido al grupo `web`: `X-Content-Type-Options: nosniff`,
`X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`,
`X-Permitted-Cross-Domain-Policies: none` y `Strict-Transport-Security`
**sólo cuando la petición es HTTPS** (`config('dsle.security.hsts_max_age')`).

**No se define una CSP estricta**: el panel usa manejadores en línea y las
experiencias inmersivas cargan un iframe de Unity de terceros; una CSP
`script-src 'self'` los rompería. Queda como mejora futura con nonces.

### 5. Portabilidad de datos personales

`GET /student/my-data` → `PersonalDataReport` (implementa `Report`) descargado
en CSV con `ReportExporter`. Reúne cuenta, roles, permisos directos, matrículas,
calificaciones y contadores de actividad de **la persona autenticada**; nadie
puede exportar los datos de otra.

### 6. Copias de seguridad (`dsle:backup`)

Comando de consola: `mysqldump --single-transaction` de la conexión `mysql`,
comprimido con `gzip` a `storage/app/backups/dsle-AAAAMMDD-HHMMSS.sql.gz`, con
**rotación** (se conservan `config('dsle.backups.keep')`, por defecto 7). La
contraseña se pasa por `MYSQL_PWD` en el entorno del proceso, nunca en la línea
de comandos. Ruta de `mysqldump` configurable con `DSLE_MYSQLDUMP_PATH`.
Programado a diario (`routes/console.php`, `dailyAt('02:30')`,
`withoutOverlapping`, `onOneServer`).

## Consecuencias

- Toda alta/cambio/baja de entidad sensible y todo evento de acceso queda en
  `audit_logs`, consultable y exportable, sin acoplar los modelos a un servicio
  concreto (el trait resuelve `AuditLogger` del contenedor).
- `audit_logs` **crece sin límite**. Retención/particionado y archivado quedan
  para la fase de DevOps; el visor limita a 5 000 filas por exportación y pagina
  la vista.
- Los permisos directos añaden una segunda dimensión de configuración; se
  auditan siempre que cambian para no perder trazabilidad.
- Sin CSP estricta el XSS reflejado no queda mitigado por cabecera; se compensa
  con el escape por defecto de Blade y la validación por Form Requests.
- El backup depende de `mysqldump` disponible en el host; el comando falla de
  forma explícita (código 1, sin dejar ficheros a medias) si no lo está o si el
  volcado sale vacío.

## Alternativas descartadas

- **`spatie/laravel-activitylog` / `spatie/laravel-permission`**: contradice
  ADR-0003 (RBAC propio) y añade dependencias; nuestro modelo ya cubre el caso.
- **Reutilizar `learning_events` como auditoría**: mezcla dos dominios con
  finalidad, formato y retención distintos.
- **CSP estricta con `report-only`**: útil, pero sin nonces en las vistas actuales
  genera ruido sin bloquear nada; se pospone.
- **Backups vía paquete (`spatie/laravel-backup`)**: potente pero pesado para el
  requisito actual (un volcado comprimido y rotado de una sola base de datos).
- **Permisos directos como roles "de un permiso"**: ensucia el catálogo de roles
  y complica la UI; el pivote aditivo es más simple.
