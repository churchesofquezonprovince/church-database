<?php

namespace App\Services;

use App\Models\DeveloperSetting;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Drive;
use Google\Service\Sheets;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleIntegrationSettings
{
    public const PREFIXES = [
        'calendar' => 'services.google_calendar',
        'children' => 'children_work.google_sheets',
    ];

    public static function saved(string $feature): ?array
    {
        $json = DeveloperSetting::string('google_integration_'.$feature);
        return $json === null ? null : json_decode($json, true, 32, JSON_THROW_ON_ERROR);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        foreach (static::PREFIXES as $feature => $prefix) {
            if (str_starts_with($key, $prefix.'.')) {
                $field = substr($key, strlen($prefix) + 1);
                $saved = static::saved($feature);
                if ($field === 'credentials_path' && filled($saved['account'] ?? null)) {
                    return static::accountPath($saved['account']);
                }
                if ($saved !== null && array_key_exists($field, $saved)) {
                    return $saved[$field];
                }
            }
        }
        return config($key, $default);
    }

    public static function legacyPath(string $feature): string
    {
        if ($feature === 'minutes') {
            $replacement = storage_path('app/service-meeting-auth/credentials.json');
            return is_file($replacement) ? $replacement : storage_path('app/google-credentials.json');
        }
        if (! isset(static::PREFIXES[$feature])) {
            throw new RuntimeException('Unknown integration.');
        }
        $path = (string) config(static::PREFIXES[$feature].'.credentials_path');
        if ($path === '') {
            throw new RuntimeException('No credentials configured.');
        }
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    public static function accountPath(string $id): string
    {
        if (in_array($id, ['existing_minutes', 'existing_calendar', 'existing_children'], true)) {
            return static::legacyPath(substr($id, 9));
        }
        if (! Str::isUuid($id) || DeveloperSetting::string('google_account_'.$id) === null) {
            throw new RuntimeException('Choose a registered account.');
        }
        return storage_path('app/google-integration-accounts/'.$id.'.json');
    }

    public static function path(string $feature, ?string $selection = null): string
    {
        $selection ??= static::saved($feature)['account'] ?? '';
        return $selection === '' ? static::legacyPath($feature) : static::accountPath($selection);
    }

    public static function credentialsAt(string $path): array
    {
        if (! is_readable($path) || filesize($path) > 65536) {
            throw new RuntimeException('Credential file missing or unreadable.');
        }
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException('Cannot read credentials.');
        }
        return ServiceMeetingGoogleAccount::credentials($json);
    }

    public static function accounts(): array
    {
        $result = [];
        foreach (['minutes' => 'Existing Minutes account', 'calendar' => 'Existing Calendar account', 'children' => "Existing Children’s Work account"] as $feature => $label) {
            try {
                $credentials = static::credentialsAt(static::legacyPath($feature));
                $result['existing_'.$feature] = $label.' — '.$credentials['client_email'];
            } catch (\Throwable) {
                // Missing legacy files do not prevent adding another account.
            }
        }
        foreach (DeveloperSetting::query()->where('key', 'like', 'google_account_%')->get() as $row) {
            $id = substr($row->key, strlen('google_account_'));
            if (! Str::isUuid($id)) {
                continue;
            }
            $metadata = json_decode($row->value, true, 32, JSON_THROW_ON_ERROR);
            $result[$id] = $metadata['label'].' — '.$metadata['email'];
        }
        return $result;
    }

    public static function addAccount(string $label, string $json): string
    {
        $credentials = ServiceMeetingGoogleAccount::credentials($json);
        $id = (string) Str::uuid();
        $directory = storage_path('app/google-integration-accounts');
        if ((! is_dir($directory) && ! mkdir($directory, 0700, true)) || ! chmod($directory, 0700)) {
            throw new RuntimeException('Cannot secure account storage.');
        }
        $path = $directory.'/'.$id.'.json';
        $handle = fopen($path, 'x');
        if ($handle === false) {
            throw new RuntimeException('Cannot create account file.');
        }
        try {
            if (! chmod($path, 0600)) {
                throw new RuntimeException('Cannot protect account file.');
            }
            $data = json_encode($credentials, JSON_THROW_ON_ERROR);
            if (fwrite($handle, $data) !== strlen($data) || ! fflush($handle)) {
                throw new RuntimeException('Cannot save credentials.');
            }
            DeveloperSetting::putValue('google_account_'.$id, json_encode([
                'label' => $label, 'email' => $credentials['client_email'],
            ], JSON_THROW_ON_ERROR));
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($path);
            throw $e;
        }
        fclose($handle);
        return $id;
    }

    public static function client(string $feature, string $account): Client
    {
        $client = new Client();
        $client->setAuthConfig(static::credentialsAt(static::path($feature, $account)));
        $client->setScopes(match ($feature) {
            'calendar' => [Calendar::CALENDAR],
            'children' => [Sheets::SPREADSHEETS],
            'minutes' => [Drive::DRIVE_READONLY],
        });
        $client->setHttpClient(new \GuzzleHttp\Client(['connect_timeout' => 5, 'timeout' => 15]));
        return $client;
    }

    // These checks only read metadata; they never create or modify Google content.
    public static function verify(string $feature, array $settings): void
    {
        $client = static::client($feature, $settings['account']);
        if ($feature === 'minutes') {
            $drive = new Drive($client);
            $folder = $drive->files->get(\App\Models\DriveMeetingDocument::serviceMeetingFolderId(), [
                'fields' => 'mimeType,trashed', 'supportsAllDrives' => true,
            ]);
            if ($folder->getMimeType() !== 'application/vnd.google-apps.folder' || $folder->getTrashed()) {
                throw new RuntimeException('Not an active folder.');
            }
        } elseif ($feature === 'calendar') {
            $service = new Calendar($client);
            foreach ($settings['calendars'] as $calendar) {
                $service->events->listEvents($calendar['id'], ['maxResults' => 1]);
            }
        } else {
            $service = new Sheets($client);
            $spreadsheet = $service->spreadsheets->get($settings['spreadsheet_id']);
            if ($settings['sheet_name'] !== '' && ! collect($spreadsheet->getSheets())->contains(
                fn ($sheet) => $sheet->getProperties()->getTitle() === $settings['sheet_name']
            )) {
                throw new RuntimeException('Sheet tab not found.');
            }
        }
    }
}
