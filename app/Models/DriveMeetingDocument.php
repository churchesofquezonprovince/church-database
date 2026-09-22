<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Cache;

class DriveMeetingDocument extends Model
{
    use Sushi;

    // --- THE MAGIC FIX ---
    // Tells Laravel to treat Google Drive IDs as strings and stop turning them into zeros!
    protected $keyType = 'string';
    public $incrementing = false;
    // ---------------------

    protected $schema = [
        'id' => 'string',
        'name' => 'string',
        'created_time' => 'datetime',
        'view_link' => 'string',
        'file_type' => 'string',
    ];


    public static function serviceMeetingFolderId(): string
    {
        $folder = DeveloperSetting::string(
            'service_meeting_drive_folder',
            '1HXwJXNAlss1h8IFa0rp1B13IxFv6RIbV'
        );

        return filled($folder)
            ? $folder
            : '1HXwJXNAlss1h8IFa0rp1B13IxFv6RIbV';
    }

    public static function serviceMeetingFolderUrl(): string
    {
        return 'https://drive.google.com/drive/folders/'
            . rawurlencode(static::serviceMeetingFolderId());
    }

    public static function documentCacheKey(): string
    {
        return 'service_meeting_docs:' . hash('sha256', static::serviceMeetingFolderId());
    }

    public function getRows(): array
    {
        return Cache::remember(static::documentCacheKey(), 900, function () {
            $client = \App\Services\ServiceMeetingGoogleAccount::client();
            
            $drive = new Drive($client);
            $folderId = static::serviceMeetingFolderId();
            
            $optParams = [
                'q' => "'{$folderId}' in parents and trashed = false",
                'fields' => 'files(id, name, createdTime, webViewLink, mimeType)',
                'pageSize' => 100,
            ];

            $results = $drive->files->listFiles($optParams);
            
            $rows = [];
            foreach ($results->getFiles() as $file) {
                $rawName = $file->getName();
                $cleanName = str_replace(['.pdf', '.docx'], '', $rawName);
                
                $meetingDate = date('Y-m-d H:i:s', strtotime($file->getCreatedTime())); 
                
                if (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})/', $cleanName, $matches)) {
                    $meetingDate = date('Y-m-d 00:00:00', strtotime($matches[1])); 
                }

                $rows[] = [
                    'id' => $file->getId(),
                    'name' => $cleanName,
                    'created_time' => $meetingDate,
                    'view_link' => $file->getWebViewLink(),
                    'file_type' => str_contains($file->getMimeType(), 'pdf') ? 'PDF' : 'Google Doc',
                ];
            }

            if (empty($rows)) {
                return [['id' => '0', 'name' => 'No files found', 'created_time' => now()->toDateTimeString(), 'view_link' => '#', 'file_type' => 'N/A']];
            }

            return $rows;
        });
    }
}