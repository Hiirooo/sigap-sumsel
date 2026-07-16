<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep= : Jumlah file backup terbaru yang disimpan}';

    protected $description = 'Membuat backup database lokal ke storage/app/backups.';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $backupDirectory = storage_path('app/backups');

        File::ensureDirectoryExists($backupDirectory);

        $timestamp = now()->format('Ymd_His');
        $appName = Str::slug((string) config('app.name', 'sigap-sumsel'));

        if ($config['driver'] === 'sqlite') {
            return $this->backupSqlite($config, $backupDirectory, $appName, $timestamp);
        }

        if (in_array($config['driver'], ['mysql', 'mariadb'], true)) {
            return $this->backupMysql($config, $backupDirectory, $appName, $timestamp);
        }

        $this->error("Driver database '{$config['driver']}' belum didukung oleh backup lokal.");

        return self::FAILURE;
    }

    private function backupSqlite(array $config, string $backupDirectory, string $appName, string $timestamp): int
    {
        $databasePath = $config['database'];

        if (! is_string($databasePath) || ! File::exists($databasePath)) {
            $this->error('File database SQLite tidak ditemukan.');

            return self::FAILURE;
        }

        $target = "{$backupDirectory}/{$appName}_sqlite_{$timestamp}.sqlite";
        File::copy($databasePath, $target);
        $this->pruneOldBackups($backupDirectory);

        $this->info("Backup database berhasil dibuat: {$target}");

        return self::SUCCESS;
    }

    private function backupMysql(array $config, string $backupDirectory, string $appName, string $timestamp): int
    {
        $target = "{$backupDirectory}/{$appName}_mysql_{$timestamp}.sql";
        $command = [
            'mysqldump',
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            '--result-file=' . $target,
            $config['database'],
        ];

        if (($config['password'] ?? '') !== '') {
            $command[] = '--password=' . $config['password'];
        }

        $result = Process::timeout(120)->run($command);

        if ($result->failed()) {
            $this->error('Backup database gagal: ' . trim($result->errorOutput() ?: $result->output()));

            return self::FAILURE;
        }

        $this->pruneOldBackups($backupDirectory);
        $this->info("Backup database berhasil dibuat: {$target}");

        return self::SUCCESS;
    }

    private function pruneOldBackups(string $backupDirectory): void
    {
        $keep = (int) ($this->option('keep') ?: env('BACKUP_KEEP_FILES', 14));

        if ($keep < 1) {
            return;
        }

        $files = collect(File::files($backupDirectory))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $files->slice($keep)->each(fn ($file) => File::delete($file->getPathname()));
    }
}
