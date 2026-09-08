# FASE 0 — Preparación

- **Fecha:** 2026-09-08
- **Estado:** ✅ Completada

## 1. Objetivo

Dejar el proyecto listo para desarrollar funcionalidades: entorno verificado, `.env`
configurado, MySQL conectado, Git inicializado y enlazado al repositorio, Vite operativo,
estructura de carpetas del monolito modular creada y documentación técnica iniciada.
No se implementa lógica de negocio.

## 2. Entorno verificado

| Componente | Versión |
|---|---|
| Laravel | 13.31.0 |
| PHP | 8.4.24 |
| Composer | 2.8.8 |
| MySQL | 8.0.30 (BD `diskover`) |
| Node.js | 20.19.4 (⚠️ recomendado subir a 22 LTS) |
| Vite | 8.2.2 |

## 3. Archivos creados

```text
routes/api.php                        # grupo /api/v1
resources/js/bootstrap.js
docs/README.md
docs/architecture/ADR-0001-monolito-modular.md
docs/architecture/ADR-0002-estructura-de-carpetas-modular.md
docs/fases/FASE-0-preparacion.md

# Estructura backend (carpetas con .gitkeep)
app/Console/Commands/
app/Exceptions/
app/Http/Controllers/{Admin,Teacher,Student,Coordinator,Analytics,AI,Immersive}/
app/Http/Middleware/
app/Http/Requests/
app/Policies/
app/Services/{Academic,Analytics,AI,Recommendations,Immersive,Reports,Security}/
app/Actions/{Academic,Analytics,AI,Recommendations,Immersive}/
app/DTOs/  app/Enums/  app/Events/  app/Listeners/  app/Jobs/  app/Notifications/

# Estructura frontend (carpetas con .gitkeep)
resources/views/{layouts,components,partials,auth}/
resources/views/components/{ui,navigation,dashboard,tables}/
resources/views/admin/{dashboard,users,roles,settings}/
resources/views/teacher/{dashboard,courses,students,contents,activities,evaluations,analytics}/
resources/views/student/{dashboard,courses,activities,evaluations,progress,recommendations,immersive}/
resources/views/coordinator/{dashboard,analytics,reports}/
resources/views/{analytics,ai,immersive,reports}/
resources/css/{base,components,layouts,utilities}/
resources/css/pages/{admin,teacher,student,coordinator,analytics,ai,immersive}/
resources/js/{components,services,utils}/
resources/js/modules/{admin,teacher,student,analytics,ai,immersive}/
```

## 4. Archivos modificados

```text
.env                 # APP_NAME=DISKOVER..., APP_URL, APP_LOCALE=es, APP_FAKER_LOCALE=es_ES, bloque DSLE_*
.env.example         # alineado con MySQL + identidad DSLE
bootstrap/app.php    # registro de routes/api.php
resources/js/app.js  # import './bootstrap'
CLAUDE.md            # guía de arquitectura DSLE (reemplaza bootstrap de Laravel Boost)
AGENTS.md            # copia sincronizada de CLAUDE.md
README.md            # portada DSLE + puesta en marcha + tabla de fases
```

## 5. Comandos ejecutados

```bash
npm install                 # 137 paquetes
npm run build               # manifest + assets en public/build/ OK
php artisan migrate --force # users, cache, jobs (batch 1) OK
git init -b main
```

## 6. Base de datos

BD `diskover` estaba vacía. Tras `migrate`: tablas `migrations`, `users`,
`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`,
`failed_jobs`. Confirma acceso de escritura a MySQL.

## 7. Cómo comprobar

```bash
php artisan about                 # Environment: local, DB: mysql
php artisan db:show               # Database: diskover
php artisan migrate:status        # 3 migraciones "Ran"
php artisan serve                 # http://localhost:8000  y  /up (healthcheck 200)
php artisan route:list            # deben aparecer rutas web + /up
```

## 8. Resultado esperado

- `php artisan serve` sirve la página de bienvenida de Laravel sin errores.
- `/up` responde 200.
- `public/build/manifest.json` existe (Vite build correcto).
- El repositorio tiene un commit inicial en `main`.

## 9. Checklist

- [x] Backend — estructura modular creada, `api.php` registrado
- [x] Base de datos — MySQL conectado, esquema base migrado
- [x] Frontend — Vite instalado y build correcto; carpetas CSS/JS modulares
- [x] Validaciones — N/A en esta fase (carpeta `Http/Requests` preparada)
- [x] Seguridad — `.env` fuera de Git; `.env.example` sin secretos; `APP_KEY` presente
- [x] Responsive — N/A en esta fase
- [x] Pruebas — `php artisan test` (suite base del skeleton) disponible
- [x] Organización — estructura conforme a ADR-0002; documentación en `docs/`

## 10. Pendiente / notas para la siguiente fase

- **Push al remoto** `https://github.com/RuzoE/DISKOVER` pendiente de autorización.
- Recomendado actualizar Node.js a 22 LTS (aviso EBADENGINE de `concurrently`).
- Decisión abierta: instalar o no `laravel/boost` (dev). Por defecto, no.
- Fase 1 arrancará autenticación, `User`/`Role`/permisos, middleware y Policies.
