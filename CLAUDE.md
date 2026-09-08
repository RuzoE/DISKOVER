# DSLE — Guía para agentes de IA

DISKOVER Smart Learning Ecosystem (DSLE) es una plataforma académica y de aprendizaje
inmersivo construida sobre **Laravel 13 + PHP 8.3+ + MySQL 8 + Blade + Vite/Tailwind 4**.

## Reglas de trabajo

1. **Desarrollo por fases.** No construir todo de una vez. Cada fase se solicita
   explícitamente ("Continuar con la Fase N") y termina deteniéndose para esperar la
   siguiente. Ver `docs/fases/`.
2. **Arquitectura: monolito modular** (ver `docs/architecture/ADR-0001`). No introducir
   microservicios.
3. **Controladores delgados.** La lógica de negocio va en `app/Services/<Modulo>` y
   `app/Actions/<Modulo>`. Nunca consultas complejas ni reglas de negocio en el
   controlador.
4. **Estructura de carpetas fija** (ver `docs/architecture/ADR-0002`). Todo archivo
   nuevo debe encajar en ella. Vistas Blade siempre bajo `resources/views/<rol o
   modulo>/...`, nunca en la raíz de `views/`.
5. **No duplicar.** Antes de crear un componente, service, helper, función JS o estilo
   CSS, comprobar si ya existe uno reutilizable.
6. **Validación** siempre con Form Requests (`app/Http/Requests`).
7. **Autorización** en el backend (middleware + Policies + Gates), nunca sólo ocultando
   botones en Blade.
8. **Secretos** sólo en `.env`. Nunca claves/tokens en el código.
9. **API** bajo `routes/api.php` con versionado `/api/v1`.
10. **No modificar funcionalidad existente sin justificarlo** y explicar qué cambia.

## Módulos de dominio

`Academic` · `Analytics` · `AI` · `Recommendations` · `Immersive` · `Reports` ·
`Security`. Cada uno con su carpeta en `app/Services/` y (según aplique) `app/Actions/`,
`app/Http/Controllers/`, `resources/views/`, `resources/js/modules/`,
`resources/css/pages/`.

## Comandos habituales

```bash
php artisan migrate
php artisan test
npm run dev
vendor/bin/pint          # formato de código
```

## Formato de respuesta por fase

Objetivo · Archivos a crear · Archivos a modificar · Comandos · Código completo (rutas
exactas, sin fragmentos ambiguos) · Explicación · Pruebas · Resultado esperado ·
Checklist (backend, BD, frontend, validaciones, seguridad, responsive, pruebas,
organización). Luego **detenerse**.
