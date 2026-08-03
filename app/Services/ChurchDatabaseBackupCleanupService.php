<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ChurchDatabaseBackupCleanupService
{
    public function cleanup(bool $dryRun = false): array
    {
        $keepLatest = max((int) config('backup.retention.keep_latest', 30), 1);
        $keepDays = max((int) config('backup.retention.keep_days', 30), 1);
        $cutoff = now()->subDays($keepDays);

        $keepIds = BackupRun::query()
            ->latest('started_at')
            ->limit($keepLatest)
            ->pluck('id')
            ->all();

        $oldBackups = BackupRun::query()
            ->whereNotIn('id', $keepIds)
            ->where('started_at', '<', $cutoff)
            ->orderBy('started_at')
            ->get();

        $stats = [
            'dry_run' => $dryRun,
            'checked' => $oldBackups->count(),
            'local_deleted' => 0,
            'external_deleted' => 0,
            'google_drive_deleted' => 0,
            'errors' => 0,
        ];

        foreach ($oldBackups as $backup) {
            try {
                if ($this->deleteFileIfSafe($backup->local_path, config('backup.local_path'), $dryRun)) {
                    $stats['local_deleted']++;
                }

                if ($this->deleteFileIfSafe($backup->external_path, config('backup.external_path'), $dryRun)) {
                    $stats['external_deleted']++;

                    if (! $dryRun) {
                        $backup->external_status = 'cleaned';
                    }
                }

                if (
                    (bool) config('backup.retention.cleanup_google_drive', true)
                    && $backup->google_drive_status === 'uploaded'
                    && $this->deleteGoogleDriveFile($backup, $dryRun)
                ) {
                    $stats['google_drive_deleted']++;

                    if (! $dryRun) {
                        $backup->google_drive_status = 'cleaned';
                    }
                }

                if (! $dryRun) {
                    $backup->save();
                }
            } catch (Throwable $exception) {
                $stats['errors']++;

                if (! $dryRun) {
                    $backup->update([
                        'error_message' => trim(($backup->error_message ? $backup->error_message . PHP_EOL : '') . 'Cleanup failed: ' . $exception->getMessage()),
                    ]);
                }
            }
        }

        return $stats;
    }

    protected function deleteFileIfSafe(?string $path, ?string $allowedDirectory, bool $dryRun): bool
    {
        $path = trim((string) $path);

        if ($path === '' || ! str_ends_with($path, '.sql.gz')) {
            return false;
        }

        $allowedDirectory = $this->normalizePath((string) $allowedDirectory, base_path());

        if (! Str::startsWith($path, rtrim($allowedDirectory, '/') . '/')) {
            return false;
        }

        if (! File::exists($path)) {
            return false;
        }

        if (! $dryRun) {
            File::delete($path);
        }

        return true;
    }

    protected function deleteGoogleDriveFile(BackupRun $backup, bool $dryRun): bool
    {
        $remotePath = trim((string) $backup->google_drive_path);

        if ($remotePath === '') {
            $remote = trim((string) config('backup.google_drive.rclone_remote', 'coqpbackup'));
            $remotePath = $remote . ':' . $backup->filename;
        }

        if (! str_ends_with($backup->filename, '.sql.gz')) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        $rcloneConfigPath = $this->normalizePath(
            (string) config('backup.google_drive.rclone_config_path', 'storage/app/google-drive/rclone.conf'),
            base_path()
        );

        $folderId = trim((string) config('backup.google_drive.folder_id'));
        $binary = trim((string) config('backup.google_drive.rclone_binary', 'rclone')) ?: 'rclone';

        $command = $this->shellCommand([
            $binary,
            'deletefile',
            $remotePath,
            '--config',
            $rcloneConfigPath,
            '--drive-root-folder-id',
            $folderId,
        ]);

        $output = [];
        $exitCode = 0;

        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(implode(PHP_EOL, $output)) ?: 'Google Drive cleanup failed.');
        }

        return true;
    }

    protected function shellCommand(array $parts): string
    {
        return collect($parts)
            ->map(fn (mixed $part): string => escapeshellarg((string) $part))
            ->implode(' ');
    }

    protected function normalizePath(string $path, string $relativeBase): string
    {
        if (Str::startsWith($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim($relativeBase . DIRECTORY_SEPARATOR . $path, '/');
    }
}
