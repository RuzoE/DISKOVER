<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Services\Reports\Contracts\Report;

/**
 * Portabilidad de datos personales: reúne en un único informe la información que
 * DSLE guarda sobre una persona (cuenta, roles, matrículas, calificaciones,
 * actividad). El estudiante puede descargarlo en CSV desde su perfil (ADR-0014).
 */
class PersonalDataReport implements Report
{
    public function __construct(private readonly User $user) {}

    public function key(): string
    {
        return 'mis-datos-'.$this->user->getKey();
    }

    public function title(): string
    {
        return 'Datos personales de '.$this->user->name;
    }

    public function meta(): array
    {
        return [
            'Generado' => now()->format('d/m/Y H:i'),
            'Titular' => $this->user->name,
            'Correo' => $this->user->email,
        ];
    }

    public function headings(): array
    {
        return ['Sección', 'Campo', 'Valor'];
    }

    public function rows(): array
    {
        $u = $this->user->loadMissing('roles', 'directPermissions');
        $rows = [];

        $rows[] = ['Cuenta', 'Nombre', $u->name];
        $rows[] = ['Cuenta', 'Correo', $u->email];
        $rows[] = ['Cuenta', 'Estado', $u->status->label()];
        $rows[] = ['Cuenta', 'Alta', $u->created_at?->format('d/m/Y H:i')];
        $rows[] = ['Cuenta', 'Último acceso', $u->last_login_at?->format('d/m/Y H:i') ?? '—'];
        $rows[] = ['Cuenta', 'Roles', $u->roles->pluck('name')->implode(', ') ?: '—'];
        $rows[] = ['Cuenta', 'Permisos directos', $u->directPermissions->pluck('name')->implode(', ') ?: '—'];

        foreach ($u->enrollments()->with('course')->get() as $enrollment) {
            $rows[] = [
                'Matrículas',
                $enrollment->course?->name ?? 'Curso #'.$enrollment->course_id,
                $enrollment->status->value.' · inscrito '.$enrollment->enrolled_at?->format('d/m/Y'),
            ];
        }

        foreach ($u->grades()->with('activity')->get() as $grade) {
            $rows[] = [
                'Calificaciones',
                $grade->activity?->title ?? 'Actividad #'.$grade->activity_id,
                (string) $grade->score,
            ];
        }

        $rows[] = ['Actividad', 'Eventos de aprendizaje', (string) $u->learningEvents()->count()];
        $rows[] = ['Actividad', 'Intentos de evaluación', (string) $u->attempts()->count()];
        $rows[] = ['Actividad', 'Conversaciones con el asistente', (string) $u->aiConversations()->count()];
        $rows[] = ['Actividad', 'Sesiones inmersivas', (string) $u->immersiveSessions()->count()];
        $rows[] = ['Actividad', 'Contenidos completados', (string) $u->completedContents()->count()];

        return $rows;
    }

    public function summary(): array
    {
        return [];
    }
}
