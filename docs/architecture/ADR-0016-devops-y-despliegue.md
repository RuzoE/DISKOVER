# ADR-0016 — DevOps: empaquetado, despliegue y operación

- **Estado:** Aceptado
- **Fecha:** 2026-09-09
- **Contexto de decisión:** Fase 13
- **Relacionado con:** ADR-0001 (monolito modular), ADR-0014 (backups),
  ADR-0015 (CI), sección 13 del prompt maestro

## Contexto

El código está completo y probado (fases 0–12) pero no hay forma reproducible de
llevarlo a producción: ni imagen, ni orquestación, ni procedimiento de
despliegue, ni ejecución del `schedule` (del que depende `dsle:backup`).

## Decisión

### 1. Una sola imagen para tres roles

`Dockerfile` multi-stage: `composer` (deps sin dev, autoloader optimizado) →
`node` (build de Vite) → `php:8.4-fpm-alpine` con las extensiones mínimas
(`pdo_mysql`, `mbstring`, `bcmath`, `gd`, `zip`, `intl`, `opcache`, `pcntl`) y
`mysql-client` (para `mysqldump`). La **misma imagen** sirve a los servicios
`app` (php-fpm), `scheduler` y `queue`; sólo cambia el comando y la variable
`CONTAINER_ROLE`.

`docker/entrypoint.sh` espera a la base de datos y, **sólo en el rol `app`**,
migra (`migrate --force`), enlaza `storage` y cachea (`optimize`). Scheduler y
queue sólo cachean, para no competir por migrar.

### 2. Orquestación de un host: `docker-compose.yml`

`app` + `web` (nginx, sirve `public/` y delega PHP a `app:9000`) + `db`
(mysql:8) + `scheduler` (bucle `schedule:run` cada 60 s) + `queue`
(`queue:work`). `storage/app` y `storage/app/backups` son volúmenes con nombre
para que las copias de seguridad sobrevivan a un redepliegue. La configuración
llega por `env_file: .env`; **la imagen no contiene ningún `.env`** (`.dockerignore`).

### 3. Despliegue sin contenedores: `deploy/deploy.sh`

Para un VPS clásico (PHP-FPM + Nginx) o Laragon: `artisan down` con trap de
recuperación, `git reset --hard origin/<rama>`, `composer install --no-dev
--optimize-autoloader`, `npm ci && npm run build`, `migrate --force`,
`optimize:clear && optimize`, `queue:restart`, `artisan up`. Idempotente y
seguro ante fallos (el `trap` levanta la app aunque un paso falle).

Complementos: `deploy/dsle-scheduler.cron` (la única línea de cron necesaria) y
`deploy/supervisor/dsle-worker.conf` (worker de cola con Supervisor).

### 4. Configuración de producción

`.env.production.example` documenta los valores endurecidos: `APP_DEBUG=false`,
`APP_ENV=production`, `LOG_LEVEL=warning`, `LOG_STACK=daily`,
`SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, y **sesión/caché/cola sobre
la base de datos** (sin dependencia de Redis; ya hay migraciones para las tres
tablas). Secretos vacíos: se rellenan en el host, nunca en el repositorio.

`docker/php/php.ini`: `opcache.validate_timestamps=0` (el código no cambia en
caliente), `expose_php=Off`, límites de subida a 20 MB.

### 5. CI publica una imagen de prueba

`.github/workflows/ci.yml` añade el job `image` (tras `tests`): construye la
imagen con Buildx y caché de GHA, la carga y hace un smoke-test
(`artisan --version`, presencia de `pdo_mysql` y `mysqldump`). No se publica en
ningún registro: sólo garantiza que el `Dockerfile` sigue siendo válido.

### 6. Salud

Se reutiliza el endpoint `/up` de Laravel (ya existente) como healthcheck de
`web` en compose. No se añade un endpoint propio.

## Consecuencias

- `docker compose up -d --build` levanta la pila completa, incluido el
  `schedule` (y por tanto `dsle:backup` diario) y un worker de cola.
- El despliegue en host tradicional es un único script versionado y auditable.
- La cola pasa a ejecutarse de verdad en producción (`database`), aunque hoy
  ningún caso de uso encola trabajos; queda listo para cuando se haga.
- `opcache.validate_timestamps=0` obliga a reconstruir la imagen (o `optimize`)
  en cada despliegue para ver los cambios: es el comportamiento deseado en
  producción.
- No se cubre alta disponibilidad (varias réplicas, balanceador, `db`
  gestionada): fuera del alcance de un despliegue de un host. `onOneServer()` ya
  está puesto en el `schedule` para cuando llegue.

## Alternativas descartadas

- **Laravel Sail**: orientado a desarrollo; su imagen no es adecuada para
  producción.
- **Laravel Octane (Swoole/RoadRunner)**: más rendimiento pero más complejidad
  operativa y de depuración; php-fpm es suficiente para la carga prevista.
- **Redis para sesión/caché/cola**: otra pieza que desplegar y vigilar; el
  driver `database` cumple y ya está migrado.
- **Publicar la imagen en GHCR desde CI**: requiere decidir versionado y
  credenciales de registro; se deja para cuando exista un entorno real al que
  desplegar.
- **Supervisor dentro del contenedor** para correr fpm+scheduler+queue juntos:
  rompe el principio de un proceso por contenedor y complica el escalado; se
  usan servicios separados en compose.
