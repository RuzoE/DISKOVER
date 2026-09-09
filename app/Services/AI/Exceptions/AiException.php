<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;

/**
 * Error al comunicarse con el proveedor de IA (timeout, respuesta inválida,
 * error HTTP, etc.). El controlador lo captura y muestra un mensaje amable.
 */
class AiException extends RuntimeException {}
