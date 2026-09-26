<?php

namespace App\Enums;

/**
 * Decisión de AcademicIntegrityGuard sobre un mensaje al asistente (ADR-0017).
 *
 * - Allow: se envía al proveedor sin cambios.
 * - Guide: se envía con una instrucción adicional que obliga a orientar sin resolver.
 * - Block: no se envía al proveedor; DSLE responde con un mensaje fijo.
 */
enum IntegrityAction: string
{
    case Allow = 'allow';
    case Guide = 'guide';
    case Block = 'block';
}
