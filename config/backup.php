<?php

return [
    'local_path' => env('BACKUP_LOCAL_PATH', 'storage/app/backups/church-database'),

    'external_path' => env('BACKUP_EXTERNAL_PATH', '/mnt/databackup/church-database-backups'),

    'filename_prefix' => env('BACKUP_FILENAME_PREFIX', 'church-database'),

    'google_drive' => [
        'enabled' => env('BACKUP_GOOGLE_DRIVE_ENABLED', false),

        'credentials_path' => env(
            'BACKUP_GOOGLE_DRIVE_CREDENTIALS_PATH',
            env('GOOGLE_CALENDAR_CREDENTIALS_PATH', 'storage/app/google-calendar/service-account.json'),
        ),

        'folder_id' => env(
            'BACKUP_GOOGLE_DRIVE_FOLDER_ID',
            '1fMxK1-_yl1VCjjRrV_6p42o0diduCC8z',
        ),
    ],
];
