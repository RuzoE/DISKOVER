# FASE 9 — Experiencias inmersivas

- **Fecha:** 2026-09-09
- **Estado:** ✅ Completada

## 1. Objetivo

Registro de experiencias inmersivas (VR/AR/Unity), vínculo con asignaturas y
actividades, control de disponibilidad, identificación del estudiante, registro
de sesiones y recepción de resultados mediante una **API REST** que consume el
cliente (Unity). Laravel orquesta; Unity ejecuta (sección 19).

## 2. Decisión de arquitectura

- **ADR-0012** — `ImmersiveExperience` + `experience_subject` + `ImmersiveSession`;
  API `/api/v1/immersive/*` autenticada por `launch_token`; resultado →
  `GradeService::setFromImmersive` (`GradeSource::Immersive`).

## 3. Archivos creados

### Base de datos
```
database/migrations/2026_09_09_120001_create_immersive_experiences_table.php
database/migrations/2026_09_09_120002_create_experience_subject_table.php
database/migrations/2026_09_09_120003_create_immersive_sessions_table.php
database/factories/ImmersiveExperienceFactory.php
database/factories/ImmersiveSessionFactory.php
```

### Dominio / módulo Immersive
```
app/Enums/ImmersiveProvider.php
app/Enums/ImmersiveSessionStatus.php
app/Models/ImmersiveExperience.php
app/Models/ImmersiveSession.php
app/Services/Immersive/ImmersiveExperienceService.php
app/Services/Immersive/ImmersiveSessionService.php
app/Policies/ImmersiveExperiencePolicy.php
```

### HTTP / vistas
```
app/Http/Requests/Immersive/StoreExperienceRequest.php      UpdateExperienceRequest.php
app/Http/Requests/Immersive/CompleteSessionRequest.php
app/Http/Controllers/Immersive/ExperienceController.php          (catálogo, web)
app/Http/Controllers/Immersive/Api/SessionController.php         (API, token)
app/Http/Controllers/Student/ImmersiveController.php             (consumo, web)
resources/views/components/immersive/experience-form.blade.php
resources/views/immersive/experiences/{index,create,edit,show}.blade.php
resources/views/immersive/student/{index,session}.blade.php
resources/css/components/immersive.css
resources/js/modules/immersive/simulator.js
```

### Pruebas
```
tests/Feature/Immersive/ExperienceManagementTest.php
tests/Feature/Immersive/StudentLaunchTest.php
tests/Feature/Immersive/ImmersiveApiTest.php
```

### Documentación
```
docs/architecture/ADR-0012-experiencias-inmersivas-y-api.md
docs/fases/FASE-9-experiencias-inmersivas.md
```

## 4. Archivos modificados

```
config/dsle.php                         # bloque immersive (session_ttl_minutes, api_rate_limit_per_minute)
routes/api.php                          # /api/v1/immersive/sessions/{token} (show/complete/abandon), throttle:immersive
routes/web.php                          # immersive/experiences (resource); student/immersive (index/launch/session/abandon)
app/Providers/AppServiceProvider.php    # Route::model experience/session; rate limiter 'immersive'
app/Providers/AuthServiceProvider.php   # ImmersiveExperiencePolicy
app/Enums/GradeSource.php               # nuevo caso Immersive
app/Services/Academic/GradeService.php  # setFromImmersive(); syncFromBestAttempt no pisa nota inmersiva
app/Models/{User,Subject,Activity}.php  # relaciones (immersiveSessions, immersiveExperiences, immersiveExperience)
database/seeders/RolePermissionSeeder.php  # permiso immersive.manage (rol coordinación)
database/seeders/DemoAcademicSeeder.php # actividad "Laboratorio virtual" + experiencia demo vinculada
resources/views/components/navigation/sidebar.blade.php  # enlaces (coordinación + estudiante); "Próximamente" -> Fase 10-11
resources/css/app.css · resources/js/app.js
```

## 5. API para el cliente (Unity)

