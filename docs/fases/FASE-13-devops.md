# FASE 13 — DevOps: empaquetado, despliegue y operación

- **Fecha:** 2026-09-09
- **Estado:** ✅ Completada

## 1. Objetivo

Hacer el proyecto desplegable de forma reproducible: imagen de producción,
orquestación de un host, procedimiento de despliegue sin contenedores, ejecución
del `schedule` (del que depende `dsle:backup`) y un worker de cola. Sin cambios
en el comportamiento de la aplicación.

## 2. Decisión de arquitectura

- **ADR-0016** — Una imagen multi-stage para los tres roles (`app` / `scheduler`
  / `queue`); `docker-compose.yml` con nginx + mysql + scheduler + queue;
  `deploy/deploy.sh` para host tradicional; `.env.production.example` endurecido;
  sesión/caché/cola sobre base de datos (sin Redis); CI construye y prueba la
  imagen.

## 3. Archivos creados

```
Dockerfile
.dockerignore
docker-compose.yml
docker/entrypoint.sh
docker/php/php.ini
docker/nginx/default.conf
.env.production.example
deploy/deploy.sh
deploy/dsle-scheduler.cron
deploy/supervisor/dsle-worker.conf
tests/Feature/ScheduleTest.php
docs/architecture/ADR-0016-devops-y-despliegue.md
docs/fases/FASE-13-devops.md
```

## 4. Archivos modificados

```
.github/workflows/ci.yml                         # job "image": build + smoke-test de la imagen
resources/views/components/navigation/sidebar.blade.php   # se retira el bloque "Próximamente / Fase 13"
README.md, docs/README.md                        # sección de despliegue; todas las fases completadas
```

## 5. Empaquetado

`Dockerfile` (multi-stage):

1. `composer:2` → `composer install --no-dev --optimize-autoloader`.
2. `node:20-alpine` → `npm ci && npm run build`.
3. `php:8.4-fpm-alpine` + extensiones (`pdo_mysql`, `mbstring`, `bcmath`, `gd`,
   `zip`, `intl`, `opcache`, `pcntl`) + `mysql-client`. Corre como `www-data`,
   `EXPOSE 9000`, `ENTRYPOINT entrypoint`.

`docker/entrypoint.sh` espera a la BD y, **sólo si `CONTAINER_ROLE=app`**, migra
(`--force`), enlaza `storage` y cachea (`optimize`); los demás roles sólo cachean.

## 6. Orquestación (`docker-compose.yml`)

| Servicio | Imagen / comando | Función |
|---|---|---|
| `app` | Dockerfile · `php-fpm` | Aplicación |
| `web` | `nginx:1.27-alpine` | Sirve `public/`, delega PHP a `app:9000`; healthcheck `/up` |
| `scheduler` | Dockerfile · bucle `schedule:run` cada 60 s | Tareas programadas (incluye `dsle:backup` diario) |
| `queue` | Dockerfile · `queue:work --tries=3 --max-time=3600` | Worker de cola (`database`) |
| `db` | `mysql:8.0` | Base de datos; healthcheck `mysqladmin ping` |

Volúmenes con nombre: `db-data`, `storage-data`, `backups-data` (las copias
sobreviven al redepliegue).

```bash
cp .env.production.example .env      # rellena APP_KEY y contraseñas
docker compose up -d --build
docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
```

## 7. Despliegue sin contenedores (`deploy/deploy.sh`)

```bash
./deploy/deploy.sh main
```

`artisan down` (con trap de recuperación) → `git reset --hard origin/main` →
`composer install --no-dev` → `npm ci && npm run build` → `migrate --force` →
`optimize:clear && optimize` → `queue:restart` → `artisan up`.

Complementos: `deploy/dsle-scheduler.cron` (línea de cron) y
`deploy/supervisor/dsle-worker.conf` (worker con Supervisor).

## 8. Configuración de producción

`.env.production.example`: `APP_ENV=production`, `APP_DEBUG=false`,
`LOG_LEVEL=warning`, `LOG_STACK=daily`, `SESSION_ENCRYPT=true`,
`SESSION_SECURE_COOKIE=true`; sesión, caché y cola sobre `database`. Secretos
vacíos, se completan en el host. `docker/php/php.ini`:
`opcache.validate_timestamps=0`, `expose_php=Off`.

## 9. Cómo probar

```bash
# Cachés de framework (lo que hace el despliegue): deben construirse y limpiarse
php artisan config:cache && php artisan route:cache && php artisan event:cache && php artisan view:cache
php artisan optimize:clear

php artisan test          # 225 pruebas (223 previas + 2 de Fase 13)

# Imagen (requiere Docker)
docker build -t dsle:local .
docker run --rm --entrypoint php dsle:local artisan --version
```

## 10. Resultado esperado

`docker compose up -d --build` levanta app + nginx + mysql + scheduler + queue;
`/up` responde y el `schedule` corre `dsle:backup` a diario. En un host clásico,
`deploy/deploy.sh` publica una versión nueva sin dejar la app caída ante un
fallo. Todas las cachés de framework se construyen sin closures.
`php artisan test` → 225/225.

## 11. Checklist

- [x] **Backend** — sin cambios de lógica; el `schedule` de `dsle:backup` queda cubierto por prueba
- [x] **Base de datos** — sin cambios de esquema; sesión/caché/cola usan tablas ya migradas
- [x] **Frontend** — build de Vite dentro de la imagen; nginx cachea estáticos; se retira el enlace "Próximamente" del sidebar
- [x] **Validaciones** — N/A
- [x] **Seguridad** — `APP_DEBUG=false`, cookies seguras y cifradas, `expose_php=Off`, la imagen no contiene `.env`, secretos sólo en el host
- [x] **Responsive** — N/A
- [x] **Pruebas** — `ScheduleTest` (backup diario + comando registrado); caché de config/route/view/event verificada manualmente; CI construye y smoke-testea la imagen
- [x] **Organización** — `docker/`, `deploy/`, `Dockerfile`, `docker-compose.yml` en la raíz; conforme a ADR-0016

## 12. Cierre del proyecto

Fases 0–13 completadas. El proyecto está funcionalmente completo, probado
(225 pruebas), con CI y desplegable por contenedores o por script. Trabajo
futuro sugerido (fuera del plan): alta disponibilidad y `db` gestionada;
publicación de la imagen en un registro; Octane si la carga lo exige; cifrado y
subida remota de las copias de seguridad; CSP con nonces; retención/particionado
de `audit_logs`.
