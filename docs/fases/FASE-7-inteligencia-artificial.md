# FASE 7 — Inteligencia artificial

- **Fecha:** 2026-09-09
- **Estado:** ✅ Completada

## 1. Objetivo

Asistente educativo con IA generativa: integración con proveedor externo
**aislada tras una interfaz**, contexto académico personalizado, gestión de
conversaciones y funcionamiento sin conexión cuando no hay proveedor configurado.

## 2. Decisión de arquitectura

- **ADR-0010** — Integración de IA: `Controlador → AiService → AiProvider (interfaz)
  → implementación`. `StubAiProvider` (sin red, por defecto) y
  `OpenAiCompatibleProvider` (API tipo OpenAI). Claves sólo en `.env`.

## 3. Archivos creados

### Base de datos
```
database/migrations/2026_09_09_100001_create_ai_conversations_table.php
database/migrations/2026_09_09_100002_create_ai_messages_table.php
database/factories/AiConversationFactory.php
```

### Dominio / módulo AI
```
app/Enums/AiMessageRole.php
app/Models/AiConversation.php
app/Models/AiMessage.php
app/DTOs/AI/ChatMessage.php
app/DTOs/AI/ChatResponse.php
app/Services/AI/Contracts/AiProvider.php
app/Services/AI/Exceptions/AiException.php
app/Services/AI/Providers/StubAiProvider.php
app/Services/AI/Providers/OpenAiCompatibleProvider.php
app/Services/AI/AcademicContextBuilder.php
app/Services/AI/PromptManager.php
app/Services/AI/AiService.php
app/Providers/AiServiceProvider.php
app/Policies/AiConversationPolicy.php
```

### HTTP / vistas
```
app/Http/Requests/AI/StoreConversationRequest.php
app/Http/Requests/AI/SendMessageRequest.php
app/Http/Controllers/AI/AssistantController.php
resources/views/ai/index.blade.php
resources/views/ai/show.blade.php
resources/views/components/ai/bubble.blade.php
resources/css/components/chat.css
resources/js/modules/ai/assistant.js
```

### Pruebas
```
tests/Feature/AI/AssistantHttpTest.php
tests/Feature/AI/AiServiceTest.php
tests/Feature/AI/OpenAiCompatibleProviderTest.php
tests/Feature/AI/AcademicContextBuilderTest.php
```

### Documentación
```
docs/architecture/ADR-0010-integracion-de-ia.md
docs/fases/FASE-7-inteligencia-artificial.md
```

## 4. Archivos modificados

```
config/dsle.php                         # bloque 'ai' (provider, base_url, api_key, model, timeout,
                                        #   temperature, max_history, rate_limit_per_minute)
.env / .env.example                     # DSLE_AI_* (provider vacío -> stub)
bootstrap/providers.php                 # registra AiServiceProvider
app/Providers/AppServiceProvider.php    # Route::model('conversation', AiConversation::class)
app/Providers/AuthServiceProvider.php   # AiConversationPolicy
app/Models/User.php                     # relación aiConversations()
routes/web.php                          # grupo assistant/* (throttle:ai en store y message)
resources/js/app.js                     # init de assistant.js
resources/css/app.css                   # import chat.css
resources/views/components/navigation/sidebar.blade.php   # enlace "Asistente IA"; "Próximamente" -> Fase 8
```

## 5. Rutas nuevas

```
GET    assistant                       assistant.index
POST   assistant                       assistant.store    (throttle:ai)
GET    assistant/{conversation}        assistant.show
POST   assistant/{conversation}/messages  assistant.message (throttle:ai)
DELETE assistant/{conversation}        assistant.destroy
```

## 6. Cómo funciona

- **Sin proveedor** (`DSLE_AI_PROVIDER` vacío o sin `DSLE_AI_API_KEY`) → se usa
  `StubAiProvider`: responde **sin red** con orientación basada en el contexto
  académico del estudiante (menciona sus asignaturas a reforzar). La UI avisa del
  «modo sin conexión».
- **Con proveedor** (`DSLE_AI_PROVIDER=openai` + clave) → `OpenAiCompatibleProvider`
  llama a `{DSLE_AI_BASE_URL}/chat/completions` (OpenAI, Groq, OpenRouter, Ollama…).
