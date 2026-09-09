# ADR-0015 — Estrategia de pruebas e integración continua

- **Estado:** Aceptado
- **Fecha:** 2026-09-09
- **Contexto de decisión:** Fase 12
- **Relacionado con:** ADR-0003 (RBAC propio), todas las fases anteriores
  (cada una añadió sus pruebas), sección 12 del prompt maestro

## Contexto

Las fases 1–11 dejaron 187 pruebas, pero con **duplicación** (≈35 clases
repetían el mismo `setUp()` de siembra de RBAC), **huecos** (los middleware
`active` y `role`, los enums y varias reglas de Form Request no se ejercían de
forma aislada) y **sin CI**. La Fase 12 consolida la suite sin cambiar el
comportamiento de la aplicación.

## Decisión

### 1. Un único andamiaje para roles y permisos

`Tests\Concerns\InteractsWithRoles` centraliza:

- la siembra de `RolePermissionSeeder` antes de cada prueba, mediante el hook
  `setUpInteractsWithRoles()` que Laravel invoca automáticamente (el mismo
  mecanismo de `setUpTraits()` que usa `RefreshDatabase`);
- atajos `adminUser()` / `coordinatorUser()` / `teacherUser()` / `studentUser()`,
  `roleUser(slug, attrs)` y `actingAsRole(slug)`.

Las 35 clases que tenían `protected function setUp()` sólo para sembrar RBAC
pasan a `use InteractsWithRoles;` y pierden ese método. No se toca la lógica de
cada prueba.

### 2. Se mantiene PHPUnit (no se adopta Pest)

El proyecto ya está íntegramente en PHPUnit; migrar a Pest sería trabajo sin
retorno funcional. Se fija PHPUnit 12 y se adopta su API de atributos
(`#[DataProvider]`), ya que las anotaciones dejaron de funcionar.

### 3. Cobertura de huecos

Se añaden pruebas para lo que no se probaba en aislamiento:

| Área | Pruebas |
|---|---|
| Middleware `active` / `role` | `tests/Feature/Auth/AccessControlMiddlewareTest` |
| Reglas de Form Request (usuarios y roles) | `tests/Feature/Admin/AdminValidationTest` |
| Enums de dominio (etiqueta y `options()`) | `tests/Unit/EnumTest` |
| Comando `dsle:backup` (fallo, guardas, forma del comando) | `tests/Feature/Security/BackupCommandTest` |
| Arranque y enrutado base | `tests/Feature/SmokeTest` |

### 4. `dsle:backup` pasa a `Illuminate\Support\Facades\Process`

Para poder probar el comando sin un `mysqldump` real se sustituye
`Symfony\Component\Process` por la fachada `Process` de Laravel (simulable con
`Process::fake()`), se usa la opción nativa `--result-file` en lugar de una
tubería de shell y **la compresión gzip se hace en PHP** (`gzencode`), lo que
además elimina la dependencia del binario `gzip` y funciona en Windows.

### 5. Integración continua

`.github/workflows/ci.yml` en cada `push`/`pull_request` a `main`:
servicio MySQL 8, PHP 8.4 con PCOV, `npm ci && npm run build`, `vendor/bin/pint
--test` y `php artisan test --coverage`. Scripts de Composer nuevos: `lint`,
`check` (lint + test) y `test:coverage` (`--min=70`, opt-in local mientras no se
mida en CI).

## Consecuencias

- Una prueba nueva que necesita roles: `use InteractsWithRoles;` y nada más;
  cero `setUp()` repetido.
- La suite pasa de 187 a 223 pruebas; sube de 4 a 6 directorios de `Feature`
  con cobertura explícita de middleware y validación.
- El objetivo de cobertura (70 %) se documenta y se puede exigir en CI en cuanto
  se ejecute con driver de cobertura; hoy CI la publica sin bloquear.
- Quedan ≈8 clases con helpers privados (`admin()`, `coordinator()`…) que
  duplican a medias los del rasgo; se migrarán al tocarlas, no en bloque, para
  no arriesgar regresiones.

## Alternativas descartadas

- **Migrar a Pest**: coste alto, beneficio nulo sobre una suite PHPUnit sana.
- **Sembrar RBAC globalmente en `Tests\TestCase`**: rompería las pruebas que
  crean sus propios roles con slugs del sistema (`HasRolesTest`), por la
  restricción `unique` de `roles.slug`. El rasgo opt-in lo evita.
- **Base de datos SQLite en memoria para las pruebas**: el entorno no tiene
  `pdo_sqlite`; además MySQL en las pruebas detecta incompatibilidades reales de
  SQL. Se mantiene `diskover_test`.
- **Refactor en bloque de todos los helpers privados**: riesgo de regresión
  desproporcionado para la fase.
