# Pruebas — DSLE

Suite basada en **PHPUnit 12** sobre `Tests\TestCase`. Las pruebas de
integración usan `RefreshDatabase` contra **MySQL `diskover_test`** (el entorno
no tiene `pdo_sqlite`; ver `phpunit.xml`).

## Ejecutar

```bash
composer test               # config:clear + php artisan test
composer lint               # vendor/bin/pint --test (sólo comprueba, no corrige)
composer check              # lint + test
composer test:coverage      # requiere Xdebug o PCOV; falla si la cobertura < 70 %

php artisan test --filter=AuditViewerTest
php artisan test tests/Feature/Security
```

## Organización

| Carpeta | Contenido |
|---|---|
| `tests/Unit` | Lógica pura sin framework (enums, redacción del `AuditLogger`, distribución de notas, `HasRoles`). |
| `tests/Feature/<Módulo>` | Un directorio por módulo de dominio; HTTP + servicios + policies de ese módulo. |
| `tests/Concerns` | Rasgos reutilizables para las pruebas. |

## Andamiaje: `Tests\Concerns\InteractsWithRoles`

Siembra el catálogo de roles/permisos (`RolePermissionSeeder`) **antes de cada
prueba** —vía el hook `setUpInteractsWithRoles()` que Laravel invoca solo, igual
que hace con `RefreshDatabase`— y ofrece atajos:

```php
class MiPruebaTest extends TestCase
{
    use InteractsWithRoles;   // no hace falta setUp() propio
    use RefreshDatabase;

    public function test_algo(): void
    {
        $admin = $this->adminUser();                 // coordinatorUser / teacherUser / studentUser
        $user  = $this->roleUser('coordinator', ['name' => 'Ada']);
        $this->actingAsRole(RoleSlug::Teacher);      // crea y autentica
    }
}
```

Regla: **una prueba nueva que necesita roles usa este rasgo**; no vuelve a
declarar `setUp()` ni llama a `seed(RolePermissionSeeder::class)`.

## Convenciones

- Nombre de método `test_<comportamiento_esperado>` en `snake_case`.
- Data providers con el atributo `#[DataProvider('metodo')]` (PHPUnit 12; la
  anotación `@dataProvider` ya no funciona).
- Procesos externos (`mysqldump`, …) se simulan con `Process::fake()`; nunca se
  invoca un binario real en las pruebas.
- Las aserciones de autorización comprueban el backend (código de estado 403 /
  redirección), no la presencia de botones en el HTML.
