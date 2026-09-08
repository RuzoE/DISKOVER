# ADR-0001 — Arquitectura de monolito modular

- **Estado:** Aceptado
- **Fecha:** 2026-09-08
- **Contexto de decisión:** Fase 0

## Contexto

DSLE integra dominios heterogéneos: gestión académica, seguimiento del aprendizaje,
analítica educativa, IA generativa, motor de recomendaciones, experiencias inmersivas
(Unity/VR/AR), dashboards y reportes, además de administración, seguridad y auditoría.

La diversidad de tecnologías (Laravel, MySQL, servicio externo de IA, Unity) podría
sugerir una arquitectura de microservicios. Sin embargo, el equipo es reducido, el
producto está en fase inicial y los límites entre dominios aún no están validados por
uso real.

## Decisión

Se adopta una **arquitectura de monolito modular** sobre Laravel 13:

1. Una única aplicación desplegable y una única base de datos relacional.
2. El código se organiza por **módulos de dominio** con responsabilidades explícitas:
   `Academic`, `Analytics`, `AI`, `Recommendations`, `Immersive`, `Reports`, `Security`.
3. Cada módulo expone su lógica a través de **Services** y **Actions**; los controladores
   permanecen delgados.
4. La comunicación entre módulos se hace mediante servicios e **eventos de dominio**,
   evitando dependencias cruzadas ocultas.
5. La IA y las experiencias inmersivas se aíslan detrás de interfaces
   (`AIProviderInterface`, contratos del módulo `Immersive`) para poder sustituir
   proveedores o motores sin tocar el resto del sistema.

## Consecuencias

**Positivas**
- Menor complejidad operativa: un despliegue, una BD, transacciones simples.
- Refactorización barata mientras los límites de dominio se estabilizan.
- Un módulo puede extraerse a servicio independiente más adelante si hay una necesidad
  técnica real (escala, aislamiento de fallos, equipo dedicado).

**Negativas / riesgos**
- Riesgo de acoplamiento si no se respetan las fronteras de módulo → se mitiga con
  revisión de código y la regla de "sólo Services/Actions como puerta de entrada".
- El monolito crece; se vigilará el tamaño de modelos y servicios.

## Alternativas descartadas

- **Microservicios desde el inicio:** sobreingeniería para el tamaño actual del equipo
  y del producto; multiplica coste de infraestructura y observabilidad sin beneficio
  inmediato.
