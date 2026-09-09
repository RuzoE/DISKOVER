<?php

namespace Tests\Feature\Security;

use App\Console\Commands\BackupDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use ReflectionMethod;
use Tests\TestCase;

class BackupCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('dsle.backups.path', storage_path('app/testing-backups-'.uniqid()));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(config('dsle.backups.path'));
        parent::tearDown();
    }

    public function test_command_fails_when_connection_is_not_mysql(): void
    {
        config()->set('database.default', 'sqlite');

        $this->artisan('dsle:backup')
            ->expectsOutputToContain("Sólo se admite la conexión 'mysql'")
            ->assertExitCode(1);
    }

    public function test_command_fails_cleanly_when_mysqldump_errors(): void
    {
        Process::fake([
            '*' => Process::result(output: '', errorOutput: 'command not found: mysqldump', exitCode: 127),
        ]);

        $this->artisan('dsle:backup')
            ->expectsOutputToContain('mysqldump falló')
            ->assertExitCode(1);

        $this->assertEmpty(File::glob(config('dsle.backups.path').'/dsle-*'));
    }

    public function test_command_fails_when_the_dump_file_is_missing(): void
    {
        // El proceso "tiene éxito" pero --result-file no escribió nada.
        Process::fake(['*' => Process::result(exitCode: 0)]);

        $this->artisan('dsle:backup')->assertExitCode(1);

        $this->assertEmpty(File::glob(config('dsle.backups.path').'/dsle-*'));
    }

    public function test_mysqldump_is_invoked_with_a_consistent_transaction_snapshot(): void
    {
        Process::fake(['*' => Process::result(exitCode: 1)]);

        $this->artisan('dsle:backup');

        Process::assertRan(function ($process) {
            $command = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($command, 'mysqldump')
                && str_contains($command, '--single-transaction')
                && str_contains($command, '--result-file=');
        });
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
