<?php

namespace App\Services\Reports\Contracts;

/**
 * Un reporte tabular de DSLE: cabeceras, filas y un resumen opcional. Se
 * renderiza en pantalla y se exporta a CSV con el mismo objeto (ReportExporter).
 */
interface Report
{
    /**
     * Clave estable (kebab-case) para nombrar el fichero de exportación.
     */
    public function key(): string;

    public function title(): string;

    /**
     * Pares etiqueta => valor con el contexto del reporte (curso, fechas…).
     *
     * @return array<string, string>
     */
    public function meta(): array;

    /**
     * @return array<int, string>
     */
    public function headings(): array;

    /**
     * Filas en el mismo orden que headings().
     *
     * @return array<int, array<int, string|int|float|null>>
     */
    public function rows(): array;

    /**
     * Indicadores agregados. Puede estar vacío.
     *
     * @return array<string, string>
     */
    public function summary(): array;
}
