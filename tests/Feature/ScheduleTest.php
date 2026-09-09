<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * El programador debe lanzar la copia de seguridad a diario (Fase 11 + 13).
 * En producción lo despierta un cron cada minuto (deploy/dsle-scheduler.cron)
 * o el servicio "scheduler" de docker-compose.
 */
class ScheduleTest extends TestCase
{
    public function test_database_backup_is_scheduled_daily(): void
    {
        $schedule = app(Schedule::class);

        $backup = collect($schedule->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'dsle:backup'));

        $this->assertNotNull($backup, 'dsle:backup no está en el programador.');
        $this->assertSame('30 2 * * *', $backup->expression);
    }

    public function test_backup_command_is_registered(): void
    {
        $this->assertArrayHasKey('dsle:backup', Artisan::all());
    }
}