```
GET    /api/v1/immersive/sessions/{token}            -> { session, experience{config,max_score,...}, student }
POST   /api/v1/immersive/sessions/{token}/complete   body { score, payload } -> { status, score, percentage }
POST   /api/v1/immersive/sessions/{token}/abandon    -> { status }
```

- El `launch_token` (64 chars) identifica sesión → estudiante → experiencia. Sin
  Sanctum ni cookie.
- Expira a `started_at + session_ttl_minutes` (config, 6 h). `complete` sobre una
  sesión no `started` o caducada → **422**. Token inexistente → **404**.
- `throttle:immersive` (config, 60/min por token/IP).

## 6. Flujo

1. **Coordinación** registra la experiencia (`immersive.manage`), elige proveedor,
   URL de lanzamiento (o simulador), asignaturas y, opcionalmente, una actividad.
2. **Estudiante** inscrito abre *Experiencias VR/AR* → *Comenzar* → Laravel crea
   la sesión y muestra el lanzador (iframe WebGL / enlace / simulador).
3. El cliente llama a la API con el token para leer la configuración y, al
   terminar, envía `{ score, payload }`.
4. Si la experiencia está vinculada a una actividad publicada, la nota se
   registra automáticamente (`source = immersive`, escalada a `max_score` de la
   actividad) y se disparan el evento de aprendizaje y el refresco de
   recomendaciones.

## 7. Cómo probar

### Automático
```bash
php artisan test            # 150 pruebas (133 previas + 17 de Fase 9)
```

### Manual
```bash
php artisan migrate:fresh --seed && php artisan serve
```
El seeder deja la experiencia demo **«Laboratorio virtual — recorrido guiado»**
(provider *simulador*) vinculada a la asignatura y a la actividad
«Laboratorio virtual: recorrido».

1. **Coordinación** (`coordinacion@diskover.test`): *Experiencias inmersivas* →
   ver/crear/editar; el detalle muestra el contrato de la API.
2. **Estudiante** (`estudiante@diskover.test`): *Experiencias VR/AR* → *Comenzar*.
   Como es simulador, el lanzador ofrece un campo de puntuación → *Finalizar
   experiencia* (llama a la API real). Se ve el resultado y, al estar vinculada,
   la calificación aparece en la actividad.
3. Un docente o estudiante no puede entrar al catálogo → **403**. Un estudiante
   no inscrito no puede lanzar → **403**. Otro estudiante no ve tu sesión → **403**.
4. API: `GET /api/v1/immersive/sessions/<token>` devuelve la config; `POST …/complete`
   con `{ "score": 88 }` cierra y califica.

## 8. Resultado esperado

Laravel registra experiencias y sesiones y recibe los resultados por API sin
ejecutar Unity; el simulador permite validar todo el ciclo en el navegador.
`php artisan test` → 150/150.

## 9. Checklist

- [x] Backend — `Immersive*Service` (controladores delgados); API token-scoped; sin lógica de Unity en Laravel
- [x] Base de datos — 3 tablas, FK `cascade`/`nullOnDelete`, `launch_token` único
- [x] Frontend — catálogo + lanzador (iframe / enlace / simulador); CSS y JS modulares
- [x] Validaciones — Form Requests (experiencia y resultado); `launch_url` obligatoria salvo simulador; `config` JSON válido
- [x] Seguridad — `role:` + `immersive.manage`; acceso del estudiante por inscripción; token efímero de un solo resultado; `throttle:immersive`
- [x] Responsive — iframe 16:9 fluido; rejilla de experiencias adaptable
- [x] Pruebas — 17 nuevas (catálogo, lanzamiento, API completa, expiración, calificación)
- [x] Organización — `app/Services/Immersive/`, `app/Http/Controllers/Immersive/`, `resources/views/immersive/`, `routes/api.php`; conforme a ADR-0012

## 10. Notas para la siguiente fase

- **Fase 10 (reportes)**: `app/Services/Reports/`, exportación cuando corresponda.
- Mejora futura: endpoint de progreso/heartbeat; `provider` de tipo webhook
  firmado para SaaS de terceros; servir el cliente por HTTPS y excluir el token
  de los logs.
