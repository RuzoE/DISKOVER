# ADR-0017 — Integridad académica en el asistente de IA

- **Estado:** Aceptado
- **Fecha:** 2026-09-26
- **Contexto de decisión:** ampliación de la Fase 7 (tutor disponible en todas las páginas)
- **Relacionado con:** ADR-0010 (integración de IA), ADR-0006 (evaluación), ADR-0014 (auditoría)

## Problema

El asistente pasa a ser un tutor disponible desde cualquier página (botón flotante
*Asistente DSLE*) y conoce los trabajos pendientes, las fechas y las notas del
estudiante. Con un modelo generativo real (Groq, OpenAI…) existe el riesgo de que
el estudiante lo use para **resolver sus actividades evaluables**: copiar el
enunciado de una pregunta durante un cuestionario, pedir «hazme la tarea» o
intentar saltarse las reglas («soy el profesor», «ignora tus instrucciones»).

Un prompt de sistema por sí solo no basta: los modelos pueden ser manipulados
(*prompt injection*) y cualquier texto enviado al proveedor ya ha salido de DSLE.

## Decisión

Defensa en **tres capas**, de la más fuerte a la más débil:

### 1. Control en Laravel, antes de llamar al proveedor

`App\Services\AI\AcademicIntegrityGuard::inspect(User, string): IntegrityVerdict`
se ejecuta en `AiService::sendMessage` **sólo para el rol estudiante** (docentes,
coordinación y administración no tienen restricciones). El texto se normaliza
(minúsculas, sin tildes ni signos) y se aplican, por orden:

| # | Condición | Acción |
|---|---|---|
| 1 | Tiene un intento de evaluación en curso (evaluación abierta, tiempo no agotado) | **Bloquear**: el mensaje no se envía; DSLE responde que el asistente está en pausa hasta que envíe la evaluación |
| 2 | Contiene el enunciado de una pregunta de una evaluación **abierta**, o ≥ 2 de sus opciones | **Bloquear**: DSLE ofrece explicar el tema sin dar la respuesta |
| 3 | Contiene el título o el enunciado de una actividad pendiente | **Modo guiado** |
| 4 | Pide que le hagan el trabajo («hazme», «resuélveme», «dame las respuestas», «escríbeme el ensayo», «soy el profesor», «ignora tus reglas»…) | **Modo guiado** |

- **Bloqueo:** el proveedor no recibe nada. El mensaje del usuario y la respuesta
  fija se guardan con `ai_messages.integrity = 'block'` y **nunca** se reenvían en
  el historial de turnos posteriores.
- **Modo guiado:** se añade una instrucción de sistema adicional que obliga a
  orientar sin resolver; el mensaje se marca `integrity = 'guide'`.
- Cada bloqueo y cada modo guiado se registra en la auditoría (ADR-0014) con los
  eventos `ai.integrity.block` y `ai.integrity.guide`, la conversación como
  entidad auditada y un código de motivo (`attempt_in_progress`, `open_question`,
  `pending_task`, `work_request`). **No** se guarda el texto del mensaje.
- Se puede desactivar con `DSLE_AI_INTEGRITY=false` (sólo para diagnóstico).

### 2. Prompt pedagógico

`PromptManager::SYSTEM` define un tutor con un procedimiento explícito: clasificar
la intención (A pendientes, B duda, C progreso, D resolver una actividad, E fuera
de tema), responder según ella y revisar la respuesta antes de enviarla. Las
reglas de no resolver se mantienen aunque el estudiante diga que es docente o
pida ignorarlas.

### 3. Límites técnicos y minimización de datos

- `max_tokens` por respuesta (`DSLE_AI_MAX_TOKENS`, 500 por defecto): una respuesta
  corta difícilmente contiene un trabajo completo listo para entregar.
- `throttle:ai` por usuario (ADR-0010) también en `POST /assistant/quick`.
- El contexto (`AcademicContextBuilder`) sólo incluye datos del propio estudiante
  y sólo su **primer nombre**; nunca correo, documento, identificadores internos,
  datos de otros estudiantes, enunciados de preguntas ni opciones correctas.
- Si el proveedor responde 429, `OpenAiCompatibleProvider` lanza
  `AiRateLimitedException` y el estudiante ve «El asistente está atendiendo muchas
  consultas; inténtalo en un minuto.».

## Alternativas descartadas

- **Confiar sólo en el prompt de sistema:** vulnerable a *prompt injection* y el
  enunciado de la pregunta ya habría salido hacia el proveedor.
- **Moderación con una segunda llamada al modelo** (clasificar cada mensaje con
  IA): duplica coste y latencia, consume el límite de uso de Groq y no es
  determinista ni fácil de probar.
- **Desactivar el asistente para estudiantes con actividades pendientes:**
  eliminaría justo la ayuda que se busca (organizarse y entender los temas).
- **Bloquear siempre que se mencione una tarea pendiente:** demasiado restrictivo;
  el estudiante debe poder pedir orientación sobre su propio trabajo, por eso esa
  regla usa el modo guiado y no el bloqueo.
- **Filtrar la respuesta del modelo a posteriori** buscando la opción correcta:
  frágil (paráfrasis) y requeriría enviar las respuestas correctas a la capa de
  comparación; se prefiere no exponerlas nunca.

## Consecuencias

- La integridad no depende del modelo elegido: las reglas 1 y 2 son deterministas
  y se prueban sin red (`tests/Feature/AI/AcademicIntegrityTest.php`).
- La detección por coincidencia de texto no cubre paráfrasis; el modo guiado y el
  prompt pedagógico cubren ese hueco de forma probabilística. Es una mitigación,
  no una garantía absoluta.
- Posibles falsos positivos (p. ej. un mensaje que cita dos opciones de una
  pregunta abierta por casualidad) sólo ocurren mientras la evaluación está
  abierta y la respuesta ofrece una alternativa útil.
- Cada mensaje de estudiante añade algunas consultas (asignaturas, actividades
  pendientes, evaluaciones abiertas). Aceptable para el volumen actual; se puede
  cachear por usuario si hace falta.
- Nueva columna `ai_messages.integrity` (nullable): migración compatible hacia
  atrás.
