<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AttendanceHistory extends Page
{
    protected string $view = 'filament.pages.attendance-history';

    public function getTitle(): string
    {
        return 'Attendance History';
    }

    public static function getNavigationLabel(): string
    {
        return 'Attendance History';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clock';
    }

    public static function getNavigationSort(): ?int
    {
        return 60;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function actionOptions(): array
    {
        return [
            'all' => 'All Attendance Logs',
            'attendance_sheet.created' => 'Sheets Created',
            'attendance_sheet.updated' => 'Sheets Updated',
            'attendance_sheet.archived' => 'Sheets Archived',
            'attendance_sheet.restored' => 'Sheets Restored',
            'attendance_sheet.deleted' => 'Sheets Deleted',
            'attendance_participants.added' => 'Participants Added',
            'attendance_participant.removed' => 'Participants Removed',
            'permanent_meeting_other_attendee.added' => 'Other Locality Attendees Added',
            'permanent_meeting_other_attendee.removed' => 'Other Locality Attendees Removed',
            'attendance_records.saved' => 'Custom Attendance Saved',
            'lords_table.attendance.saved' => "Lord's Table Saved",
            'prayer_meeting.attendance.saved' => 'Prayer Meeting Saved',
        ];
    }

    public function attendanceActions(): array
    {
        return array_values(array_filter(
            array_keys($this->actionOptions()),
            fn (string $action): bool => $action !== 'all',
        ));
    }

    public function selectedAction(): string
    {
        $action = request('log_action', request('action', 'all'));

        return array_key_exists($action, $this->actionOptions())
            ? $action
            : 'all';
    }

    public function actionUrl(string $action): string
    {
        $query = request()->query();

        unset($query['action']);

        $query['log_action'] = $action;

        if ($action === 'all') {
            unset($query['log_action']);
        }

        return static::getUrl() . ($query ? ('?' . http_build_query($query)) : '');
    }

    public function selectedDateFrom(): ?string
    {
        return $this->validDate(request('date_from'));
    }

    public function selectedDateTo(): ?string
    {
        return $this->validDate(request('date_to'));
    }

    public function searchTerm(): ?string
    {
        $term = trim((string) request('q', ''));

        return $term === '' ? null : $term;
    }

    public function clearFiltersUrl(): string
    {
        return static::getUrl();
    }

    public function logs(): Collection
    {
        return ActivityLog::query()
            ->whereIn('action', $this->attendanceActions())
            ->when($this->selectedAction() !== 'all', fn ($query) => $query->where('action', $this->selectedAction()))
            ->when($this->selectedDateFrom(), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($this->selectedDateTo(), fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($this->searchTerm(), function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('action', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('subject_type', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at')
            ->take(150)
            ->get();
    }

    public function actionLabel(?string $action): string
    {
        return $this->actionOptions()[$action] ?? str_replace('_', ' ', (string) $action);
    }

    public function actionBadgeClass(?string $action): string
    {
        return match ($action) {
            'attendance_sheet.created' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100',
            'attendance_sheet.updated' => 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-100',
            'attendance_sheet.archived' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100',
            'attendance_sheet.restored' => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-100',
            'attendance_sheet.deleted' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
        };
    }

    public function logTitle(ActivityLog $log): string
    {
        $newValues = $this->arrayValue($log->new_values ?? null);
        $oldValues = $this->arrayValue($log->old_values ?? null);

        return (string) (
            $newValues['title']
            ?? $oldValues['title']
            ?? $newValues['sheet_title']
            ?? $oldValues['sheet_title']
            ?? $log->description
            ?? 'Attendance Log'
        );
    }

    public function logSummary(ActivityLog $log): string
    {
        $newValues = $this->arrayValue($log->new_values ?? null);
        $oldValues = $this->arrayValue($log->old_values ?? null);

        $parts = [];

        $locality = $newValues['locality'] ?? $oldValues['locality'] ?? null;
        $meetingTime = $newValues['meeting_time'] ?? $oldValues['meeting_time'] ?? null;
        $sessions = $newValues['sessions_created'] ?? $oldValues['sessions_count'] ?? null;
        $participants = $newValues['participants_count'] ?? $oldValues['participants_count'] ?? null;

        if ($locality) {
            $parts[] = 'Locality: ' . $locality;
        }

        if ($meetingTime) {
            $parts[] = 'Time: ' . $meetingTime;
        }

        if ($sessions !== null) {
            $parts[] = 'Sessions: ' . $sessions;
        }

        if ($participants !== null) {
            $parts[] = 'Participants: ' . $participants;
        }

        return $parts === []
            ? (string) ($log->description ?? 'No additional details.')
            : implode(' · ', $parts);
    }

    public function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function displayValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || $value === '') {
            return 'None';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    private function validDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
