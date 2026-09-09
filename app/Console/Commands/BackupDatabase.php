<?php

namespace App\Console\Commands;

use App\Services\Security\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Copia de seguridad de la base de datos MySQL con `mysqldump` (opción nativa
 * `--result-file`, sin tubería de shell), comprimida con gzip en PHP y con
 * rotación (se conservan las N más recientes). Ver ADR-0014.
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

        $stamp = now()->format('Ymd-His');
        $sqlFile = $path.DIRECTORY_SEPARATOR."dsle-{$stamp}.sql";
        $gzFile = $sqlFile.'.gz';

        $result = Process::timeout(600)
            ->env(['MYSQL_PWD' => (string) $db['password']])
            ->run([
                config('dsle.backups.mysqldump_path', 'mysqldump'),
                '--host='.$db['host'],
                '--port='.$db['port'],
                '--user='.$db['username'],
                '--single-transaction',
                '--quick',
                '--no-tablespaces',
                '--result-file='.$sqlFile,
                $db['database'],
            ]);

        if (! $result->successful() || ! File::exists($sqlFile) || File::size($sqlFile) === 0) {
            @File::delete($sqlFile);
            $reason = trim($result->errorOutput()) ?: 'el volcado quedó vacío.';
            $this->error('mysqldump falló: '.$reason);

            return self::FAILURE;
        }

        File::put($gzFile, gzencode(File::get($sqlFile), 9));
        File::delete($sqlFile);

        $keep = (int) ($this->option('keep') ?? config('dsle.backups.keep', 7));
        $removed = $this->rotate($path, $keep);

        $sizeKb = round(File::size($gzFile) / 1024, 1);
        $this->info('Copia creada: '.basename($gzFile)." ({$sizeKb} KB). Copias eliminadas por rotación: {$removed}.");

        $audit->record('backup.created', null, [
            'file' => basename($gzFile),
            'size_bytes' => File::size($gzFile),
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
