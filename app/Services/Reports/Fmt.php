<?php

namespace App\Services\Reports;

/**
 * Formato numérico compacto compartido por los reportes (sin ceros a la derecha).
 */
final class Fmt
{
    public static function num(int|float|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }

        return rtrim(rtrim(number_format((float) $value, $decimals, '.', ''), '0'), '.') ?: '0';
    }
}
