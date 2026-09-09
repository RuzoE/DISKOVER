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
