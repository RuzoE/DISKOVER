<?php

namespace App\DTOs\AI;

/**
 * Respuesta normalizada de cualquier proveedor de IA.
 */
final readonly class ChatResponse
{
    public function __construct(
        public string $content,
        public string $model,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
    ) {}
}
