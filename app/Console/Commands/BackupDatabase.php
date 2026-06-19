<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackupMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Dump the MySQL database, compress it, prune old backups, and email off-site copy';

    public function handle(): int
    {
        $connection = config('database.connections.mysql');
        $database = $connection['database'] ?? null;

        if (! $database) {
            $this->error('MySQL database name is not configured.');
            Log::error('db:backup failed: MySQL database name is not configured.');

            return self::FAILURE;
        }

        $backupPath = config('backup.path');
        $mysqldumpPath = config('backup.mysqldump_path');

        if (! is_file($mysqldumpPath)) {
            $this->error("mysqldump not found at: {$mysqldumpPath}");
            Log::error('db:backup failed: mysqldump not found.', ['path' => $mysqldumpPath]);

            return self::FAILURE;
        }

        if (! is_dir($backupPath) && ! mkdir($backupPath, 0755, true) && ! is_dir($backupPath)) {
            $this->error("Could not create backup directory: {$backupPath}");
            Log::error('db:backup failed: could not create backup directory.', ['path' => $backupPath]);

            return self::FAILURE;
        }

        $date = now()->format('Y-m-d');
        $sqlFile = $backupPath.DIRECTORY_SEPARATOR."{$database}_{$date}.sql";
        $gzFile = $sqlFile.'.gz';

        $this->info("Backing up database: {$database}");

        $dumpProcess = new Process($this->buildMysqldumpCommand(
            $mysqldumpPath,
            $connection,
            $database,
            $sqlFile
        ));

        $dumpProcess->setTimeout(600);
        $dumpProcess->run();

        if (! $dumpProcess->isSuccessful()) {
            @unlink($sqlFile);
            $this->error('mysqldump failed: '.$dumpProcess->getErrorOutput());
            Log::error('db:backup failed: mysqldump error.', [
                'output' => $dumpProcess->getErrorOutput(),
            ]);

            return self::FAILURE;
        }

        if (! is_file($sqlFile) || filesize($sqlFile) === 0) {
            @unlink($sqlFile);
            $this->error('Backup file is missing or empty.');
            Log::error('db:backup failed: backup file is missing or empty.');

            return self::FAILURE;
        }

        $gzOk = $this->gzipFile($sqlFile, $gzFile);

        @unlink($sqlFile);

        if (! $gzOk || ! is_file($gzFile)) {
            $this->error('Failed to compress backup file.');
            Log::error('db:backup failed: gzip compression failed.');

            return self::FAILURE;
        }

        $this->info('Backup saved: '.$gzFile);
        Log::info('db:backup succeeded.', ['file' => $gzFile]);

        $this->pruneOldBackups($backupPath, $database);

        $this->sendBackupEmail($gzFile, $database, $date);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return list<string>
     */
    private function buildMysqldumpCommand(
        string $mysqldumpPath,
        array $connection,
        string $database,
        string $outputFile
    ): array {
        $command = [
            $mysqldumpPath,
            '--host='.$connection['host'],
            '--port='.($connection['port'] ?? '3306'),
            '--user='.$connection['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            '--result-file='.$outputFile,
            $database,
        ];

        if (! empty($connection['password'])) {
            $command[] = '--password='.$connection['password'];
        }

        return $command;
    }

    private function gzipFile(string $source, string $destination): bool
    {
        $input = fopen($source, 'rb');

        if ($input === false) {
            return false;
        }

        $output = gzopen($destination, 'wb9');

        if ($output === false) {
            fclose($input);

            return false;
        }

        while (! feof($input)) {
            $chunk = fread($input, 1024 * 512);

            if ($chunk === false) {
                fclose($input);
                gzclose($output);
                @unlink($destination);

                return false;
            }

            gzwrite($output, $chunk);
        }

        fclose($input);
        gzclose($output);

        return true;
    }

    private function pruneOldBackups(string $backupPath, string $database): void
    {
        $retentionCount = config('backup.retention_count', 10);
        $pattern = $backupPath.DIRECTORY_SEPARATOR.$database.'_*.sql.gz';
        $files = glob($pattern) ?: [];

        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $filesToDelete = array_slice($files, $retentionCount);

        foreach ($filesToDelete as $file) {
            if (@unlink($file)) {
                $this->line('Deleted old backup: '.basename($file));
                Log::info('db:backup pruned old file.', ['file' => $file]);
            }
        }
    }

    private function sendBackupEmail(string $gzFile, string $database, string $date): void
    {
        $recipient = config('backup.email');

        if (! $recipient) {
            $this->warn('BACKUP_EMAIL is not set; skipping off-site email.');
            Log::warning('db:backup skipped email: BACKUP_EMAIL is not set.');

            return;
        }

        $maxBytes = config('backup.max_email_mb', 20) * 1024 * 1024;
        $fileSize = filesize($gzFile);

        if ($fileSize > $maxBytes) {
            $sizeMb = round($fileSize / 1024 / 1024, 2);
            $this->warn("Backup ({$sizeMb} MB) exceeds email limit; local copy kept only.");
            Log::warning('db:backup skipped email: file too large.', [
                'file' => $gzFile,
                'size_mb' => $sizeMb,
            ]);

            return;
        }

        try {
            Mail::to($recipient)->send(new DatabaseBackupMail($gzFile, $database, $date));
            $this->info("Backup emailed to: {$recipient}");
            Log::info('db:backup email sent.', ['recipient' => $recipient]);
        } catch (\Throwable $e) {
            $this->warn('Local backup saved, but email failed: '.$e->getMessage());
            Log::error('db:backup email failed.', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
