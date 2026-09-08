# FASE 1 — Autenticación y seguridad

- **Fecha:** 2026-09-08
- **Estado:** ✅ Completada

## 1. Objetivo

Login, logout y recuperación de contraseña con los mecanismos de primera parte de
Laravel; gestión de usuarios; RBAC propio (roles + permisos); middlewares, policies
y registro de eventos de autenticación. Base visual reutilizable (componentes Blade
+ sistema de diseño CSS).

## 2. Decisiones de arquitectura

- **ADR-0003** — RBAC propio (tablas `roles`/`permissions`/pivotes) en lugar de
  `spatie/laravel-permission`.
- **ADR-0004** — CSS modular propio; se retira Tailwind del proyecto.

## 3. Archivos creados

### Base de datos
```
database/migrations/2026_09_08_100001_create_roles_table.php
database/migrations/2026_09_08_100002_create_permissions_table.php
database/migrations/2026_09_08_100003_create_permission_role_table.php
database/migrations/2026_09_08_100004_create_role_user_table.php
database/migrations/2026_09_08_100005_add_profile_fields_to_users_table.php   (users.status, users.last_login_at)
database/factories/RoleFactory.php
database/factories/PermissionFactory.php
database/seeders/RolePermissionSeeder.php
```

### Dominio / seguridad
```
app/Enums/UserStatus.php
app/Enums/RoleSlug.php
app/Models/Role.php
app/Models/Permission.php
app/Models/Concerns/HasRoles.php
app/Services/Security/UserService.php
app/Services/Security/RoleService.php
app/Policies/UserPolicy.php
app/Policies/RolePolicy.php
app/Providers/AuthServiceProvider.php
app/Listeners/AuthEventSubscriber.php
config/dsle.php
```

### HTTP
```
app/Http/Middleware/EnsureUserHasRole.php          (alias: role)
app/Http/Middleware/EnsureUserHasPermission.php    (alias: permission)
app/Http/Middleware/EnsureAccountIsActive.php      (alias: active)
app/Http/Requests/Auth/LoginRequest.php
app/Http/Requests/Auth/PasswordResetLinkRequest.php
app/Http/Requests/Auth/NewPasswordRequest.php
app/Http/Requests/Admin/StoreUserRequest.php
app/Http/Requests/Admin/UpdateUserRequest.php
app/Http/Requests/Admin/StoreRoleRequest.php
app/Http/Requests/Admin/UpdateRoleRequest.php
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Auth/PasswordResetLinkController.php
app/Http/Controllers/Auth/NewPasswordController.php
app/Http/Controllers/DashboardController.php
app/Http/Controllers/Admin/UserController.php
app/Http/Controllers/Admin/RoleController.php
app/Http/Controllers/Admin/PermissionController.php
```

### Vistas y componentes
```
resources/views/layouts/app.blade.php
resources/views/layouts/guest.blade.php
resources/views/components/ui/{button,input,select,label,alert,badge,card}.blade.php
resources/views/components/navigation/navbar.blade.php
resources/views/components/tables/empty-state.blade.php
resources/views/components/admin/{user-form,role-form}.blade.php
resources/views/auth/{login,forgot-password,reset-password}.blade.php
resources/views/dashboard.blade.php
resources/views/admin/users/{index,create,edit,show}.blade.php
resources/views/admin/roles/{index,create,edit,show}.blade.php
resources/views/admin/permissions/index.blade.php
resources/views/vendor/pagination/dsle.blade.php
```

### CSS / JS
```
resources/css/app.css   (reescrito: solo @import)
resources/css/base/{tokens,reset,typography}.css
resources/css/layouts/{app,guest}.css
resources/css/components/{buttons,forms,alerts,badges,cards,tables,pagination}.css
resources/css/pages/admin/users.css
resources/css/utilities/helpers.css
resources/js/utils/confirm.js
```

### Idiomas
```
lang/es/{auth,passwords,validation}.php
lang/en/*  (php artisan lang:publish — fallback)
```

### Pruebas
```
tests/Feature/Auth/AuthenticationTest.php
tests/Feature/Auth/PasswordResetTest.php
tests/Feature/Admin/UserManagementTest.php
tests/Feature/Admin/RoleManagementTest.php
tests/Unit/HasRolesTest.php
```

### Documentación
```
docs/architecture/ADR-0003-rbac-propio.md
docs/architecture/ADR-0004-css-modular-sin-framework-utilidades.md
docs/fases/FASE-1-autenticacion-y-seguridad.md
```

## 4. Archivos modificados

