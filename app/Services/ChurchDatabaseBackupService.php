<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ChurchDatabaseBackupService
{
    public function run(bool $copyToExternal = true): BackupRun
    {
        $startedAt = now();

        $databaseName = (string) DB::connection()->getDatabaseName();

        $filename = sprintf(
            '%s-%s.sql.gz',
            config('backup.filename_prefix', 'church-database'),
            $startedAt->timezone(config('app.timezone'))->format('Ymd-His')
        );

        $localDirectory = $this->normalizePath((string) config('backup.local_path'), base_path());
        $localPath = $localDirectory . DIRECTORY_SEPARATOR . $filename;

        File::ensureDirectoryExists($localDirectory, 0750, true);

        $backupRun = BackupRun::create([
            'filename' => $filename,
            'status' => 'running',
            'database_name' => $databaseName,
            'local_path' => $localPath,
            'external_status' => $copyToExternal ? 'pending' : 'skipped',
            'started_at' => $startedAt,
        ]);

        try {
            $this->dumpDatabaseToGzip($localPath);

            clearstatcache(true, $localPath);

            $backupRun->update([
                'status' => 'completed',
                'local_size_bytes' => File::size($localPath),
                'finished_at' => now(),
            ]);

            if ($copyToExternal) {
                $this->copyBackupToExternalStorage($backupRun, $localPath, $filename);
            }

            return $backupRun->fresh();
        } catch (Throwable $exception) {
            $backupRun->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 5000),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    protected function dumpDatabaseToGzip(string $path): void
    {
        $handle = gzopen($path, 'wb9');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open backup file for writing: {$path}");
        }

        try {
            $pdo = DB::connection()->getPdo();
            $databaseName = (string) DB::connection()->getDatabaseName();

            $this->gzWriteLine($handle, '-- COQP Database Backup');
            $this->gzWriteLine($handle, '-- Database: ' . $databaseName);
            $this->gzWriteLine($handle, '-- Generated: ' . now()->timezone(config('app.timezone'))->toDateTimeString());
            $this->gzWriteLine($handle, '');
            $this->gzWriteLine($handle, 'SET FOREIGN_KEY_CHECKS=0;');
            $this->gzWriteLine($handle, 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";');
            $this->gzWriteLine($handle, '');

            foreach ($this->tables() as $table) {
                $this->dumpTable($handle, $pdo, $table);
            }

            $this->gzWriteLine($handle, 'SET FOREIGN_KEY_CHECKS=1;');
        } finally {
            gzclose($handle);
        }
    }

    protected function dumpTable($handle, \PDO $pdo, string $table): void
    {
        $quotedTable = $this->quoteIdentifier($table);

        $this->gzWriteLine($handle, '');
        $this->gzWriteLine($handle, '-- Table: ' . $table);
        $this->gzWriteLine($handle, 'DROP TABLE IF EXISTS ' . $quotedTable . ';');

        $createRow = (array) DB::selectOne('SHOW CREATE TABLE ' . $quotedTable);
        $createStatement = $createRow['Create Table'] ?? array_values($createRow)[1] ?? null;

        if (! $createStatement) {
            throw new \RuntimeException("Unable to get CREATE TABLE statement for {$table}");
        }

        $this->gzWriteLine($handle, $createStatement . ';');
        $this->gzWriteLine($handle, '');

        $statement = $pdo->query('SELECT * FROM ' . $quotedTable);

        if (! $statement) {
            return;
        }

        $batch = [];
        $columnsSql = null;

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            if ($columnsSql === null) {
                $columnsSql = collect(array_keys($row))
                    ->map(fn (string $column): string => $this->quoteIdentifier($column))
                    ->implode(', ');
            }

            $valuesSql = collect(array_values($row))
                ->map(fn (mixed $value): string => $this->sqlValue($pdo, $value))
                ->implode(', ');

            $batch[] = '(' . $valuesSql . ')';

            if (count($batch) >= 100) {
                $this->writeInsertBatch($handle, $quotedTable, $columnsSql, $batch);
                $batch = [];
            }
        }

        if ($batch !== [] && $columnsSql !== null) {
            $this->writeInsertBatch($handle, $quotedTable, $columnsSql, $batch);
        }
    }

    protected function writeInsertBatch($handle, string $quotedTable, string $columnsSql, array $batch): void
    {
        $this->gzWriteLine(
            $handle,
            'INSERT INTO ' . $quotedTable . ' (' . $columnsSql . ') VALUES' . PHP_EOL .
            implode(',' . PHP_EOL, $batch) . ';'
        );
    }

    protected function tables(): array
    {
        return collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn (object $row): string => (string) array_values((array) $row)[0])
            ->values()
            ->all();
    }

    protected function copyBackupToExternalStorage(BackupRun $backupRun, string $localPath, string $filename): void
    {
        $externalDirectory = (string) config('backup.external_path');

        if (blank($externalDirectory)) {
            $backupRun->update([
                'external_status' => 'skipped',
            ]);

            return;
        }

        $externalDirectory = $this->normalizePath($externalDirectory, base_path());
        $externalPath = $externalDirectory . DIRECTORY_SEPARATOR . $filename;

        try {
            File::ensureDirectoryExists($externalDirectory, 0750, true);

            if (! is_writable($externalDirectory)) {
                throw new \RuntimeException("External backup directory is not writable: {$externalDirectory}");
            }

            if (! copy($localPath, $externalPath)) {
                throw new \RuntimeException("Failed to copy backup to external storage: {$externalPath}");
            }

            clearstatcache(true, $externalPath);

            $backupRun->update([
                'external_path' => $externalPath,
                'external_status' => 'copied',
                'external_size_bytes' => File::size($externalPath),
            ]);
        } catch (Throwable $exception) {
            $backupRun->update([
                'external_path' => $externalPath,
                'external_status' => 'failed',
                'error_message' => trim(($backupRun->error_message ? $backupRun->error_message . PHP_EOL : '') . 'External copy failed: ' . $exception->getMessage()),
            ]);
        }
    }

    protected function sqlValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $pdo->quote((string) $value);
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    protected function gzWriteLine($handle, string $line): void
    {
        gzwrite($handle, $line . PHP_EOL);
    }

    protected function normalizePath(string $path, string $relativeBase): string
    {
        if (Str::startsWith($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim($relativeBase . DIRECTORY_SEPARATOR . $path, '/');
    }
}
