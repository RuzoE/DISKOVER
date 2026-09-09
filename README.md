# DISKOVER Smart Learning Ecosystem (DSLE)

> Plataforma Inteligente para la Gestión Académica y el Aprendizaje Inmersivo basada en
> Inteligencia Artificial, Analítica Educativa, Realidad Virtual y Realidad Aumentada.
>
> Academia DISKOVER US.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13 · PHP 8.3+ |
| Base de datos | MySQL 8 |
| Vistas | Blade |
| Assets | Vite · Tailwind CSS 4 · JavaScript |
| IA | Servicio externo (aislado tras `AIProviderInterface`) |
| Inmersivo | Unity (VR/AR), integrado vía API |
| Contenedores | Docker (cuando sea necesario) |

## Arquitectura

Monolito modular sobre Laravel 13. Módulos de dominio: `Academic`, `Analytics`, `AI`,
`Recommendations`, `Immersive`, `Reports`, `Security`. Controladores delgados; la lógica
vive en `app/Services` y `app/Actions`. Ver `docs/architecture/` (ADR).

## Requisitos locales

- PHP 8.3+ (probado con 8.4)
- Composer 2.x
- Node.js 20.19+ (recomendado 22 LTS) y npm
- MySQL 8

## Puesta en marcha

```bash
composer install
cp .env.example .env
php artisan key:generate

# Crear la base de datos 'diskover' en MySQL y ajustar credenciales en .env

php artisan migrate
npm install
npm run build          # o: npm run dev

php artisan serve
```

La aplicación queda disponible en `http://localhost:8000`.
Healthcheck: `http://localhost:8000/up`.

Tras `php artisan db:seed` existe un administrador inicial
(`DSLE_ADMIN_EMAIL` / `DSLE_ADMIN_PASSWORD`, por defecto
`admin@diskover.test` / `password`). **Cámbialo en cualquier entorno real.**

### Pruebas

```bash
# Una sola vez: base de datos de pruebas (el entorno no tiene pdo_sqlite)
mysql -uroot -e "CREATE DATABASE diskover_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

composer test            # php artisan test (226 pruebas)
composer lint            # vendor/bin/pint --test
composer check           # lint + test
```

Detalles del andamiaje y las convenciones: [`tests/README.md`](tests/README.md).
CI en cada push a `main`: [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

## Despliegue

**Contenedores** (pila completa: app + nginx + mysql + scheduler + queue):

```bash
cp .env.production.example .env      # rellena APP_KEY y contraseñas
docker compose up -d --build
docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
```

**Host tradicional** (PHP-FPM + Nginx, o Laragon):

```bash
./deploy/deploy.sh main
```

El programador (`dsle:backup` diario, ADR-0014) lo ejecuta el servicio
`scheduler` de compose o la línea de cron de `deploy/dsle-scheduler.cron`.
Ver [ADR-0016](docs/architecture/ADR-0016-devops-y-despliegue.md).

## Desarrollo por fases

El proyecto se construye en fases consecutivas (0 → 13). El registro de cada fase
—objetivo, archivos, pruebas y checklist— está en `docs/fases/`.

| Fase | Contenido | Estado |
|---|---|---|
| 0 | Preparación del entorno y estructura modular | ✅ |
| 1 | Autenticación, usuarios, roles y permisos | ✅ |
| 2 | Gestión académica (cursos, asignaturas, inscripciones, contenidos) | ✅ |
| 3 | Actividades y evaluaciones (preguntas, intentos, calificaciones) | ✅ |
| 4 | Seguimiento del aprendizaje (progreso, historial, perfil) | ✅ |
| 5 | Dashboards por rol (estudiante, docente, coordinación, admin) | ✅ |
| 6 | Learning Analytics (evolución, distribución, dificultades) | ✅ |
| 7 | Inteligencia artificial (asistente educativo + contexto) | ✅ |
| 8 | Motor de recomendaciones (reglas + seguimiento) | ✅ |
| 9 | Experiencias inmersivas (Unity, API de sesiones y resultados) | ✅ |
| 10 | Reportes (académicos, desempeño, institucionales, CSV) | ✅ |
| 11 | Auditoría y seguridad avanzada (registro append-only, permisos directos, cabeceras, backups) | ✅ |
| 12 | Pruebas e integración continua (andamiaje `InteractsWithRoles`, 223 pruebas, CI GitHub Actions) | ✅ |
| 13 | DevOps: imagen multi-stage, docker-compose (nginx/db/scheduler/queue), `deploy/deploy.sh` | ✅ |

## Repositorio

`https://github.com/RuzoE/DISKOVER`