- `PromptManager` envía: instrucción de sistema (tutor que no da la respuesta
  hecha) + `CONTEXTO ACADÉMICO` + últimos `max_history` mensajes + mensaje nuevo.
- Si el proveedor falla, `AiService` guarda un mensaje de asistente `failed` con
  un texto amable (no rompe la conversación) y lo registra en el log.
- `throttle:ai`: por defecto 12 peticiones/min por usuario (`dsle.ai.rate_limit_per_minute`).

## 7. Cómo probar

### Automático
```bash
php artisan test            # 116 pruebas (102 previas + 14 de Fase 7)
```

### Manual
```bash
php artisan migrate --seed && php artisan serve
```
1. Entrar con `estudiante@diskover.test` → *Asistente IA* (sidebar). Escribir una
   consulta y enviar: aparece la respuesta del asistente (modo sin conexión) con
   sugerencias y mención a los puntos a reforzar.
2. Continuar la conversación (Enter envía, Shift+Enter salto de línea). Volver al
   listado, abrir otra vez, eliminar.
3. Otro usuario no puede abrir ni escribir en esa conversación → **403**.
4. Superar 12 mensajes/min → **429** (rate limit).
5. Para respuestas reales: en `.env` poner `DSLE_AI_PROVIDER=openai`,
   `DSLE_AI_API_KEY=...` (y `DSLE_AI_BASE_URL` / `DSLE_AI_MODEL` si aplica),
   `php artisan config:clear`.

## 8. Resultado esperado

El asistente responde con contexto del estudiante, las conversaciones se
guardan y se pueden retomar/eliminar, y todo funciona sin ninguna cuenta de IA.
`php artisan test` → 116/116.

## 9. Checklist

- [x] Backend — IA aislada tras `AiProvider`; `AiService` como único punto de entrada; controlador delgado
- [x] Base de datos — `ai_conversations` / `ai_messages` con FK cascade e índices
- [x] Frontend — hilo de chat, composer, `x-ai.bubble`; CSS modular; JS de mejora progresiva
- [x] Validaciones — Form Requests para crear conversación y enviar mensaje
- [x] Seguridad — `AiConversationPolicy` (sólo el dueño); `throttle:ai`; claves sólo en `.env`; mensaje de error si el proveedor falla
- [x] Responsive — burbujas al 92 % en móvil; composer apila
- [x] Pruebas — 14 nuevas (HTTP, servicio, proveedor OpenAI con `Http::fake`, contexto académico)
- [x] Organización — `app/Services/AI/`, `app/Http/Controllers/AI/`, `resources/views/ai/`; conforme a ADR-0010

## 10. Notas para la siguiente fase

- **Fase 8 (motor de recomendaciones)** será un módulo **separado** de la IA
  generativa (sección 17): reglas sobre resultados/progreso, no llamadas a IA.
- Posible mejora futura: *streaming* de la respuesta y caché del contexto académico.

---

## 11. Ampliación (2026-09-26) — Tutor flotante e integridad académica

Ver **ADR-0017**. El asistente pasa a ser un tutor disponible desde cualquier
página para el estudiante (y el administrador, para pruebas) que responde qué
trabajos le faltan, dudas sobre sus temas y cómo va su progreso, **sin hacerle la
tarea**.

### Archivos creados
```
app/Services/AI/AcademicIntegrityGuard.php          # reglas de bloqueo / modo guiado
app/Services/AI/Exceptions/AiRateLimitedException.php
app/Services/Academic/StudentWorkloadService.php    # pendientes, contenidos, notas, intentos en curso
app/DTOs/AI/IntegrityVerdict.php
app/Enums/IntegrityAction.php
app/Http/Requests/AI/QuickMessageRequest.php
database/migrations/2026_09_26_100001_add_integrity_to_ai_messages_table.php
resources/views/components/ai/floating-assistant.blade.php
resources/js/modules/ai/quick-assistant.js
resources/css/components/quick-assistant.css
tests/Fakes/SpyAiProvider.php
tests/Feature/AI/AcademicIntegrityTest.php
tests/Feature/AI/QuickAssistantHttpTest.php
docs/architecture/ADR-0017-integridad-academica-en-ia.md
```

