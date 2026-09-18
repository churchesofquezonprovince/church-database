<?php

namespace App\Filament\Pages;

use App\Models\Locality;
use App\Models\Person;
use App\Models\ShepherdingActivityType;
use App\Models\ShepherdingContact;
use App\Support\ShepherdingHistoryQuery;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

class ShepherdingHistory extends Page
{
    use WithPagination;

    protected string $view =
        'filament.pages.shepherding-history';

    protected static ?string $slug =
        'shepherding-history';

    public string $search = '';

    public ?int $personId = null;

    public string $outcome = 'all';

    public string $activity = '';

    public ?int $localityId = null;

    public string $from = '';

    public string $to = '';

    public string $mode = 'all';

    public ?int $recordId = null;

    public int $perPage = 50;

    public function mount(): void
    {
        $this->search =
            trim(
                (string) request()->query(
                    'search',
                    ''
                )
            );

        $requestedPersonId =
            (int) request()->query(
                'person',
                0
            );

        if (
            $requestedPersonId > 0
            && Person::query()
                ->whereKey($requestedPersonId)
                ->exists()
        ) {
            $this->personId =
                $requestedPersonId;
        }

        $requestedOutcome =
            (string) request()->query(
                'outcome',
                'all'
            );

        $this->outcome =
            array_key_exists(
                $requestedOutcome,
                ShepherdingContact::outcomeOptions()
            )
                ? $requestedOutcome
                : 'all';

        $requestedActivity =
            strtoupper(
                trim(
                    (string) request()->query(
                        'activity',
                        ''
                    )
                )
            );

        if (
            filled($requestedActivity)
            && ShepherdingActivityType::query()
                ->where(
                    'code',
                    $requestedActivity
                )
                ->exists()
        ) {
            $this->activity =
                $requestedActivity;
        }

        $requestedLocalityId =
            (int) request()->query(
                'locality',
                0
            );

        if (
            $requestedLocalityId > 0
            && Locality::query()
                ->whereKey($requestedLocalityId)
                ->exists()
        ) {
            $this->localityId =
                $requestedLocalityId;
        }

        $from =
            (string) request()->query(
                'from',
                ''
            );

        $to =
            (string) request()->query(
                'to',
                ''
            );

        $this->from =
            $this->validDate($from)
                ? $from
                : '';

        $this->to =
            $this->validDate($to)
                ? $to
                : '';

        $this->mode =
            request()->query('mode')
                === 'follow-up'
                ? 'follow-up'
                : 'all';

        $requestedRecordId =
            (int) request()->query(
                'record',
                0
            );

        if (
            $requestedRecordId > 0
            && ShepherdingContact::query()
                ->whereKey(
                    $requestedRecordId
                )
                ->exists()
        ) {
            $this->recordId =
                $requestedRecordId;
        }

        $requestedPerPage =
            (int) request()->query(
                'per_page',
                50
            );

        $this->perPage =
            in_array(
                $requestedPerPage,
                [
                    50,
                    100,
                    200,
                    500,
                ],
                true
            )
                ? $requestedPerPage
                : 50;
    }

    public function getTitle(): string
    {
        return 'Shepherding History';
    }

