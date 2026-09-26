<?php

namespace App\Services\AI\Exceptions;

/**
 * El proveedor de IA respondió 429: se alcanzó su límite de uso. AiService
 * muestra al estudiante un aviso para reintentar en un minuto.
 */
class AiRateLimitedException extends AiException {}
