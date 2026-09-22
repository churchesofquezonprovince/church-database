<?php
namespace App\Http\Controllers;
use App\Filament\Pages\DeveloperOptions;
use App\Models\DeveloperSetting;
use App\Services\RcloneBackupSettings as Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RcloneBackupSettingsController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $rules = ['action' => ['required', Rule::in(['test', 'save'])], 'enabled' => ['required', 'boolean'],
            'remote' => ['required', 'string', Rule::in(Settings::connections())], 'folder' => ['required', 'string', 'max:2000']];
        $validator = validator($request->all(), $rules);
        if ($validator->fails()) { return $this->result(false, $validator->errors()->first()); }
        $data = $validator->validated();
        try { $folder = Settings::folderId($data['folder']); }
        catch (\RuntimeException $e) { return $this->result(false, $e->getMessage()); }
        $destination = array_replace(Settings::current(), ['remote' => $data['remote'], 'folder_id' => $folder]);
        if ($data['action'] === 'test' || $data['enabled']) {
            try { Settings::verify($destination); }
            catch (\Throwable) { return $this->result(false, 'Could not list that folder. Check the rclone login and folder access. Saved settings were kept.'); }
        }
        if ($data['action'] === 'test') {
            return $this->result(true, 'Folder listing succeeded. This read-only check does not prove upload permission. No settings were changed.');
        }
        DeveloperSetting::putValue('google_drive_backup_settings', json_encode([
            'enabled' => (bool) $data['enabled'], 'remote' => $data['remote'], 'folder_id' => $folder,
        ], JSON_THROW_ON_ERROR));
        return $this->result(true, $data['enabled']
            ? 'Backup settings saved. New uploads will use this destination. Existing backup records keep their recorded destinations.'
            : 'Drive uploads disabled. Local backups and the existing retention policy are unchanged.');
    }
    private function result(bool $success, string $message)
    {
        return redirect(DeveloperOptions::getUrl().'#google-integrations')->with($success ? 'google_setup_success' : 'google_setup_error', $message);
    }
}
