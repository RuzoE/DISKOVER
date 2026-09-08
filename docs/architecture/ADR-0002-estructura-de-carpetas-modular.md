# ADR-0002 — Estructura de carpetas modular (backend y frontend)

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 0
- **Relacionado con:** ADR-0001

## Contexto

El monolito modular (ADR-0001) necesita una convención de carpetas explícita para que
cada dominio tenga un lugar predecible y para evitar que la lógica se acumule en los
controladores o en `app.js` / un CSS monolítico.

## Decisión

### Backend (`app/`)

| Carpeta | Responsabilidad |
|---|---|
| `Http/Controllers/{Admin,Teacher,Student,Coordinator,Analytics,AI,Immersive}` | Controladores delgados por rol/módulo. Sólo orquestan: validan (Form Request), llaman a un Service/Action y devuelven respuesta. |
| `Http/Requests` | Validación de toda entrada de usuario. |
| `Http/Middleware` | Filtros de petición (auth, roles, rate limiting…). |
| `Services/{Academic,Analytics,AI,Recommendations,Immersive,Reports,Security}` | Lógica de negocio del dominio. Puerta de entrada de cada módulo. |
| `Actions/{Academic,Analytics,AI,Recommendations,Immersive}` | Operaciones de negocio unitarias y reutilizables (una acción = un caso de uso). |
| `DTOs` | Objetos de transferencia de datos entre capas. |
| `Enums` | Enumeraciones del dominio (estados, tipos, roles…). |
| `Events` / `Listeners` | Comunicación desacoplada entre módulos y disparo de auditoría. |
| `Jobs` | Trabajo diferido / en cola (procesos pesados, integraciones externas). |
| `Notifications` | Notificaciones a usuarios (mail, base de datos, broadcast). |
| `Policies` | Autorización por modelo. |
| `Models` | Entidades Eloquent del dominio, sin lógica de negocio pesada. |

### Frontend (`resources/`)

- `views/` organizado por **rol/módulo** (`admin/`, `teacher/`, `student/`,
  `coordinator/`, `analytics/`, `ai/`, `immersive/`, `reports/`), nunca vistas sueltas
  en la raíz.
- `views/components/{ui,navigation,dashboard,tables}` para componentes Blade
  reutilizables. Si algo se usa en más de un módulo → se convierte en componente.
- `css/` separado en `base/`, `components/`, `layouts/`, `pages/<rol>/`, `utilities/`.
  Sin un único archivo gigante; sin estilos inline salvo excepción justificada.
- `js/` con `bootstrap.js`, `components/`, `modules/<rol>/`, `services/` (llamadas a
  API), `utils/` (funciones reutilizables). Nada de lógica de módulo en `app.js`.

### API (`routes/`)

- `routes/web.php` para la aplicación Blade.
- `routes/api.php` con **versionado obligatorio** bajo `/api/v1` (`prefix('v1')`).

### Documentación (`docs/`)

- `docs/architecture/` para ADR; `docs/fases/` para el registro de cada fase.

## Consecuencias

- Las carpetas se crean vacías con `.gitkeep` en Fase 0; se poblarán en su fase.
- Cualquier archivo nuevo debe encajar en esta estructura; si no encaja, se discute y
  se registra (posible nuevo ADR) antes de crear una carpeta fuera de convención.
