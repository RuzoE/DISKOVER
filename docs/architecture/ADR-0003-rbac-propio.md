# ADR-0003 — Control de acceso basado en roles (RBAC) propio

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 1
- **Relacionado con:** ADR-0001

## Contexto

DSLE necesita roles (administrador, coordinación, docente, estudiante y roles
personalizados) y permisos granulares. Laravel no trae RBAC en el núcleo; las
opciones son un paquete (de facto `spatie/laravel-permission`) o una
implementación propia sobre Eloquent + Gate.

## Decisión

Se implementa un **RBAC propio y mínimo**:

- Tablas: `roles`, `permissions`, `permission_role`, `role_user`.
- `users.status` (`active` | `inactive` | `suspended`) para el ciclo de vida de la cuenta.
- Trait `App\Models\Concerns\HasRoles` en `User`: `hasRole()`, `hasAnyRole()`,
  `hasPermission()`, `syncRoles()`, `assignRole()`.
- Los permisos se conceden **solo a través de roles** (no hay permisos directos a
  usuario; se reevaluará en la Fase 11).
- Autorización mediante los mecanismos de Laravel:
  - `Gate::before` concede todo al rol *super* (`admin`, configurable en
    `config/dsle.php`).
  - Policies (`UserPolicy`, `RolePolicy`) para autorización por modelo.
  - Middlewares `role:` y `permission:` para proteger rutas.
  - Un `Gate` por cada permiso persistido (para usar `@can('users.view')`), con la
    lista de slugs cacheada 1 h e invalidada por el seeder.
- Salvaguardas duras (no dependen del rol): un usuario no puede eliminar su
  propia cuenta; los roles `is_system` no se pueden eliminar ni renombrar su slug.
  Se aplican en controlador **y** en el service.

## Consecuencias

**Positivas**
- Cero dependencias externas; control total del esquema (útil para la auditoría
  de la Fase 11).
- Esquema simple y explícito, fácil de testear.

**Negativas / riesgos**
- Reimplementamos utilidades que el paquete ya resuelve (cache de permisos por
  usuario, permisos directos, equipos/tenancy). Si en el futuro se necesitan esas
  funciones, se valorará migrar a `spatie/laravel-permission` con un ADR nuevo.
- `Gate::before` enmascara las policies para el admin: las reglas que deban
  aplicarse **también** al admin se implementan como `abort_if` explícitos.

## Alternativas descartadas

- **spatie/laravel-permission:** excelente paquete, pero para el alcance actual
  (roles simples vía pivote) añade una dependencia y convenciones propias que no
  necesitamos todavía. La puerta queda abierta a adoptarlo más adelante.