    public static function getNavigationLabel(): string
    {
        return 'Shepherding History';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shepherding';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clock';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public function people(): Collection
    {
        return Person::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    public function localities(): Collection
    {
        return Locality::query()
            ->orderBy('name')
            ->get();
    }

    public function activityTypes(): Collection
    {
        return ShepherdingActivityType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public function outcomeOptions(): array
    {
        return ShepherdingContact::outcomeOptions();
    }

    public function historyContacts(): LengthAwarePaginator
    {
        $history =
            ShepherdingHistoryQuery::make();

        if (filled($this->from)) {
            $history->from(
                $this->from
            );
        }

        if (filled($this->to)) {
            $history->to(
                $this->to
            );
        }

        if (filled($this->personId)) {
            $history->person(
                (int) $this->personId
            );
        }

        if (filled($this->activity)) {
            $history->activity(
                $this->activity
            );
        }

        if (filled($this->localityId)) {
            $history->locality(
                (int) $this->localityId
            );
        }

        if ($this->outcome !== 'all') {
            $history->outcome(
                $this->outcome
            );
        }

        $query =
            $history
                ->query()
                ->with([
                    'morningRevivalWeek.publication',
                    'bibleReadings',
                    'hymns.bookEntries.hymnBook',
                    'hymns.sources',
                    'hymnAdditionRequests',
                ]);

        if (
            $this->mode
            === 'follow-up'
        ) {
            $query->whereIn(
                'outcome',
                [
                    ShepherdingContact::OUTCOME_UNAVAILABLE,
                    ShepherdingContact::OUTCOME_RESCHEDULE,
                    ShepherdingContact::OUTCOME_DECLINED,
                ]
            );
        }

        if (filled($this->recordId)) {
            $query->whereKey(
                (int) $this->recordId
            );
        }

        $search =
            trim($this->search);

        if (filled($search)) {
            $like =
                "%{$search}%";

            $query->where(
                function (
                    Builder $query
                ) use (
                    $like
                ): void {
                    $query
                        ->where(
                            'notes',
                            'like',
                            $like
                        )
                        ->orWhere(
                            'outcome',
                            'like',
                            $like
                        )
                        ->orWhereHas(
                            'contactedPeople',
                            function (
                                Builder $query
                            ) use (
                                $like
                            ): void {
                                $query
                                    ->where(
                                        'firstname',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'middlename',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'lastname',
                                        'like',
                                        $like
                                    );
                            }
                        )
                        ->orWhereHas(
                            'contactedHouseholds',
                            fn (
                                Builder $query
                            ) =>
                                $query->where(
                                    'household_name',
                                    'like',
                                    $like
                                )
                        )
                        ->orWhereHas(
                            'contactedCampusContacts',
                            function (
                                Builder $query
                            ) use (
                                $like
                            ): void {
                                $query
                                    ->where(
                                        'firstname',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'lastname',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'school_campus',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'locality',
                                        'like',
                                        $like
                                    );
                            }
                        )
                        ->orWhereHas(
                            'contactedGospelContacts',
                            function (
                                Builder $query
                            ) use (
                                $like
                            ): void {
                                $query
                                    ->where(
                                        'firstname',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'lastname',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'locality',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'contact_place',
                                        'like',
                                        $like
                                    );
                            }
                        )
                        ->orWhereHas(
                            'householdMembers',
                            function (
                                Builder $query
                            ) use (
                                $like
                            ): void {
                                $query
                                    ->where(
                                        'firstname',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'lastname',
                                        'like',
                                        $like
                                    );
                            }
                        )
                        ->orWhereHas(
                            'locality',
                            fn (
                                Builder $query
                            ) =>
                                $query->where(
                                    'name',
                                    'like',
                                    $like
                                )
                        )
                        ->orWhereHas(
                            'activityTypes',
                            function (
                                Builder $query
                            ) use (
                                $like
                            ): void {
                                $query
                                    ->where(
                                        'code',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'name',
                                        'like',
                                        $like
                                    );
                            }
                        )
                        ->orWhereHas(
                            'ministryLessons',
                            function (
                                Builder $query
                            ) use (
                                $like
                            ): void {
                                $query
                                    ->where(
                                        'code',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'title',
                                        'like',
                                        $like
                                    );
                            }
                        )
                        ->orWhereHas(
                            'participants',
                            function (
                                Builder $query
                            ) use (
                                $like
                            ): void {
                                $query
                                    ->where(
                                        'firstname',
                                        'like',
                                        $like
                                    )
                                    ->orWhere(
                                        'lastname',
                                        'like',
                                        $like
                                    );
                            }
                        );
                }
            );
        }

        return $query
            ->orderByDesc(
                'contact_date'
            )
            ->orderByDesc(
                'contact_time'
            )
            ->orderByDesc('id')
            ->paginate(
                $this->perPage
            );
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->personId = null;
        $this->outcome = 'all';
        $this->activity = '';
        $this->localityId = null;
        $this->from = '';
        $this->to = '';
        $this->mode = 'all';
        $this->recordId = null;

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPersonId(): void
    {
        $this->resetPage();
    }

    public function updatedOutcome(): void
    {
        $this->resetPage();
    }

    public function updatedActivity(): void
    {
        $this->resetPage();
    }

    public function updatedLocalityId(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(
        mixed $value
    ): void {
        $value =
            (int) $value;

        $this->perPage =
            in_array(
                $value,
                [
                    50,
                    100,
                    200,
                    500,
                ],
                true
            )
                ? $value
                : 50;

        $this->resetPage();
    }

    private function validDate(
        string $date
    ): bool {
        return (bool) preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $date
        );
    }
}
