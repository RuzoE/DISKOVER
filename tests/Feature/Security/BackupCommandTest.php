<?php

namespace Tests\Feature\Security;

use App\Console\Commands\BackupDatabase;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class BackupCommandTest extends TestCase
{
    public function test_command_fails_when_connection_is_not_mysql(): void
    {
        config()->set('database.default', 'sqlite');

        $this->artisan('dsle:backup')
            ->expectsOutputToContain("Sólo se admite la conexión 'mysql'")
            ->assertExitCode(1);
    }

    public function test_rotation_keeps_only_the_newest_backups(): void
    {
        $dir = storage_path('app/testing-backups-'.uniqid());
        File::ensureDirectoryExists($dir);

        try {
            foreach (range(1, 6) as $i) {
                $path = $dir.DIRECTORY_SEPARATOR."dsle-2026010{$i}-000000.sql.gz";
                File::put($path, 'x');
                touch($path, now()->subDays(6 - $i)->getTimestamp());
            }

            $rotate = new ReflectionMethod(BackupDatabase::class, 'rotate');
            $removed = $rotate->invoke(app(BackupDatabase::class), $dir, 3);

            $remaining = collect(File::glob($dir.DIRECTORY_SEPARATOR.'dsle-*.sql.gz'));

            $this->assertSame(3, $removed);
            $this->assertCount(3, $remaining);
            // Los tres más recientes (índices 4, 5 y 6) deben permanecer.
            $this->assertTrue($remaining->contains(fn ($f) => str_contains($f, '20260106')));
            $this->assertFalse($remaining->contains(fn ($f) => str_contains($f, '20260101')));
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_rotation_is_a_no_op_when_keep_is_zero(): void
    {
        $dir = storage_path('app/testing-backups-'.uniqid());
        File::ensureDirectoryExists($dir);

        try {
            File::put($dir.DIRECTORY_SEPARATOR.'dsle-20260101-000000.sql.gz', 'x');

            $rotate = new ReflectionMethod(BackupDatabase::class, 'rotate');
            $removed = $rotate->invoke(app(BackupDatabase::class), $dir, 0);

            $this->assertSame(0, $removed);
            $this->assertCount(1, File::glob($dir.DIRECTORY_SEPARATOR.'dsle-*.sql.gz'));
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
