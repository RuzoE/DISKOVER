<?php

namespace App\Console\Commands;

use App\Services\Security\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Copia de seguridad de la base de datos MySQL con `mysqldump`, comprimida con
 * gzip y con rotación (se conservan las N más recientes). Ver ADR-0014.
 */
class BackupDatabase extends Command
{
    protected $signature = 'dsle:backup {--keep= : Número de copias a conservar (por defecto config dsle.backups.keep)}';

    protected $description = 'Genera una copia de seguridad comprimida de la base de datos y rota las antiguas.';

    public function handle(AuditLogger $audit): int
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            $this->error("Sólo se admite la conexión 'mysql' (actual: {$connection}).");

            return self::FAILURE;
        }

        $db = config('database.connections.mysql');
        $path = config('dsle.backups.path');
        File::ensureDirectoryExists($path);

        $filename = 'dsle-'.now()->format('Ymd-His').'.sql.gz';
        $target = $path.DIRECTORY_SEPARATOR.$filename;

        $dump = config('dsle.backups.mysqldump_path', 'mysqldump');

        $process = Process::fromShellCommandline(
            escapeshellarg($dump)
            .' --host='.escapeshellarg((string) $db['host'])
            .' --port='.escapeshellarg((string) $db['port'])
            .' --user='.escapeshellarg((string) $db['username'])
            .' --single-transaction --quick --no-tablespaces '
            .escapeshellarg((string) $db['database'])
            .' | gzip > '.escapeshellarg($target),
        );

        $process->setTimeout(600);
        $process->setEnv(['MYSQL_PWD' => (string) $db['password']]);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($target) || File::size($target) === 0) {
            @File::delete($target);
            $this->error('mysqldump falló: '.trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        $keep = (int) ($this->option('keep') ?? config('dsle.backups.keep', 7));
        $removed = $this->rotate($path, $keep);

        $sizeKb = round(File::size($target) / 1024, 1);
        $this->info("Copia creada: {$filename} ({$sizeKb} KB). Copias eliminadas por rotación: {$removed}.");

        $audit->record('backup.created', null, [
            'file' => $filename,
            'size_bytes' => File::size($target),
            'rotated_out' => $removed,
        ], 'Copia de seguridad de la base de datos');

        return self::SUCCESS;
    }

    /**
     * Conserva los `$keep` ficheros más recientes; elimina el resto.
     */
    private function rotate(string $path, int $keep): int
    {
        if ($keep < 1) {
            return 0;
        }

        $backups = collect(File::glob($path.DIRECTORY_SEPARATOR.'dsle-*.sql.gz'))
            ->sortByDesc(fn (string $file) => File::lastModified($file))
            ->values();

        $stale = $backups->slice($keep);

        foreach ($stale as $file) {
            File::delete($file);
        }

        return $stale->count();
    }
}
