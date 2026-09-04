<?php

namespace App\Filament\Pages;

use App\Models\AttendanceSheet;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ManageAttendanceSheets extends Page
{
    protected string $view = 'filament.pages.manage-attendance-sheets';

    public function getTitle(): string
    {
        return 'Manage Attendance Sheets';
    }

    public static function getNavigationLabel(): string
    {
        return 'Manage Attendance Sheets';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-pencil-square';
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

    public function selectedStatus(): string
    {
        $status = request('status', 'active');

        return in_array($status, ['active', 'archived', 'all'], true)
            ? $status
            : 'active';
    }

    public function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'archived' => 'Archived',
            'all' => 'All',
        ];
    }

    public function statusUrl(string $status): string
    {
        $query = request()->query();
        $query['status'] = $status;

        if ($status === 'active') {
            unset($query['status']);
        }

        return static::getUrl() . ($query ? ('?' . http_build_query($query)) : '');
    }

    public function sheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->when($this->selectedStatus() === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->selectedStatus() === 'archived', fn ($query) => $query->where('is_active', false))
            ->withCount(['sessions', 'participants'])
            ->with([
    'sessions' => fn ($query) => $query
        ->orderBy('session_date')
        ->orderBy('id'),
])
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();
    }
}
