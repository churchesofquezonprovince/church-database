<?php

namespace App\Filament\Pages;

use App\Services\BaptismOverviewData;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BaptismOverview extends Page
{
    protected string $view = 'filament.pages.baptism-overview';
    protected static ?string $slug = 'baptism-overview';

    public int $year = 2026;
    public int $month = 1;
    public string $display = 'year';
    public string $locality = '';
    public string $search = '';
    public string $activity = '';
    public string $selectedDate = '';
    public string $listScope = 'period';
    public int $peoplePage = 1;
    public array $sessionChoices = [];

    public static function getNavigationLabel(): string { return 'Baptism Overview'; }
    public function getTitle(): string { return 'Baptism Overview'; }
    public static function getNavigationGroup(): ?string { return 'Shepherding'; }
    public static function getNavigationIcon(): ?string { return 'heroicon-o-sparkles'; }
    public static function getNavigationSort(): ?int { return 35; }
    public static function canAccess(): bool { return auth()->user()?->canManageRecords() ?? false; }
    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

    public function mount(): void
    {
        BaptismOverviewData::authorize();
        $this->year = (int) now()->format('Y');
        $this->month = (int) now()->format('n');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['year', 'month', 'display', 'locality', 'search', 'activity'], true)) {
            $this->selectedDate = '';
            $this->listScope = 'period';
            $this->peoplePage = 1;
            $this->sessionChoices = [];
        }
    }

    public function selectDay(string $date): void
    {
        BaptismOverviewData::authorize();
        abort_unless(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1])
            && (int) $m[1] === $this->year
            && ($this->display !== 'month' || (int) $m[2] === $this->month), 422);
        $this->selectedDate = $date;
        $this->listScope = 'period';
        $this->peoplePage = 1;
        $this->sessionChoices = [];
    }

    public function showList(string $scope = 'period'): void
    {
        BaptismOverviewData::authorize();
        abort_unless(in_array($scope, ['period', 'partial', 'unknown-year'], true), 422);
        $this->selectedDate = '';
        $this->listScope = $scope;
        $this->peoplePage = 1;
        $this->sessionChoices = [];
    }

    public function changePeoplePage(int $page): void
    {
        BaptismOverviewData::authorize();
        $this->peoplePage = max(1, $page);
        $this->sessionChoices = [];
    }

    public function confirmActivity(int $personId, string $date): void
    {
        BaptismOverviewData::authorize();
        $choice = $this->sessionChoices[$personId] ?? null;
        if (! is_scalar($choice) || ! ctype_digit((string) $choice) || (int) $choice < 1) {
            Notification::make()->title('Choose an activity first')->warning()->send();
            return;
        }
        try {
            app(BaptismOverviewData::class)->confirm($personId, (int) $choice, $date);
        } catch (ValidationException $e) {
            Notification::make()->title('Activity could not be linked')
                ->body($e->validator->errors()->first())->warning()->send();
            return;
        }
        unset($this->sessionChoices[$personId]);
        Notification::make()->title('Baptism activity confirmed')->success()->send();
    }

    public function removeActivity(int $personId): void
    {
        app(BaptismOverviewData::class)->unlink($personId);
        unset($this->sessionChoices[$personId]);
        Notification::make()->title('Activity link removed')->body('The baptism date was kept.')->success()->send();
    }

    protected function getViewData(): array
    {
        BaptismOverviewData::authorize();
        // Bound client-controlled filters on every request, including Livewire updates.
        $this->year = max(1, min(9999, $this->year));
        $this->month = max(1, min(12, $this->month));
        $this->display = $this->display === 'month' ? 'month' : 'year';
        $service = app(BaptismOverviewData::class);
        $all = $service->records();
        $links = $service->links();
        $all = $all->map(function ($row) use ($links): array {
            $row['link'] = $links->get($row['person_id']);
            $row['linked'] = BaptismOverviewData::linkIsCurrent($row['link'], $row['date']);
            return $row;
        });
        $localities = $all->pluck('locality')->filter()->unique()->sort()->values();
        $years = $all->pluck('parts.year')->filter(fn ($y) => $y >= 1 && $y <= 9999)
            ->push((int) now()->format('Y'))->push($this->year)->unique()->sortDesc()->values();
        $activityOptions = $all->filter(fn ($r) => $r['linked'])
            ->mapWithKeys(fn ($r) => [(string) $r['link']->sheet_id => $r['link']->sheet_title])->sort();
        $search = mb_strtolower(trim(mb_substr($this->search, 0, 200)));
        $filtered = $all->filter(function ($row) use ($search): bool {
            if ($this->locality !== '' && $row['locality'] !== $this->locality) { return false; }
            if ($search !== '' && ! str_contains(mb_strtolower($row['name']), $search)) { return false; }
            if ($this->activity === 'unlinked') { return ! $row['linked']; }
            return $this->activity === '' || ($row['linked'] && (string) $row['link']->sheet_id === $this->activity);
        });
        $yearRows = $filtered->filter(fn ($r) => $r['parts']['year'] === $this->year);
        $period = $yearRows->filter(fn ($r) => $this->display === 'year' || $r['parts']['month'] === $this->month);
        $unknownYear = $filtered->filter(fn ($r) => $r['parts']['year'] === null);
        $complete = $period->filter(fn ($r) => $r['date'] !== null);
        $partial = $period->filter(fn ($r) => $r['date'] === null);
        $counts = $complete->countBy('date');
        $start = CarbonImmutable::create($this->year, 1, 1, 0, 0, 0);
        $sessions = $service->sessions($start->format('Y-m-d'), $start->endOfYear()->format('Y-m-d'));
        $sessionsByDate = $sessions->groupBy('date');
        $calendars = [];
        foreach ($this->display === 'month' ? [$this->month] : range(1, 12) as $month) {
            $first = $start->setMonth($month);
            $days = [];
            for ($d = 1; $d <= $first->daysInMonth; $d++) {
                $date = $first->setDay($d)->format('Y-m-d');
                $count = (int) $counts->get($date, 0);
                $days[] = ['date' => $date, 'day' => $d, 'count' => $count,
                    'level' => $count === 0 ? 0 : ($count === 1 ? 1 : ($count <= 4 ? 2 : ($count <= 9 ? 3 : 4)))];
            }
            $monthRows = $yearRows->filter(fn ($r) => $r['parts']['month'] === $month);
            $calendars[] = ['number' => $month, 'name' => $first->format('F'),
                'offset' => (int) $first->format('N') - 1, 'days' => $days,
                'count' => $monthRows->count(), 'partial' => $monthRows->filter(fn ($r) => ! $r['date'])->count()];
        }
        $rows = match ($this->listScope) {
            'unknown-year' => $unknownYear,
            'partial' => $partial,
            default => $this->selectedDate !== ''
                ? $period->filter(fn ($r) => $r['date'] === $this->selectedDate) : $period,
        };
        $rows = $rows->sortBy(fn ($r) => ($r['date'] ?? '9999-99-99').' '.$r['name'])->values();
        $lastPage = max(1, (int) ceil($rows->count() / 20));
        $this->peoplePage = min(max(1, $this->peoplePage), $lastPage);
        $people = $rows->slice(($this->peoplePage - 1) * 20, 20)->values();
        $neededDates = $people->pluck('date')->filter()->unique();
        $neededSessions = $sessions->filter(fn ($s) => $neededDates->contains($s['date']))->pluck('id');
        $attendance = $neededSessions->isEmpty() ? collect() : DB::table('attendance_records')
            ->whereIn('attendance_session_id', $neededSessions)->whereIn('person_id', $people->pluck('person_id'))
            ->select('person_id', 'attendance_session_id', 'status', 'is_present')->get()
            ->groupBy(fn ($r) => $r->person_id.':'.$r->attendance_session_id);
        $people = $people->map(function ($row) use ($sessionsByDate, $attendance): array {
            $row['suggestions'] = $sessionsByDate->get($row['date'], collect())->map(function ($s) use ($row, $attendance): array {
                $records = $attendance->get($row['person_id'].':'.$s['id'], collect());
                $s['attended'] = $records->contains(fn ($r) => BaptismOverviewData::attended($r));
                return $s;
            })->sortByDesc('attended')->values();
            return $row;
        });
        $summary = $period->filter(fn ($r) => $r['linked'])->groupBy(fn ($r) => $r['link']->sheet_id)
            ->map(function ($group): array {
                return ['title' => $group->first()['link']->sheet_title, 'count' => $group->count(),
                    'dates' => $group->pluck('date')->unique()->sort()->values()];
            })->sortByDesc('count');
        $listTitle = match ($this->listScope) {
            'unknown-year' => 'Baptisms with unknown year — all years',
            'partial' => 'Incomplete dates in this period',
            default => $this->selectedDate !== '' ? 'Baptisms on '.$this->selectedDate : 'Baptisms in this period',
        };
        return compact('localities', 'years', 'activityOptions', 'calendars', 'people', 'lastPage', 'summary', 'listTitle') + [
            'total' => $period->count(), 'completeCount' => $complete->count(), 'partialCount' => $partial->count(),
            'unknownYearCount' => $unknownYear->count(),
            'unknownMonthCount' => $yearRows->filter(fn ($r) => $r['parts']['month'] === null)->count(),
            'linkedCount' => $period->filter(fn ($r) => $r['linked'])->count(), 'rowCount' => $rows->count(),
            'daySessions' => $sessionsByDate->get($this->selectedDate, collect()),
        ];
    }
}