### Archivos modificados
```
app/Services/AI/AcademicContextBuilder.php   # pendientes (máx. 10), próximos 7 días, contenidos sin completar,
                                             #   últimas 3 notas con retroalimentación; sólo el primer nombre
app/Services/AI/PromptManager.php            # nuevo prompt del tutor + instrucción de modo guiado;
                                             #   excluye del historial los mensajes bloqueados.
                                             #   Corrección: el historial toma los últimos max_history
                                             #   mensajes en orden cronológico (antes, los más antiguos)
app/Services/AI/AiService.php                # integra el guard + auditoría, quickMessage(), aviso 429.
                                             #   Corrección: el mensaje nuevo ya no se envía duplicado
                                             #   (antes aparecía en el historial y otra vez al final)
app/Services/AI/Providers/OpenAiCompatibleProvider.php  # max_tokens; 429 -> AiRateLimitedException
app/Http/Controllers/AI/AssistantController.php          # acción quick() (JSON)
app/Models/AiMessage.php · app/Models/AiConversation.php  # campo integrity; etiqueta «Asistente rápido»
config/dsle.php                              # dsle.ai.max_tokens, dsle.ai.integrity
routes/web.php                               # POST assistant/quick (role:admin,student + throttle:ai)
resources/views/layouts/app.blade.php        # <x-ai.floating-assistant />
resources/views/components/ai/bubble.blade.php  # estilo de aviso para mensajes bloqueados
resources/js/app.js · resources/css/app.css · resources/css/utilities/helpers.css (u-sr-only)
.env.example · README.md                     # variables de Groq y pasos en Laravel Cloud
```

### Ruta nueva
```
POST   assistant/quick                 assistant.quick    (auth, active, role:admin,student, throttle:ai, CSRF)
```

### Configuración (Groq)
```dotenv
DSLE_AI_PROVIDER=openai
DSLE_AI_BASE_URL=https://api.groq.com/openai/v1
DSLE_AI_MODEL=openai/gpt-oss-120b      # llama-3.3-70b-versatile retirado por Groq el 16/08/2026
DSLE_AI_API_KEY=
DSLE_AI_MAX_TOKENS=500
DSLE_AI_INTEGRITY=true
DSLE_AI_REASONING_EFFORT=low           # sólo modelos de razonamiento
```

Diagnóstico: `php artisan dsle:ai-check` (configuración efectiva sin la clave + llamada de prueba).

### Pruebas nuevas
- Contexto: incluye pendientes y vencidas propias, próximos 7 días, contenidos y
  retroalimentación; excluye datos de otros estudiantes, enunciados y opciones.
- Integridad: pregunta normal → proveedor; intento en curso → el proveedor no
  recibe nada; enunciado copiado u opciones de evaluación abierta → bloqueo;
  evaluación cerrada → no bloquea; «Hazme la tarea» y título de tarea pendiente →
  modo guiado + auditoría; docentes sin restricciones; sólo llega el primer nombre;
  los mensajes bloqueados no se reenvían en el historial.
- `/assistant/quick`: exige autenticación, responde JSON, reutiliza la conversación
  rápida, valida, restringe por rol y aplica el límite de tasa.
- Proveedor: envía `max_tokens`; 429 → `AiRateLimitedException` → aviso amable.

### Checklist
- [x] Backend — guard en `app/Services/AI`, consultas en `StudentWorkloadService`; controlador delgado
- [x] Base de datos — columna `ai_messages.integrity` (nullable)
- [x] Frontend — botón flotante y panel lateral, sugerencias, indicador «escribiendo», errores
- [x] Validaciones — `QuickMessageRequest`
- [x] Seguridad — autenticación, CSRF, rol, `throttle:ai`, auditoría `ai.integrity.*`, minimización de datos
- [x] Responsive / accesibilidad — panel a pantalla completa en móvil; `aria-*`, foco, cierre con Escape
- [x] Pruebas — proveedor falso (`SpyAiProvider`); nunca se llama a la API real
- [x] Organización — conforme a ADR-0002, ADR-0004, ADR-0010 y ADR-0017