```
bootstrap/app.php            # aliases role/permission/active; redirects guest/user; api ya estaba
bootstrap/providers.php      # registra AuthServiceProvider
app/Providers/AppServiceProvider.php   # Event::subscribe(AuthEventSubscriber), vista de paginación
app/Models/User.php          # trait HasRoles, casts status/last_login_at, fillable status, isActive()
app/Http/Controllers/Controller.php    # traits AuthorizesRequests + ValidatesRequests
routes/web.php               # rutas de auth, dashboard y admin (users/roles/permissions)
database/factories/UserFactory.php     # status + states suspended/inactive/withRole
database/seeders/DatabaseSeeder.php    # RolePermissionSeeder + admin + usuarios demo
vite.config.js               # sin plugin tailwindcss
package.json                 # sin tailwindcss / @tailwindcss/vite
resources/css/app.css        # imports del sistema de diseño
resources/js/app.js          # init de confirm.js
phpunit.xml                  # DB de pruebas: mysql / diskover_test (no hay pdo_sqlite en el entorno)
.env / .env.example          # DSLE_ADMIN_*, DSLE_MAX_LOGIN_ATTEMPTS
```

## 5. Comandos ejecutados

```bash
composer dump-autoload
php artisan lang:publish
php artisan migrate:fresh --seed
php artisan test            # 25 pruebas, 63 aserciones — OK
npm install && npm run build
vendor/bin/pint --dirty
# BD de pruebas (una vez):
#   CREATE DATABASE diskover_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 6. Modelo de datos (RBAC)

- `roles(id, slug*, name, description, is_system, timestamps)`
- `permissions(id, slug*, name, group, description, timestamps)`
- `permission_role(permission_id, role_id)` — PK compuesta, FK cascade
- `role_user(role_id, user_id)` — PK compuesta, FK cascade
- `users` + `status`, `last_login_at`

Roles de sistema sembrados: `admin` (todos los permisos), `coordinator`
(`users.view`, `roles.view`), `teacher`, `student` (sin permisos de administración).
Permisos sembrados: `users.{view,create,update,delete}`, `roles.{view,create,update,delete}`.

## 7. Cómo probar

### Automático
```bash
php artisan test
```

### Manual
```bash
php artisan migrate:fresh --seed
php artisan serve
```
1. Ir a `/` → redirige a `/login`.
2. Entrar con `admin@diskover.test` / `password` → `/dashboard` con nombre, estado, roles y último acceso.
3. `Usuarios`: listar, filtrar por texto/estado, crear (con roles), editar, ver detalle, eliminar.
   - Intentar eliminar la propia cuenta → 403.
4. `Roles`: crear un rol personalizado con permisos, editarlo, eliminarlo.
   - Intentar eliminar un rol de sistema → 403.
5. `Permisos`: catálogo de solo lectura agrupado.
6. Cerrar sesión.
7. `/forgot-password` con un correo válido → el enlace aparece en `storage/logs/laravel.log` (MAIL_MAILER=log). Abrirlo y fijar nueva contraseña.
8. Suspender un usuario y comprobar que no puede iniciar sesión.
9. Fuerza 5+ intentos fallidos seguidos → mensaje de bloqueo temporal (rate limiting).
10. `storage/logs/laravel.log` registra `auth.login`, `auth.logout`, `auth.failed`, `auth.lockout`, `auth.password_reset`.

## 8. Resultado esperado

- Login/logout y recuperación de contraseña funcionales con UI propia y responsive.
- Panel `/admin/users` y `/admin/roles` operativos, protegidos por middleware
  `permission:` + policies. Usuarios sin permiso reciben 403.
- `users.last_login_at` se actualiza en cada acceso.
- `php artisan test` → 25/25 en verde.

## 9. Checklist

- [x] Backend — controladores delgados, lógica en `Services/Security`, RBAC vía Gate/Policies
- [x] Base de datos — 5 migraciones, integridad referencial, índices, seeder idempotente
- [x] Frontend — layouts, componentes Blade reutilizables, sistema de diseño CSS por módulo
- [x] Validaciones — Form Requests para auth y administración
- [x] Seguridad — hashing (cast), CSRF, rate limiting, throttling de login, cuentas no activas bloqueadas, mass-assignment acotado, autorización en backend
- [x] Responsive — navbar y formularios adaptados a móvil; tablas con scroll horizontal
- [x] Pruebas — 25 pruebas (auth, reset, gestión de usuarios/roles, trait HasRoles)
- [x] Organización — estructura conforme a ADR-0002/0003/0004; documentación al día

## 10. Notas / pendiente para la siguiente fase

- La auditoría persistente (tabla) llega en la **Fase 11**; hoy los eventos van al
  log vía `AuthEventSubscriber` (único punto de enganche futuro).
- Los dashboards por rol llegan en la **Fase 5**; `dashboard.blade.php` es provisional.
- `permissions` es un catálogo fijo del sistema (no editable por UI); nuevos permisos
  se añaden en el `RolePermissionSeeder` en la fase que los introduzca.
- Entorno sin `pdo_sqlite`: las pruebas usan la BD MySQL `diskover_test`.
- Recordatorio: cambiar `DSLE_ADMIN_PASSWORD` en cualquier entorno real.
