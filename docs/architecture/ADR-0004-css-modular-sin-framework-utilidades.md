# ADR-0004 — CSS modular propio (sin framework de utilidades)

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 1
- **Relacionado con:** ADR-0002

## Contexto

El *skeleton* de Laravel 13 incluía Tailwind CSS 4 (`@tailwindcss/vite` +
`@import 'tailwindcss'`). El PROMPT MAESTRO (secciones 5–8) define una
arquitectura de CSS por archivos: `base/`, `components/`, `layouts/`,
`pages/<rol>/`, `utilities/`, con estilos de página en `pages/` y estilos
reutilizables en `components/`, y prohíbe el archivo CSS monolítico y los estilos
inline.

Ese modelo (clases semánticas + hojas por componente) y el enfoque de Tailwind
(utilidades en el marcado) tiran en direcciones opuestas. Mantener ambos genera
inconsistencia y un `app.css` enorme.

## Decisión

Se retira Tailwind del proyecto y se adopta un **sistema de diseño propio en CSS
plano**:

- `resources/css/base/tokens.css`: única fuente de color, espaciado, tipografía,
  radios y sombras mediante *custom properties* en `:root`.
- `base/reset.css`, `base/typography.css`.
- `components/`: `buttons`, `forms`, `alerts`, `badges`, `cards`, `tables`,
  `pagination` (una hoja por componente, clases tipo BEM).
- `layouts/`: `app` (navbar + shell autenticado), `guest` (pantallas de acceso).
- `pages/<rol>/`: estilos específicos de pantalla (por ahora `pages/admin/users.css`).
- `utilities/helpers.css`: un puñado de utilidades de espaciado, uso moderado.
- `app.css` solo contiene los `@import` en orden.
- Componentes Blade (`resources/views/components/ui/*`) para botón, input, select,
  label, alert, badge, card; y `tables/empty-state`, `navigation/navbar`.
- Vista de paginación propia en `resources/views/vendor/pagination/dsle.blade.php`,
  fijada como predeterminada en `AppServiceProvider`.

Cambios: `vite.config.js` (sin plugin `tailwindcss`), `package.json` (sin
`tailwindcss` ni `@tailwindcss/vite`), `resources/css/app.css`. Se conserva la
fuente *Instrument Sans* vía `laravel-vite-plugin/fonts`.

## Consecuencias

**Positivas**
- Coherencia con ADR-0002; un único sistema visual reutilizable (sección 28).
- CSS de producción pasó de ~38 kB a ~10 kB.
- Sin dependencia de la cadena de Tailwind.

**Negativas / riesgos**
- Sin utilidades listas para usar: cada patrón nuevo se añade como componente o
  clase. Mitigado por los tokens y el set inicial de componentes.
- Hay que mantener manualmente la coherencia (revisión de código).

## Alternativas descartadas

- **Mantener Tailwind y usarlo solo para utilidades:** seguiría empujando hacia
  utilidades-en-marcado y complica la regla de "estilos de página en `pages/`".
- **Tailwind con `@apply` en las hojas de componente:** acopla el sistema a
  Tailwind sin ganar nada frente a CSS plano con tokens.
