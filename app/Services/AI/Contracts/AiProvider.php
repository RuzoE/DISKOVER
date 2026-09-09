<?php

namespace App\Services\AI\Contracts;

use App\DTOs\AI\ChatMessage;
use App\DTOs\AI\ChatResponse;
use App\Services\AI\Exceptions\AiException;

/**
 * Contrato de un proveedor de IA. La aplicación depende de esta interfaz,
 * nunca de una implementación concreta (ADR-0010 / sección 16 del prompt).
 */
interface AiProvider
{
    /**
     * Envía el hilo de mensajes y devuelve la respuesta del asistente.
     *
     * @param  array<int, ChatMessage>  $messages
     *
     * @throws AiException si el proveedor falla o no está disponible.
     */
    public function chat(array $messages): ChatResponse;

    /**
     * Identificador del proveedor (para persistir junto a la conversación).
     */
    public function name(): string;

    /**
     * Modelo que utilizará este proveedor.
     */
    public function model(): string;
}
