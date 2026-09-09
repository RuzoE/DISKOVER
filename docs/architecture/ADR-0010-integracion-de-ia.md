# ADR-0010 — Integración de IA (asistente educativo)

- **Estado:** Aceptado
- **Fecha:** 2026-09-09
- **Contexto de decisión:** Fase 7
- **Relacionado con:** ADR-0001, ADR-0007, sección 16 del prompt maestro

## Contexto

DSLE necesita un asistente educativo que use IA generativa, sin acoplar la
aplicación a un proveedor concreto y sin exponer claves en el código. Además
debe funcionar en desarrollo sin ninguna cuenta de IA.

## Decisión

### La IA se consume sólo a través de una interfaz

```
Controlador → AiService → AiProvider (interfaz) → implementación concreta
```

- **`App\Services\AI\Contracts\AiProvider`** — `chat(ChatMessage[]): ChatResponse`,
  `name()`, `model()`. La aplicación depende de esta interfaz, nunca de una clase.
- **`App\Services\AI\AiService`** — único punto de entrada: crea conversaciones,
  añade mensajes, pide la respuesta y **persiste** el intercambio. Nunca lanza:
  si el proveedor falla, guarda un mensaje de asistente marcado `failed`.
- **`App\Services\AI\PromptManager`** — arma el hilo enviado: instrucción de
  sistema + contexto académico + historial reciente (`dsle.ai.max_history`) +
  mensaje nuevo.
- **`App\Services\AI\AcademicContextBuilder`** — resumen de texto con datos
  reales del propio usuario (cursos, progreso, promedio, asignaturas a reforzar),
  reutilizando los servicios de analítica de las fases 4–6.

### Implementaciones

- **`StubAiProvider`** (por defecto) — **sin red**. Genera una respuesta
  educativa determinista a partir del último mensaje y del contexto académico.
  Permite usar y probar la función sin ninguna cuenta. Es también el proveedor
  de las pruebas.
- **`OpenAiCompatibleProvider`** — `POST {base_url}/chat/completions`, compatible
  con OpenAI, Groq, OpenRouter, Ollama, etc. (mismo formato de mensajes y de
  respuesta). URL base, modelo, clave y timeout desde `config('dsle.ai')`.

### Selección de proveedor

`App\Providers\AiServiceProvider` liga `AiProvider::class` como *singleton*:
`provider === 'openai'` **y** hay `api_key` → `OpenAiCompatibleProvider`; en
cualquier otro caso → `StubAiProvider`. Las claves viven **sólo en `.env`**
(`DSLE_AI_*`), nunca en el repositorio.

### Datos y HTTP

- `ai_conversations` (user, título, contexto, provider, model, `last_message_at`)
  y `ai_messages` (role `user|assistant|system`, contenido, tokens, `failed`).
  `AiConversationPolicy`: sólo el dueño ve/edita/borra.
- Rutas `assistant/*` bajo `auth`+`active`, disponibles a **cualquier usuario
  autenticado**. `store` y `message` con `throttle:ai` (limiter definido en
  `AiServiceProvider`, `dsle.ai.rate_limit_per_minute`, por defecto 12/min/usuario).
- Sin *streaming*: el ciclo es POST → se persiste → redirect a la conversación.

## Consecuencias

- Cambiar de proveedor = una implementación nueva de `AiProvider` + un `case` en
  el binding; el resto de la app no cambia.
- La función es demostrable y testeable sin coste ni red gracias a `StubAiProvider`.
- El contexto académico se recalcula por mensaje (coste O(cursos·asignaturas));
  aceptable, se cacheará si hace falta.
- Sin streaming la respuesta llega de golpe; suficiente para el alcance actual.
- El `PromptManager` incluye una instrucción de no revelar el prompt/contexto,
  pero no es una garantía dura frente a *prompt injection*.

## Alternativas descartadas

- **SDK oficial de un proveedor** (p. ej. `openai-php`): acopla a un proveedor y
  añade dependencia; el cliente HTTP de Laravel cubre el endpoint estándar.
- **Sin proveedor de reserva** (deshabilitar la función si no hay clave): peor
  experiencia de desarrollo y pruebas más frágiles.
- **Colas / jobs para la llamada**: innecesario ahora; el timeout acota la espera.
