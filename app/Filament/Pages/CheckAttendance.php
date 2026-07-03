<?php

namespace App\Filament\Pages;

use App\Models\AttendanceParticipant;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CheckAttendance extends Page
{
    protected string $view = 'filament.pages.check-attendance';

    public function getTitle(): string
    {
        return 'Check Attendance';
    }

    public static function getNavigationLabel(): string
    {
        return 'Check Attendance';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-check-badge';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function sheets(): Collection
    {
        return AttendanceSheet::query()
            ->where('is_active', true)
            ->withCount(['sessions', 'participants'])
            ->latest()
            ->get();
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $sheetId = request()->integer('sheetId');

        $query = AttendanceSheet::query()
            ->where('is_active', true)
            ->withCount(['sessions', 'participants']);

        if ($sheetId) {
            return $query->find($sheetId);
        }

        return $query->latest()->first();
    }

    public function sessions(): Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        return AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->orderBy('session_date')
            ->get();
    }

    public function selectedSession(): ?AttendanceSession
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return null;
        }

        $sessionId = request()->integer('sessionId');

        $query = AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id);

        if ($sessionId) {
            return $query->find($sessionId);
        }

        return $query
            ->orderByRaw('CASE WHEN session_date >= CURDATE() THEN 0 ELSE 1 END')
            ->orderBy('session_date')
            ->first();
    }

    public function participantRows(): Collection
    {
        $session = $this->selectedSession();

        if (! $session) {
            return collect();
        }

        $sessionDate = $session->session_date->format('Y-m-d');

        return AttendanceParticipant::query()
            ->with(['person.churchProfile'])
            ->where('attendance_sheet_id', $session->attendance_sheet_id)
            ->where('is_active', true)
            ->where(function ($query) use ($sessionDate): void {
                $query->whereNull('starts_on')
                    ->orWhere('starts_on', '<=', $sessionDate);
            })
            ->where(function ($query) use ($sessionDate): void {
                $query->whereNull('ends_on')
                    ->orWhere('ends_on', '>=', $sessionDate);
            })
            ->get()
            ->sortBy(fn (AttendanceParticipant $participant): string => $participant->person?->display_name ?? '')
            ->values();
    }

    public function presentPersonIds(): array
    {
        $session = $this->selectedSession();

        if (! $session) {
            return [];
        }

        return AttendanceRecord::query()
            ->where('attendance_session_id', $session->id)
            ->where('is_present', true)
            ->pluck('person_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function recordCounts(): array
    {
        $session = $this->selectedSession();

        if (! $session) {
            return [
                'present' => 0,
                'absent' => 0,
                'marked' => 0,
            ];
        }

        return [
            'present' => AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('is_present', true)
                ->count(),

            'absent' => AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('is_present', false)
                ->count(),

            'marked' => AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->count(),
        ];
    }

    public function sheetUrl(AttendanceSheet $sheet): string
    {
        return self::getUrl() . '?sheetId=' . $sheet->id;
    }

    public function sessionUrl(AttendanceSheet $sheet, AttendanceSession $session): string
    {
        return self::getUrl() . '?sheetId=' . $sheet->id . '&sessionId=' . $session->id;
    }
}
