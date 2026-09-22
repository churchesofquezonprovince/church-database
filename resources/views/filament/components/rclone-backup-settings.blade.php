@php
    $backupGoogleSettings = \App\Services\RcloneBackupSettings::current();
    $backupGoogleConnections = \App\Services\RcloneBackupSettings::connections();
    $legacyGoogleBackups = \App\Models\BackupRun::query()->where('google_drive_status', 'uploaded')->whereNull('google_drive_destination')->count();
@endphp
<details>
    <summary>Google Drive Backups</summary>
    <div class="google-body">
        <p>Choose an existing rclone Drive connection and the folder for new backup uploads.</p>
        <p class="google-help">These connections have their own Google login. The service-account choices for Calendar, Sheets, and Minutes do not change this login.</p>
        @if($backupGoogleConnections === [])
            <p role="status">No readable Google Drive connections were found in the configured rclone file. Keep the current setup and check the rclone connection before saving.</p>
        @else
            <form method="POST" action="{{ url('/internal/rclone-backup-settings') }}">
                @csrf
                <label for="backup-drive-enabled">Google Drive uploads</label>
                <select id="backup-drive-enabled" name="enabled">
                    <option value="1" @selected($backupGoogleSettings['enabled'])>Enabled</option>
                    <option value="0" @selected(! $backupGoogleSettings['enabled'])>Disabled</option>
                </select>
                <label for="backup-drive-remote">Drive connection</label>
                <select id="backup-drive-remote" name="remote" required>
                    <option value="">Select a connection</option>
                    @foreach($backupGoogleConnections as $remote)
                        <option value="{{ $remote }}" @selected($backupGoogleSettings['remote'] === $remote)>{{ $remote }}</option>
                    @endforeach
                </select>
                <label for="backup-drive-folder">Google Drive folder URL or ID</label>
                <input id="backup-drive-folder" name="folder" value="{{ $backupGoogleSettings['folder_id'] }}" maxlength="2000" required>
                <p class="google-help">Saving enabled uploads first checks whether the folder can be listed. Upload permission is confirmed when a backup is actually uploaded.</p>
                <button type="submit" name="action" value="test">Test Folder Access</button>
                <button type="submit" name="action" value="save">Save Backup Settings</button>
            </form>
        @endif
        <p class="google-help">Changing this destination does not move existing files. Disabling Drive uploads does not disable local backups or the retention policy.</p>
        @if($legacyGoogleBackups > 0)
            <p>{{ number_format($legacyGoogleBackups) }} older uploaded backup(s) have no recorded destination. Automatic Drive cleanup will skip these files until their original locations are verified.</p>
        @endif
        <details>
            <summary>How do I use another Google login for backups?</summary>
            <div class="google-body">
                <p>Add a separately named Google Drive connection through the server’s rclone setup, using its existing configuration file. Keep the old connection available for older backup files.</p>
                <p>Then refresh this page, choose the new connection, enter its backup folder, and use Test Folder Access before saving. A connection name is a label; it is not necessarily the Google account email.</p>
                <p class="google-help">This form selects existing connections. It does not perform a new Google sign-in or upload an rclone configuration file.</p>
            </div>
        </details>
    </div>
</details>
