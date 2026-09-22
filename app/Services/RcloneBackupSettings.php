<?php
namespace App\Services;

use App\Models\DeveloperSetting;
use App\Models\BackupRun;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class RcloneBackupSettings
{
    public static function current(): array
    {
        $json = DeveloperSetting::string('google_drive_backup_settings');
        $saved = $json === null ? [] : json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        $path = (string) config('backup.google_drive.rclone_config_path', 'storage/app/google-drive/rclone.conf');
        return [
            'enabled' => $saved['enabled'] ?? (bool) config('backup.google_drive.enabled', false),
            'remote' => $saved['remote'] ?? trim((string) config('backup.google_drive.rclone_remote', 'coqpbackup')),
            'folder_id' => $saved['folder_id'] ?? trim((string) config('backup.google_drive.folder_id')),
            'config_path' => str_starts_with($path, '/') ? $path : base_path($path),
        ];
    }

    public static function binary(): string
    {
        return trim((string) config('backup.google_drive.rclone_binary', 'rclone')) ?: 'rclone';
    }

    public static function connections(): array
    {
        $path = static::current()['config_path'];
        if (! is_readable($path)) { return []; }
        // Only return names of Drive sections, never token or credential values.
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) { return []; }
        $section = null; $names = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*\[([^\]]+)\]\s*$/', $line, $match)) {
                $section = $match[1];
            } elseif ($section !== null && preg_match('/^\s*type\s*=\s*drive\s*$/', $line)) {
                if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_. -]{0,127}$/D', $section)) { $names[] = $section; }
            }
        }
        return array_values(array_unique($names));
    }

    public static function folderId(string $input): string
    {
        $input = trim($input);
        if (str_contains($input, '://')) {
            $url = parse_url($input);
            if (! is_array($url) || ($url['scheme'] ?? '') !== 'https'
                || strtolower($url['host'] ?? '') !== 'drive.google.com'
                || isset($url['user']) || isset($url['pass']) || isset($url['port'])
                || ! preg_match('~^/drive/(?:u/\d+/)?folders/([A-Za-z0-9_-]+)/?$~', $url['path'] ?? '', $match)) {
                throw new RuntimeException('Enter a Google Drive folder URL or folder ID.');
            }
            $input = $match[1];
        }
        if (! preg_match('/^[A-Za-z0-9_-]{10,200}$/D', $input)) {
            throw new RuntimeException('Enter a valid Google Drive folder ID.');
        }
        return $input;
    }

    public static function validateDestination(array $destination): array
    {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_. -]{0,127}$/D', $destination['remote'] ?? '')
            || ! is_string($destination['config_path'] ?? null)
            || ! str_starts_with($destination['config_path'], '/')
            || str_contains($destination['config_path'], "\0")) {
            throw new RuntimeException('Invalid saved backup destination.');
        }
        return [
            'remote' => $destination['remote'],
            'folder_id' => static::folderId($destination['folder_id'] ?? ''),
            'config_path' => $destination['config_path'],
        ];
    }

    public static function destinationFor(BackupRun $backup): ?array
    {
        if (! is_array($backup->google_drive_destination)) { return null; }
        $destination = static::validateDestination($backup->google_drive_destination);
        if (basename($backup->filename) !== $backup->filename
            || str_contains($backup->filename, '\\')
            || ! str_ends_with($backup->filename, '.sql.gz')
            || $backup->google_drive_path !== $destination['remote'].':'.$backup->filename) {
            throw new RuntimeException('Backup path does not match its recorded destination.');
        }
        return $destination;
    }

    public static function command(string $action, array $destination, ?string $filename = null, ?string $localPath = null): array
    {
        $destination = static::validateDestination($destination);
        if (! in_array($action, ['lsf', 'copyto', 'deletefile'], true)) { throw new RuntimeException('Invalid backup operation.'); }
        if ($action !== 'lsf' && ($filename === null || basename($filename) !== $filename
            || str_contains($filename, '\\') || ! str_ends_with($filename, '.sql.gz'))) {
            throw new RuntimeException('Invalid backup filename.');
        }
        $parts = [static::binary(), $action];
        if ($action === 'copyto') {
            if ($localPath === null) { throw new RuntimeException('Missing local backup.'); }
            $parts[] = $localPath;
        }
        $parts[] = $destination['remote'].':'.($filename ?? '');
        array_push($parts, '--config', $destination['config_path'], '--drive-root-folder-id', $destination['folder_id']);
        if ($action === 'copyto') { $parts[] = '--no-traverse'; }
        array_push($parts, '--retries', '1', '--low-level-retries', '1', '--contimeout', '10s', '--timeout', '30s');
        return $parts;
    }

    public static function verify(array $destination): void
    {
        $result = Process::timeout(45)->run(static::command('lsf', $destination));
        if (! $result->successful()) {
            throw new RuntimeException('Folder could not be listed. Check the connection login, folder ID, and sharing access.');
        }
    }
}
