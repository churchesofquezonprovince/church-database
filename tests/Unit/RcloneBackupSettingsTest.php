<?php
namespace Tests\Unit;
use App\Models\BackupRun;
use App\Services\RcloneBackupSettings as Settings;
use Tests\TestCase;

class RcloneBackupSettingsTest extends TestCase
{
    private function destination(): array {
        return ['remote' => 'original-drive', 'folder_id' => 'original_folder_12345', 'config_path' => '/private/rclone.conf'];
    }
    private function backup(): BackupRun {
        return new BackupRun(['filename' => 'backup.sql.gz', 'google_drive_path' => 'original-drive:backup.sql.gz', 'google_drive_destination' => $this->destination()]);
    }
    public function test_cleanup_uses_recorded_destination_after_settings_change(): void {
        config(['backup.google_drive.folder_id' => 'new_folder_123456', 'backup.google_drive.rclone_remote' => 'new-drive']);
        $destination = Settings::destinationFor($this->backup());
        $this->assertSame($this->destination(), $destination);
        $command = Settings::command('deletefile', $destination, 'backup.sql.gz');
        $this->assertContains('original-drive:backup.sql.gz', $command);
        $this->assertContains('original_folder_12345', $command);
        $this->assertNotContains('new_folder_123456', $command);
    }
    public function test_legacy_backup_cleanup_skips_unknown_destination(): void {
        $backup = new BackupRun(['filename' => 'backup.sql.gz', 'google_drive_path' => 'original-drive:backup.sql.gz']);
        $service = new class extends \App\Services\ChurchDatabaseBackupCleanupService {
            public function attempt(BackupRun $backup): bool { return $this->deleteGoogleDriveFile($backup, false); }
        };
        \Illuminate\Support\Facades\Process::fake();
        $this->assertFalse($service->attempt($backup));
        \Illuminate\Support\Facades\Process::assertNothingRan();
    }
    public function test_mismatched_recorded_path_is_rejected(): void {
        $backup = $this->backup(); $backup->google_drive_path = 'different-drive:backup.sql.gz';
        $this->expectException(\RuntimeException::class);
        Settings::destinationFor($backup);
    }
    public function test_filename_cannot_escape_destination_folder(): void {
        $this->expectException(\RuntimeException::class);
        Settings::command('deletefile', $this->destination(), '../backup.sql.gz');
    }
    public function test_folder_url_is_normalized(): void {
        $this->assertSame('original_folder_12345', Settings::folderId('https://drive.google.com/drive/folders/original_folder_12345?usp=sharing'));
    }
    public function test_non_google_url_is_rejected(): void {
        $this->expectException(\RuntimeException::class);
        Settings::folderId('https://example.com/drive/folders/original_folder_12345');
    }
    public function test_non_admin_cannot_change_backup_settings(): void {
        $request = \Illuminate\Http\Request::create('/internal/rclone-backup-settings', 'POST');
        $request->setUserResolver(fn () => new class { public function isAdmin(): bool { return false; } });
        try {
            app(\App\Http\Controllers\RcloneBackupSettingsController::class)->store($request);
            $this->fail('Non-admin was accepted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }
}
