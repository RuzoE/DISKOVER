# FASE 12 — Pruebas e integración continua

- **Fecha:** 2026-09-09
- **Estado:** ✅ Completada

## 1. Objetivo

Consolidar la suite de pruebas heredada de las fases 1–11: eliminar la
duplicación del arranque de cada prueba, cubrir los huecos (middleware de
acceso, enums, reglas de Form Request, camino de fallo del comando de backup) y
añadir integración continua. Sin cambios en el comportamiento de la aplicación.

## 2. Decisión de arquitectura

- **ADR-0015** — Rasgo único `Tests\Concerns\InteractsWithRoles` (siembra RBAC
  vía el hook `setUpInteractsWithRoles` + atajos de usuario por rol); se mantiene
  PHPUnit (12) y su API de atributos; `dsle:backup` pasa a la fachada `Process`
  para poder simularse; CI con GitHub Actions.

## 3. Archivos creados

```
tests/Concerns/InteractsWithRoles.php
tests/Feature/Auth/AccessControlMiddlewareTest.php
tests/Feature/Admin/AdminValidationTest.php
tests/Feature/SmokeTest.php                     (reemplaza tests/Feature/ExampleTest.php)
tests/Unit/EnumTest.php
tests/README.md
.github/workflows/ci.yml
docs/architecture/ADR-0015-estrategia-de-pruebas.md
docs/fases/FASE-12-pruebas.md
```

## 4. Archivos modificados

```
app/Console/Commands/BackupDatabase.php   # Illuminate\Process + --result-file + gzip en PHP (simulable)
tests/Feature/Security/BackupCommandTest.php   # + fallo de proceso, guardas, forma del comando (7 pruebas)
composer.json                             # scripts lint / check / test:coverage
.gitignore                                # /build
README.md, docs/README.md                 # tablas de fases + lista de ADR

# 35 clases de prueba: se elimina su `setUp()` de siembra y se añade
#   `use InteractsWithRoles;` (Academic, Admin, AI, Analytics, Assessment,
#   Dashboard, Immersive, Recommendations, Reports, Security, Tracking)

# Eliminados: tests/Unit/ExampleTest.php  (assertTrue(true), sin valor)
```

## 5. Andamiaje `InteractsWithRoles`

```php
class MiPruebaTest extends TestCase
{
    use InteractsWithRoles;   // siembra RolePermissionSeeder antes de cada prueba
    use RefreshDatabase;

    public function test_x(): void
    {
        $admin       = $this->adminUser();                  // + coordinatorUser / teacherUser / studentUser
        $coordinador = $this->roleUser('coordinator', ['name' => 'Ada']);
        $docente     = $this->actingAsRole(RoleSlug::Teacher);   // crea y autentica
    }
}
```

El hook `setUpInteractsWithRoles()` lo invoca Laravel automáticamente (mismo
mecanismo de `setUpTraits()` que usa `RefreshDatabase`). Las pruebas nuevas ya
no declaran `setUp()` ni llaman a `seed(RolePermissionSeeder::class)`.

## 6. Cobertura añadida

| Prueba | Qué cubre |
|---|---|
| `AccessControlMiddlewareTest` | `active` expulsa a cuentas suspendidas/inactivas y deja pasar a las activas; `role` bloquea sin rol, permite con rol y siempre al admin; invitado → login |
| `AdminValidationTest` | email único, contraseña confirmada, slug de rol/permiso inexistente, slug de rol de sistema inmutable, actualización conservando el propio email |
| `EnumTest` | toda variante de los enums de dominio tiene etiqueta; `options()` cubre exactamente las variantes; `RoleSlug` = los 4 roles de sistema |
| `BackupCommandTest` | conexión no-MySQL, `mysqldump` con error, fichero de volcado ausente, forma del comando (`--single-transaction`, `--result-file`), rotación (conserva N, no-op con 0) |
| `SmokeTest` | raíz redirige, dashboard exige auth, login renderiza, `/up` responde |

## 7. Integración continua

`.github/workflows/ci.yml` en cada `push` / `pull_request` a `main`:

1. Servicio MySQL 8 + `diskover_test`.
2. PHP 8.4 (PCOV), Node 20, `composer install`, `npm ci && npm run build`.
3. `vendor/bin/pint --test` (estilo).
4. `php artisan test --coverage`.

Scripts de Composer: `composer lint`, `composer check` (lint + test),
`composer test:coverage` (`--min=70`, opt-in local con driver de cobertura).

## 8. Cómo probar

```bash
composer test        # 223 pruebas
composer lint        # pint --test, sin cambios
composer check       # lint + test
```

## 9. Resultado esperado

`php artisan test` → **223/223** (187 previas + 36 nuevas). `vendor/bin/pint
--test` sin hallazgos. La suite ya no repite el arranque de RBAC en 35 ficheros.
El workflow de CI queda listo para ejecutarse al hacer push.

## 10. Checklist

- [x] **Backend** — `dsle:backup` migrado a la fachada `Process` (simulable) sin cambiar su contrato ni su salida
- [x] **Base de datos** — sin cambios de esquema; `diskover_test` sigue siendo la BD de pruebas
- [x] **Frontend** — sin cambios
- [x] **Validaciones** — reglas de los Form Requests de usuarios y roles cubiertas por casos de rechazo
- [x] **Seguridad** — middleware `active` y `role` con pruebas de aislamiento; procesos externos siempre simulados
- [x] **Responsive** — N/A
- [x] **Pruebas** — andamiaje único `InteractsWithRoles`; 223 pruebas; huecos cubiertos; `ExampleTest` retirado
- [x] **Organización** — `tests/Concerns/`, un directorio de `Feature` por módulo; `tests/README.md`; CI en `.github/workflows/`; conforme a ADR-0015

## 11. Notas para la siguiente fase

- **Fase 13 (DevOps)**: despliegue, contenedores, `.env` de producción, healthchecks,
  ejecución del `schedule` (`dsle:backup`), y —si se añade driver de cobertura en
  CI— exigir el umbral del 70 % con `--min=70`.
- Quedan ≈8 clases con helpers privados (`admin()`, `coordinator()`…) que
  duplican a medias los del rasgo; migrar al tocarlas.
