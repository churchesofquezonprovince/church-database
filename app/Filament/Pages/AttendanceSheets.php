<?php

namespace App\Filament\Pages;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceSheet;
use App\Models\Person;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AttendanceSheets extends Page
{
    protected string $view = 'filament.pages.attendance-sheets';

    public function getTitle(): string
    {
        return 'Attendance Sheets';
    }

    public static function getNavigationLabel(): string
    {
        return 'Attendance Sheets';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function selectedMode(): string
    {
        $mode = request('mode', 'all');

        return in_array($mode, ['all', 'recurring', 'one_time'], true)
            ? $mode
            : 'all';
    }

    public function modeOptions(): array
    {
        return [
            'all' => 'All Sheets',
            'recurring' => 'Recurring',
            'one_time' => 'One-time',
        ];
    }

    public function modeUrl(string $mode): string
    {
        $query = array_merge(request()->query(), [
            'mode' => $mode,
        ]);

        if ($mode === 'all') {
            unset($query['mode']);
        }

        return static::getUrl() . ($query ? ('?' . http_build_query($query)) : '');
    }

    public function sheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_CUSTOM)
            ->where('is_active', true)
            ->when($this->selectedMode() === 'recurring', fn ($query) => $query->where('is_one_time', false))
            ->when($this->selectedMode() === 'one_time', fn ($query) => $query->where('is_one_time', true))
            ->withCount(['sessions', 'participants'])
            ->orderByDesc('is_active')
            ->orderByRaw('CASE WHEN locality IS NULL OR locality = "" THEN 1 ELSE 0 END')
            ->orderBy('locality')
            ->latest()
            ->get();
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $sheetId = request()->integer('sheetId');

        $query = AttendanceSheet::query()
            ->withCount(['sessions', 'participants'])
            ->with(['sessions' => fn ($query) => $query->orderBy('session_date')]);

        if ($sheetId) {
            return $query->find($sheetId);
        }

        return $query->latest()->first();
    }

    public function participantRows(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        return AttendanceParticipant::query()
            ->with(['person.churchProfile'])
            ->where('attendance_sheet_id', $sheet->id)
            ->get()
            ->sortBy(fn (AttendanceParticipant $participant): string => $participant->person?->display_name ?? '')
            ->values();
    }

    public function availablePeople(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        $existingPersonIds = AttendanceParticipant::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->pluck('person_id');

        return Person::query()
            ->with(['churchProfile'])
            ->whereNotIn('id', $existingPersonIds)
            ->when(
                filled($sheet->locality),
                fn ($query) => $query->orderByRaw('CASE WHEN locality = ? THEN 0 ELSE 1 END', [$sheet->locality])
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function sheetUrl(AttendanceSheet $sheet): string
    {
        return self::getUrl() . '?sheetId=' . $sheet->id;
    }
}
