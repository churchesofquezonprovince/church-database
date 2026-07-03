<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Models\Person;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class LordsTableMeeting extends Page
{
    protected string $view = 'filament.pages.lords-table-meeting';

    public function otherLocalityCandidates(): \Illuminate\Support\Collection
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return collect();
        }

        $participantIds = $sheet->participants()
            ->pluck('person_id')
            ->all();

        return Person::query()
            ->when($participantIds !== [], fn ($query) => $query->whereNotIn('id', $participantIds))
            ->when(! blank($sheet->locality), function ($query) use ($sheet): void {
                $query->where(function ($query) use ($sheet): void {
                    $query->whereNull('locality')
                        ->orWhere('locality', '')
                        ->orWhereRaw('LOWER(locality) != ?', [strtolower(trim((string) $sheet->locality))]);
                });
            })
            ->orderBy('id')
            ->get();
    }

    public function getTitle(): string
    {
        return "Lord's Table Meeting";
    }

    public static function getNavigationLabel(): string
    {
        return "Lord's Table Meeting";
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-check-circle';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function localities(): Collection
    {
        $localities = Person::query()
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->distinct()
            ->orderBy('locality')
            ->pluck('locality');

        $hasNoLocality = Person::query()
            ->where(fn ($query) => $query->whereNull('locality')->orWhere('locality', ''))
            ->exists();

        if ($hasNoLocality) {
            $localities->push('__no_locality');
        }

        return $localities;
    }

    public function selectedLocality(): ?string
    {
        $locality = request()->query('locality');

        if (filled($locality)) {
            return (string) $locality;
        }

        return $this->localities()->first();
    }

    public function selectedMeetingDate(): string
    {
        $date = request()->query('meeting_date');

        if (filled($date)) {
            return CarbonImmutable::parse((string) $date)->toDateString();
        }

        $today = CarbonImmutable::today();

        return $today->dayOfWeek === 0
            ? $today->toDateString()
            : $today->next(0)->toDateString();
    }

    public function people(): Collection
    {
        $locality = $this->selectedLocality();

        if (! $locality) {
            return collect();
        }

        return Person::query()
            ->with(['churchProfile'])
            ->when(
                $locality === '__no_locality',
                fn ($query) => $query->where(fn ($query) => $query->whereNull('locality')->orWhere('locality', '')),
                fn ($query) => $query->where('locality', $locality),
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function selectedSheet(): ?AttendanceSheet
    {
        $locality = $this->selectedLocality();

        if (! $locality) {
            return null;
        }

        return AttendanceSheet::query()
            ->where('sheet_type', AttendanceSheet::TYPE_LORDS_TABLE)
            ->where(function ($query) use ($locality): void {
                if ($locality === '__no_locality') {
                    $query->whereNull('locality');
                } else {
                    $query->where('locality', $locality);
                }
            })
            ->first();
    }

    public function selectedSession(): ?AttendanceSession
    {
        $sheet = $this->selectedSheet();

        if (! $sheet) {
            return null;
        }

        return AttendanceSession::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->whereDate('session_date', $this->selectedMeetingDate())
            ->first();
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

    public function counts(): array
    {
        $session = $this->selectedSession();

        if (! $session) {
            return [
                'present' => 0,
                'absent' => 0,
                'people' => $this->people()->count(),
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

            'people' => $this->people()->count(),
        ];
    }


    public function otherLocalityPresentRecords(): Collection
    {
        $session = $this->selectedSession();
        $sheet = $this->selectedSheet();

        if (! $session || ! $sheet) {
            return collect();
        }

        $participantIds = $sheet->participants()
            ->pluck('person_id')
            ->all();

        return AttendanceRecord::query()
            ->with('person')
            ->where('attendance_session_id', $session->id)
            ->where('is_present', true)
            ->whereNotNull('person_id')
            ->when($participantIds !== [], fn ($query) => $query->whereNotIn('person_id', $participantIds))
            ->orderBy('marked_at')
            ->get();
    }

    public function localityLabel(?string $locality = null): string
    {
        $locality ??= $this->selectedLocality();

        return $locality === '__no_locality'
            ? 'No Locality'
            : (string) $locality;
    }
}
