<?php

namespace App\Filament\Pages;

use App\Models\BackupRun;
use App\Services\ChurchDatabaseBackupService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class BackupDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Backup Dashboard';

    protected static ?string $title = 'Backup Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'backup-dashboard';

    protected string $view = 'filament.pages.backup-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function runBackup(): void
    {
        try {
            $backup = app(ChurchDatabaseBackupService::class)->run();

            $body = 'Local backup completed.';

            if ($backup->external_status === 'copied') {
                $body .= ' External backup copied.';
            } elseif ($backup->external_status === 'failed') {
                $body .= ' External backup failed.';
            }

            Notification::make()
                ->title('Backup completed')
                ->body($body)
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Backup failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function latestBackup(): ?BackupRun
    {
        return BackupRun::query()
            ->latest('started_at')
            ->first();
    }

    public function lastSuccessfulBackup(): ?BackupRun
    {
        return BackupRun::query()
            ->where('status', 'completed')
            ->latest('finished_at')
            ->first();
    }

    public function backupRuns(): Collection
    {
        return BackupRun::query()
            ->latest('started_at')
            ->limit(30)
            ->get();
    }

    public function totalBackups(): int
    {
        return BackupRun::query()->count();
    }

    public function completedBackups(): int
    {
        return BackupRun::query()
            ->where('status', 'completed')
            ->count();
    }

    public function failedBackups(): int
    {
        return BackupRun::query()
            ->where('status', 'failed')
            ->count();
    }

    public function formatBytes(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, $value >= 10 || $unit === 'B' ? 0 : 1) . ' ' . $unit;
            }

            $value /= 1024;
        }

        return number_format($value, 1) . ' TB';
    }
}
