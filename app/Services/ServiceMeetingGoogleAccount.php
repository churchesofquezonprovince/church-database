<?php

namespace App\Services;

use App\Models\DriveMeetingDocument;
use Google\Client;
use Google\Service\Drive;
use RuntimeException;

class ServiceMeetingGoogleAccount
{
    public static function replacementPath(): string
    {
        return storage_path('app/service-meeting-auth/credentials.json');
    }

    public static function activePath(): string
    {
        return \App\Services\GoogleIntegrationSettings::path('minutes');
    }

    public static function credentials(string $json): array
    {
        if (strlen($json) > 65536) {
            throw new RuntimeException('Credential file is too large.');
        }

        $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);

        if (
            ! is_array($data)
            || ($data['type'] ?? '') !== 'service_account'
            || ! is_string($data['client_email'] ?? null)
            || ! filter_var($data['client_email'], FILTER_VALIDATE_EMAIL)
            || ! str_ends_with(strtolower($data['client_email']), '.gserviceaccount.com')
            || ! is_string($data['private_key'] ?? null)
        ) {
            throw new RuntimeException('Not a service-account credential file.');
        }

        if (
            ! is_string($data['client_id'] ?? null)
            || ! preg_match('/^\d+$/', $data['client_id'])
        ) {
            throw new RuntimeException(
                'Credentials are missing client_id. Upload the original Google JSON key.'
            );
        }

        $key = openssl_pkey_get_private($data['private_key']);
        $details = $key ? openssl_pkey_get_details($key) : false;

        if (! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA) {
            throw new RuntimeException('Invalid service-account private key.');
        }

        // Use fixed Google endpoints, never endpoints supplied by an upload.
        return [
            'type' => 'service_account',
            'client_id' => $data['client_id'],
            'client_email' => $data['client_email'],
            'private_key' => $data['private_key'],
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];
    }

    public static function readCredentials(): array
    {
        $path = static::activePath();

        if (! is_readable($path) || filesize($path) > 65536) {
            throw new RuntimeException('Credentials are missing or unreadable.');
        }

        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException('Credentials could not be read.');
        }

        return static::credentials($json);
    }

    public static function status(): array
    {
        if (! is_file(static::activePath())) {
            return ['label' => 'Not configured', 'email' => null];
        }

        try {
            $credentials = static::readCredentials();

            return [
                'label' => 'Credentials configured',
                'email' => $credentials['client_email'],
            ];
        } catch (\Throwable) {
            return ['label' => 'Credentials need attention', 'email' => null];
        }
    }

    public static function client(?array $credentials = null): Client
    {
        $client = new Client();
        $client->setAuthConfig($credentials ?? static::readCredentials());
        $client->addScope(Drive::DRIVE_READONLY);
        $client->setHttpClient(new \GuzzleHttp\Client([
            'connect_timeout' => 10,
            'timeout' => 25,
        ]));

        return $client;
    }

    public static function testFolder(?array $credentials = null): void
    {
        $drive = new Drive(static::client($credentials));
        $folderId = DriveMeetingDocument::serviceMeetingFolderId();

        $folder = $drive->files->get($folderId, [
            'fields' => 'id,mimeType,trashed',
            'supportsAllDrives' => true,
        ]);

        if (
            $folder->getMimeType() !== 'application/vnd.google-apps.folder'
            || $folder->getTrashed()
        ) {
            throw new RuntimeException('The configured item is not an active folder.');
        }

        $drive->files->listFiles([
            'q' => "'{$folderId}' in parents and trashed = false",
            'fields' => 'files(id)',
            'pageSize' => 1,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);
    }

    public static function install(array $credentials): void
    {
        $directory = dirname(static::replacementPath());

        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            throw new RuntimeException('Cannot create credential directory.');
        }

        if (! chmod($directory, 0700)) {
            throw new RuntimeException('Cannot secure credential directory.');
        }

        $temporary = tempnam($directory, '.upload-');

        if ($temporary === false) {
            throw new RuntimeException('Cannot prepare credential file.');
        }

        try {
            if (
                ! chmod($temporary, 0600)
                || file_put_contents(
                    $temporary,
                    json_encode($credentials, JSON_THROW_ON_ERROR),
                    LOCK_EX
                ) === false
            ) {
                throw new RuntimeException('Cannot write credential file.');
            }

            if (! rename($temporary, static::replacementPath())) {
                throw new RuntimeException('Cannot activate credential file.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
