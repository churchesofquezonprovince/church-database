<?php

namespace App\Filament\Pages;

use App\Models\MorningRevivalPublication;
use App\Models\MorningRevivalWeek;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MorningRevivalSetup extends Page
{
    protected string $view =
        'filament.pages.morning-revival-setup';

    protected static ?string $slug =
        'morning-revival-setup';

    public ?int $editingPublicationId = null;

    public string $sourceTitle = '';

    public string $generalSubject = '';

    public string $startDate = '';

    public bool $isActive = true;

    /*
     * One row per Morning Revival week/message.
     *
     * Day 1-6 dates are derived from the publication
     * start date, so they are never entered separately.
     */
    public array $weekTitles = [];

    /*
     * Bulk outline paste helper.
     *
     * Parsing fills General Subject + Week titles only.
     * Source / Conference and start date remain explicit
     * administrative fields.
     */
    public string $outlinePaste = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $this->newPublication();
    }

    public function getTitle(): string
    {
        return 'Morning Revival Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'Morning Revival Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-sun';
    }

    public static function getNavigationSort(): ?int
    {
        return 8;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin()
            ?? false;
    }

    public function publications(): Collection
    {
        return MorningRevivalPublication::query()
            ->with([
                'weeks' =>
                    fn ($query) =>
                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy(
                                'week_number'
                            ),
            ])
            ->orderByDesc(
                'start_date'
            )
            ->orderByDesc(
                'id'
            )
            ->get();
    }

    public function todayReading(): ?array
    {
        $today =
            now()->startOfDay();

        $week =
            MorningRevivalWeek::query()
                ->with(
                    'publication'
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereHas(
                    'publication',
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                )
                ->whereDate(
                    'start_date',
                    '<=',
                    $today
                )
                ->orderByDesc(
                    'start_date'
                )
                ->first();

        if (! $week) {
            return null;
        }

        $day =
            $week->dayNumberForDate(
                $today
            );

        /*
         * Sunday or a date outside Day 1-6.
         */
        if ($day === null) {
            return null;
        }

        return [
            'week' =>
                $week,

            'day' =>
                $day,

            'date' =>
                $today,
        ];
    }

    public function newPublication(): void
    {
        $this->resetValidation();

        $this->editingPublicationId =
            null;

        $this->sourceTitle = '';

        $this->generalSubject = '';

        /*
         * Convenient default only.
         * Admin may change this before saving.
         */
        $this->startDate =
            now()
                ->startOfWeek(
                    Carbon::MONDAY
                )
                ->toDateString();

        $this->isActive = true;

        $this->outlinePaste = '';

        /*
         * Six weeks is the common current workflow,
         * but rows can be added or removed.
         */
        $this->weekTitles = [
            '',
            '',
            '',
            '',
            '',
            '',
        ];
    }

    public function editPublication(
        int $publicationId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $publication =
            MorningRevivalPublication::query()
                ->find($publicationId);

        if (! $publication) {
            Notification::make()
                ->title(
                    'Morning Revival publication not found'
                )
                ->warning()
                ->send();

            return;
        }

        $weeks =
            MorningRevivalWeek::query()
                ->where(
                    'morning_revival_publication_id',
                    $publication->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'week_number'
                )
                ->get();

        $this->resetValidation();

        $this->outlinePaste = '';

        $this->editingPublicationId =
            (int) $publication->id;

        $this->sourceTitle =
            (string)
            $publication->source_title;

        $this->generalSubject =
            (string)
            $publication->general_subject;

        $this->startDate =
            $publication->start_date
                ->toDateString();

        $this->isActive =
            (bool)
            $publication->is_active;

        $this->weekTitles =
            $weeks
                ->pluck('title')
                ->map(
                    fn ($title): string =>
                        (string) $title
                )
                ->values()
                ->all();

        if ($this->weekTitles === []) {
            $this->weekTitles = [
                '',
            ];
        }

        /*
         * Editing is primarily about maintaining the
         * weekly message list, so move the browser
         * directly to Weeks / Messages after Livewire
         * finishes rendering the loaded publication.
         */
        $this->dispatch(
            'scroll-to-morning-revival-weeks'
        );
    }

    public function parseOutlinePaste(): void
    {
        $text =
            trim(
                $this->outlinePaste
            );

        if ($text === '') {
            Notification::make()
                ->title(
                    'Paste a Morning Revival outline first'
                )
                ->warning()
                ->send();

            return;
        }

        /*
         * Collapse copied PDF / document whitespace so
         * "Week 2:" can be detected even when everything
         * arrives as one giant pasted paragraph.
         */
        $normalized =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            );

        $normalized =
            trim(
                (string) $normalized
            );

        $matched =
            preg_match_all(
                '/\bWeek\s+(\d+)\s*:\s*'
                . '(.*?)'
                . '(?=\s+\bWeek\s+\d+\s*:|$)/iu',
                $normalized,
                $matches,
                PREG_SET_ORDER
            );

        if (
            ! $matched
            || $matches === []
        ) {
            Notification::make()
                ->title(
                    'No Week headings found'
                )
                ->body(
                    'Use headings such as '
                    . 'Week 1:, Week 2:, and so on.'
                )
                ->warning()
                ->send();

            return;
        }

        if (
            count(
                $matches
            ) > 24
        ) {
            Notification::make()
                ->title(
                    'Too many weeks detected'
                )
                ->body(
                    'A Morning Revival publication '
                    . 'may contain up to 24 weeks.'
                )
                ->warning()
                ->send();

            return;
        }

        $firstWeekPosition = null;

        if (
            preg_match(
                '/\bWeek\s+\d+\s*:/iu',
                $normalized,
                $firstWeekMatch,
                PREG_OFFSET_CAPTURE
            )
        ) {
            $firstWeekPosition =
                $firstWeekMatch[
                    0
                ][1];
        }

        $subject =
            $firstWeekPosition !== null
                ? trim(
                    substr(
                        $normalized,
                        0,
                        $firstWeekPosition
                    )
                )
                : '';

        $parsedWeeks = [];

        foreach (
            $matches
            as $match
        ) {
            $weekNumber =
                (int) $match[1];

            $title =
                trim(
                    (string) $match[2]
                );

            if (
                $weekNumber < 1
                || $weekNumber > 24
                || $title === ''
            ) {
                continue;
            }

            $parsedWeeks[
                $weekNumber
            ] =
                $title;
        }

        if ($parsedWeeks === []) {
            Notification::make()
                ->title(
                    'No usable Week titles found'
                )
                ->warning()
                ->send();

            return;
        }

        ksort(
            $parsedWeeks
        );

        $weekNumbers =
            array_keys(
                $parsedWeeks
            );

        $expectedWeekNumbers =
            range(
                1,
                count(
                    $parsedWeeks
                )
            );

        if (
            $weekNumbers
            !== $expectedWeekNumbers
        ) {
            Notification::make()
                ->title(
                    'Week numbers are incomplete'
                )
                ->body(
                    'The pasted outline must contain '
                    . 'continuous Week numbers starting '
                    . 'with Week 1.'
                )
                ->warning()
                ->send();

            return;
        }

        if ($subject !== '') {
            $this->generalSubject =
                $subject;
        }

        $this->weekTitles =
            array_values(
                $parsedWeeks
            );

        $this->resetValidation();

        Notification::make()
            ->title(
                'Morning Revival outline parsed'
            )
            ->body(
                count(
                    $parsedWeeks
                )
                . ' week'
                . (
                    count(
                        $parsedWeeks
                    ) === 1
                        ? ''
                        : 's'
                )
                . ' loaded for review.'
            )
            ->success()
            ->send();

        $this->dispatch(
            'scroll-to-morning-revival-weeks'
        );
    }

    public function clearOutlinePaste(): void
    {
        $this->outlinePaste = '';
    }

    public function addWeek(): void
    {
        if (
            count(
                $this->weekTitles
            ) >= 24
        ) {
            Notification::make()
                ->title(
                    'Week limit reached'
                )
                ->body(
                    'A publication may contain '
                    . 'up to 24 weeks.'
                )
                ->warning()
                ->send();

            return;
        }

        $this->weekTitles[] = '';
    }

    public function removeWeek(
        int $index
    ): void {
        if (
            ! array_key_exists(
                $index,
                $this->weekTitles
            )
        ) {
            return;
        }

        if (
            count(
                $this->weekTitles
            ) <= 1
        ) {
            Notification::make()
                ->title(
                    'At least one week is required'
                )
                ->warning()
                ->send();

            return;
        }

        unset(
            $this->weekTitles[
                $index
            ]
        );

        $this->weekTitles =
            array_values(
                $this->weekTitles
            );
    }

    public function savePublication(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $data =
            $this->validate([
                'sourceTitle' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'generalSubject' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'startDate' => [
                    'required',
                    'date',
                ],

                'isActive' => [
                    'boolean',
                ],

                'weekTitles' => [
                    'required',
                    'array',
                    'min:1',
                    'max:24',
                ],

                'weekTitles.*' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ]);

        $startDate =
            Carbon::parse(
                $data[
                    'startDate'
                ]
            )->startOfDay();

        if (! $startDate->isMonday()) {
            $this->addError(
                'startDate',
                'Week 1 Day 1 must be a Monday.'
            );

            Notification::make()
                ->title(
                    'Start date must be Monday'
                )
                ->body(
                    'Morning Revival Day 1 is Monday '
                    . 'and Day 6 is Saturday.'
                )
                ->warning()
                ->send();

            return;
        }

        $sourceTitle =
            trim(
                $data[
                    'sourceTitle'
                ]
            );

        $generalSubject =
            trim(
                $data[
                    'generalSubject'
                ]
            );

        $weekTitles =
            collect(
                $data[
                    'weekTitles'
                ]
            )
                ->map(
                    fn ($title): string =>
                        trim(
                            (string) $title
                        )
                )
                ->values()
                ->all();

        $duplicate =
            MorningRevivalPublication::query()
                ->whereDate(
                    'start_date',
                    $startDate
                )
                ->when(
                    $this->editingPublicationId,
                    fn ($query) =>
                        $query->whereKeyNot(
                            $this->editingPublicationId
                        )
                )
                ->exists();

        if ($duplicate) {
            $this->addError(
                'startDate',
                'Another Morning Revival publication '
                . 'already begins on this date.'
            );

            Notification::make()
                ->title(
                    'Publication start date already used'
                )
                ->warning()
                ->send();

            return;
        }

        $publicationId =
            DB::transaction(
                function () use (
                    $sourceTitle,
                    $generalSubject,
                    $startDate,
                    $weekTitles,
                    $data
                ): int {
                    if (
                        $this->editingPublicationId
                    ) {
                        $publication =
                            MorningRevivalPublication
                                ::query()
                                ->lockForUpdate()
                                ->findOrFail(
                                    $this
                                        ->editingPublicationId
                                );

                        $publication->forceFill([
                            'source_title' =>
                                $sourceTitle,

                            'general_subject' =>
                                $generalSubject,

                            'start_date' =>
                                $startDate
                                    ->toDateString(),

                            'is_active' =>
                                (bool)
                                $data[
                                    'isActive'
                                ],
                        ])->save();
                    } else {
                        $publication =
                            MorningRevivalPublication
                                ::query()
                                ->create([
                                    'source_title' =>
                                        $sourceTitle,

                                    'general_subject' =>
                                        $generalSubject,

                                    'start_date' =>
                                        $startDate
                                            ->toDateString(),

                                    'is_active' =>
                                        (bool)
                                        $data[
                                            'isActive'
                                        ],
                                ]);
                    }

                    foreach (
                        $weekTitles
                        as $index => $title
                    ) {
                        $weekNumber =
                            $index + 1;

                        $weekStart =
                            $startDate
                                ->copy()
                                ->addWeeks(
                                    $index
                                );

                        MorningRevivalWeek
                            ::query()
                            ->updateOrCreate(
                                [
                                    'morning_revival_publication_id' =>
                                        $publication->id,

                                    'week_number' =>
                                        $weekNumber,
                                ],
                                [
                                    'title' =>
                                        $title,

                                    'start_date' =>
                                        $weekStart
                                            ->toDateString(),

                                    'is_active' =>
                                        true,
                                ]
                            );
                    }

                    /*
                     * Never delete old week identities.
                     * If the publication is shortened,
                     * unused trailing weeks become inactive.
                     */
                    MorningRevivalWeek::query()
                        ->where(
                            'morning_revival_publication_id',
                            $publication->id
                        )
                        ->where(
                            'week_number',
                            '>',
                            count(
                                $weekTitles
                            )
                        )
                        ->update([
                            'is_active' =>
                                false,
                        ]);

                    return (int)
                    $publication->id;
                }
            );

        $this->editingPublicationId =
            $publicationId;

        Notification::make()
            ->title(
                'Morning Revival publication saved'
            )
            ->body(
                count(
                    $weekTitles
                )
                . ' week'
                . (
                    count(
                        $weekTitles
                    ) === 1
                        ? ''
                        : 's'
                )
                . ' scheduled.'
            )
            ->success()
            ->send();

        $this->editPublication(
            $publicationId
        );
    }

    public function weekStartDate(
        int $index
    ): ?Carbon {
        if (
            trim(
                $this->startDate
            ) === ''
        ) {
            return null;
        }

        try {
            return Carbon::parse(
                $this->startDate
            )
                ->startOfDay()
                ->addWeeks(
                    $index
                );
        } catch (\Throwable) {
            return null;
        }
    }
}
