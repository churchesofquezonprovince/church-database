<?php

namespace App\Filament\Pages;

use App\Models\AttendanceMeetingFormQuestion;
use App\Models\AttendanceMeetingSeries;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Support\LocalityOptions;
use App\Support\MeetingFormDatabaseFieldRegistry;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageAttendanceSheets extends Page
{
    protected string $view =
        'filament.pages.manage-attendance-sheets';

    /*
     * Meeting Series form state is keyed by Attendance Sheet ID
     * because several Sheets can be displayed on this page at once.
     */
    public array $meetingSeriesSelections = [];

    public array $newMeetingSeriesNames = [];

    public array $newMeetingSeriesSlugs = [];

    /*
     * Google Form-like builder state.
     *
     * New-question state is keyed by Attendance Sheet ID.
     * Existing-question state is keyed by Question ID.
     */
    public array $newMeetingFormQuestionTypes = [];

    public array $newMeetingFormQuestionTexts = [];

    public array $newMeetingFormQuestionDescriptions = [];

    public array $newMeetingFormQuestionRequired = [];

    public array $newMeetingFormQuestionOptions = [];

    public array $newMeetingFormQuestionDatabaseFields = [];

    public array $newMeetingFormQuestionAllowCorrection = [];

    public array $meetingFormQuestionTypes = [];

    public array $meetingFormQuestionTexts = [];

    public array $meetingFormQuestionDescriptions = [];

    public array $meetingFormQuestionRequired = [];

    public array $meetingFormQuestionOptions = [];

    public array $meetingFormQuestionDatabaseFields = [];

    public array $meetingFormQuestionAllowCorrection = [];

    public function mount(): void
    {
        /*
         * Pre-fill new permanent Meeting Series fields so the
         * proposed public identity is visible and editable.
         *
         * These are defaults only. Nothing is created until the
         * administrator clicks Create Permanent Link.
         */
        AttendanceSheet::query()
            ->where(
                'sheet_type',
                AttendanceSheet::TYPE_CUSTOM
            )
            ->whereNull(
                'attendance_meeting_series_id'
            )
            ->orderBy('id')
            ->get([
                'id',
                'title',
            ])
            ->each(
                function (
                    AttendanceSheet $sheet
                ): void {
                    $this
                        ->newMeetingSeriesNames[
                            $sheet->id
                        ] =
                            (string)
                            $sheet->title;

                    $this
                        ->newMeetingSeriesSlugs[
                            $sheet->id
                        ] =
                            Str::slug(
                                (string)
                                $sheet->title
                            );
                }
            );

        $this->loadMeetingFormQuestionState();
    }

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
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords()
            ?? false;
    }

    public function selectedStatus(): string
    {
        $status =
            request(
                'status',
                'active'
            );

        return in_array(
            $status,
            [
                'active',
                'archived',
                'all',
            ],
            true
        )
            ? $status
            : 'active';
    }

    public function localityOptions(): array
    {
        return LocalityOptions::groupedActiveConfigured();
    }

    public function statusOptions(): array
    {
        return [
            'active' =>
                'Active',

            'archived' =>
                'Archived',

            'all' =>
                'All',
        ];
    }

    public function statusUrl(
        string $status
    ): string {
        $query =
            request()->query();

        $query['status'] =
            $status;

        if ($status === 'active') {
            unset(
                $query['status']
            );
        }

        return static::getUrl()
            . (
                $query
                    ? '?'
                        . http_build_query(
                            $query
                        )
                    : ''
            );
    }

    public function sheets(): Collection
    {
        return AttendanceSheet::query()
            ->where(
                'sheet_type',
                AttendanceSheet::TYPE_CUSTOM
            )
            ->when(
                $this->selectedStatus()
                    === 'active',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->when(
                $this->selectedStatus()
                    === 'archived',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        false
                    )
            )
            ->withCount([
                'sessions',
                'participants',
            ])
            ->withMax(
                'sessions as latest_session_date',
                'session_date'
            )
            ->with([
                'sessions' =>
                    fn ($query) =>
                        $query
                            ->orderBy(
                                'session_date'
                            )
                            ->orderBy(
                                'id'
                            ),

                'meetingFormQuestions',

                'meetingSeries' =>
                    fn ($query) =>
                        $query
                            ->withCount(
                                'sheets'
                            ),
            ])
            ->when(
                $this->selectedStatus()
                    === 'archived',

                /*
                 * Archived Sheets ignore schedule-type priority.
                 *
                 * Recurring Weekly, Consecutive Days, and Manual
                 * Dates are positioned according to their most
                 * recent Attendance Session.
                 *
                 * start_date is only the fallback when a Sheet
                 * has no Session.
                 */
                fn ($query) =>
                    $query
                        ->orderByRaw(
                            'CASE
                                WHEN latest_session_date IS NULL
                                THEN 1
                                ELSE 0
                            END'
                        )
                        ->orderByDesc(
                            'latest_session_date'
                        )
                        ->orderByDesc(
                            'start_date'
                        )
                        ->orderByDesc(
                            'id'
                        ),

                /*
                 * Active / All retain normal operational priority.
                 */
                fn ($query) =>
                    $query
                        ->orderByDesc(
                            'is_active'
                        )
                        ->orderByRaw(
                            'CASE
                                WHEN schedule_type = ? THEN 1
                                WHEN schedule_type = ? THEN 2
                                WHEN schedule_type = ? THEN 3
                                WHEN schedule_type = ? THEN 4
                                ELSE 5
                            END',
                            [
                                AttendanceSheet::SCHEDULE_ONE_TIME,
                                AttendanceSheet::SCHEDULE_CONSECUTIVE,
                                AttendanceSheet::SCHEDULE_MANUAL,
                                AttendanceSheet::SCHEDULE_RECURRING,
                            ]
                        )
                        ->orderByRaw(
                            'CASE
                                WHEN start_date IS NULL
                                THEN 1
                                ELSE 0
                            END'
                        )
                        ->orderBy(
                            'start_date'
                        )
                        ->orderBy(
                            'id'
                        )
            )
            ->get();
    }

    /*
     * ============================================================
     * PHASE 28M.1 — GOOGLE FORM-LIKE BUILDER
     * ============================================================
     */

    public function meetingFormQuestionTypeOptions(): array
    {
        return [
            AttendanceMeetingFormQuestion::TYPE_SHORT_ANSWER =>
                'Short Answer',

            AttendanceMeetingFormQuestion::TYPE_PARAGRAPH =>
                'Paragraph',

            AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE =>
                'Multiple Choice',

            AttendanceMeetingFormQuestion::TYPE_CHECKBOXES =>
                'Checkboxes',

            AttendanceMeetingFormQuestion::TYPE_DROPDOWN =>
                'Dropdown',

            AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD =>
                'Database Field',

            AttendanceMeetingFormQuestion::TYPE_NOTICE =>
                'Notice',
        ];
    }

    public function meetingFormDatabaseFieldOptions(): array
    {
        return collect(
            MeetingFormDatabaseFieldRegistry::definitions()
        )
            ->mapWithKeys(
                fn (
                    array $definition,
                    string $key
                ): array => [
                    $key =>
                        $definition['label'],
                ]
            )
            ->all();
    }

    public function meetingFormDatabaseFieldGroups(): array
    {
        return MeetingFormDatabaseFieldRegistry::groupedOptions();
    }

    public function meetingFormDatabaseFieldDefinition(
        ?string $field
    ): ?array {
        return MeetingFormDatabaseFieldRegistry::definition(
            $field
        );
    }

    public function selectNewMeetingFormDatabaseField(
        int $sheetId,
        string $field
    ): void {
        if (
            ! MeetingFormDatabaseFieldRegistry::definition(
                $field
            )
        ) {
            return;
        }

        $this->newMeetingFormQuestionDatabaseFields[
            $sheetId
        ] = $field;

        $this->newMeetingFormQuestionTexts[
            $sheetId
        ] =
            MeetingFormDatabaseFieldRegistry::label(
                $field
            )
            ?? '';
    }

    public function selectMeetingFormDatabaseField(
        int $questionId,
        string $field
    ): void {
        if (
            ! MeetingFormDatabaseFieldRegistry::definition(
                $field
            )
        ) {
            return;
        }

        $this->meetingFormQuestionDatabaseFields[
            $questionId
        ] = $field;

        $this->meetingFormQuestionTexts[
            $questionId
        ] =
            MeetingFormDatabaseFieldRegistry::label(
                $field
            )
            ?? '';
    }


    public function addMeetingFormQuestion(
        int $sheetId
    ): void {
        $this->authorizeManagement();

        $sheet =
            $this->managedSheet(
                $sheetId
            );

        if (! $sheet) {
            $this->sheetNotFound();

            return;
        }

        if (
            $sheet->meeting_form_type
            !== AttendanceSheet::MEETING_FORM_GOOGLE
        ) {
            Notification::make()
                ->title('Google Form-like mode is not enabled')
                ->body(
                    'Save this Attendance Sheet with Google Form-like selected first.'
                )
                ->warning()
                ->send();

            return;
        }

        $data = validator(
            [
                'question_type' =>
                    $this
                        ->newMeetingFormQuestionTypes[
                            $sheetId
                        ]
                        ?? AttendanceMeetingFormQuestion::TYPE_SHORT_ANSWER,

                'question_text' =>
                    $this
                        ->newMeetingFormQuestionTexts[
                            $sheetId
                        ]
                        ?? '',

                'description' =>
                    $this
                        ->newMeetingFormQuestionDescriptions[
                            $sheetId
                        ]
                        ?? null,

                'is_required' =>
                    (bool) (
                        $this
                            ->newMeetingFormQuestionRequired[
                                $sheetId
                            ]
                            ?? false
                    ),

                'options' =>
                    $this
                        ->newMeetingFormQuestionOptions[
                            $sheetId
                        ]
                        ?? null,

                'database_field' =>
                    $this
                        ->newMeetingFormQuestionDatabaseFields[
                            $sheetId
                        ]
                        ?? null,

                'allow_correction' =>
                    (bool) (
                        $this
                            ->newMeetingFormQuestionAllowCorrection[
                                $sheetId
                            ]
                            ?? true
                    ),
            ],
            [
                'question_type' => [
                    'required',
                    'in:short_answer,paragraph,multiple_choice,checkboxes,dropdown,database_field,notice',
                ],

                'question_text' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'is_required' => [
                    'boolean',
                ],

                'options' => [
                    'nullable',
                    'array',
                ],

                'options.*' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'database_field' => [
                    'nullable',
                    Rule::in(
                        MeetingFormDatabaseFieldRegistry::keys()
                    ),
                ],

                'allow_correction' => [
                    'boolean',
                ],
            ]
        )->validate();

        if (
            $data['question_type']
            === AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
            &&
            blank(
                $data['database_field']
                ?? null
            )
        ) {
            throw ValidationException::withMessages([
                'newMeetingFormQuestionDatabaseFields.'
                . $sheetId =>
                    'Select the database field to use.',
            ]);
        }

        $options =
            $this->normalizedMeetingFormQuestionOptions(
                $data['options'] ?? null
            );

        if (
            $this->meetingFormQuestionRequiresOptions(
                $data['question_type']
            )
            && count($options) < 2
        ) {
            throw ValidationException::withMessages([
                'newMeetingFormQuestionOptions.'
                . $sheetId =>
                    'Enter at least two choices, one per line.',
            ]);
        }

        $question =
            $sheet
                ->meetingFormQuestions()
                ->create([
                    'question_type' =>
                        $data['question_type'],

                    'database_field' =>
                        $data['question_type']
                        === AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
                            ? $data['database_field']
                            : null,

                    'question_text' =>
                        trim(
                            $data['question_text']
                        ),

                    'description' =>
                        filled(
                            $data['description']
                            ?? null
                        )
                            ? trim(
                                $data['description']
                            )
                            : null,

                    'options' =>
                        $this->meetingFormQuestionRequiresOptions(
                            $data['question_type']
                        )
                            ? $options
                            : null,

                    'is_required' =>
                        (bool) $data['is_required'],

                    'allow_correction' =>
                        $data['question_type']
                        === AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
                            ? (bool) $data['allow_correction']
                            : false,

                    'sort_order' =>
                        (
                            (int) (
                                $sheet
                                    ->meetingFormQuestions()
                                    ->max(
                                        'sort_order'
                                    )
                                ?? 0
                            )
                        ) + 10,
                ]);

        $this->hydrateMeetingFormQuestionState(
            $question
        );

        $this->newMeetingFormQuestionTypes[
            $sheetId
        ] =
            AttendanceMeetingFormQuestion::TYPE_SHORT_ANSWER;

        $this->newMeetingFormQuestionTexts[
            $sheetId
        ] = '';

        $this->newMeetingFormQuestionDescriptions[
            $sheetId
        ] = '';

        $this->newMeetingFormQuestionRequired[
            $sheetId
        ] = false;

        $this->newMeetingFormQuestionOptions[
            $sheetId
        ] = [
            '',
            '',
        ];

        $this->newMeetingFormQuestionDatabaseFields[
            $sheetId
        ] =
            AttendanceMeetingFormQuestion::DATABASE_FIELD_BIRTHDATE;

        $this->newMeetingFormQuestionAllowCorrection[
            $sheetId
        ] = true;

        Notification::make()
            ->title('Question added')
            ->success()
            ->send();
    }

    public function saveMeetingFormQuestion(
        int $questionId
    ): void {
        $this->authorizeManagement();

        $question =
            $this->managedMeetingFormQuestion(
                $questionId
            );

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        $data = validator(
            [
                'question_type' =>
                    $this
                        ->meetingFormQuestionTypes[
                            $questionId
                        ]
                        ?? $question->question_type,

                'question_text' =>
                    $this
                        ->meetingFormQuestionTexts[
                            $questionId
                        ]
                        ?? $question->question_text,

                'description' =>
                    $this
                        ->meetingFormQuestionDescriptions[
                            $questionId
                        ]
                        ?? $question->description,

                'is_required' =>
                    (bool) (
                        $this
                            ->meetingFormQuestionRequired[
                                $questionId
                            ]
                            ?? false
                    ),

                'options' =>
                    $this
                        ->meetingFormQuestionOptions[
                            $questionId
                        ]
                        ?? null,

                'database_field' =>
                    $this
                        ->meetingFormQuestionDatabaseFields[
                            $questionId
                        ]
                        ?? $question->database_field,

                'allow_correction' =>
                    (bool) (
                        $this
                            ->meetingFormQuestionAllowCorrection[
                                $questionId
                            ]
                            ?? $question->allow_correction
                    ),
            ],
            [
                'question_type' => [
                    'required',
                    'in:short_answer,paragraph,multiple_choice,checkboxes,dropdown,database_field,notice',
                ],

                'question_text' => [
                    'required',
                    'string',
                    'max:500',
                ],

                'description' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'is_required' => [
                    'boolean',
                ],

                'options' => [
                    'nullable',
                    'array',
                ],

                'options.*' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'database_field' => [
                    'nullable',
                    Rule::in(
                        MeetingFormDatabaseFieldRegistry::keys()
                    ),
                ],

                'allow_correction' => [
                    'boolean',
                ],
            ]
        )->validate();

        if (
            $data['question_type']
            === AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
            &&
            blank(
                $data['database_field']
                ?? null
            )
        ) {
            throw ValidationException::withMessages([
                'meetingFormQuestionDatabaseFields.'
                . $questionId =>
                    'Select the database field to use.',
            ]);
        }

        $options =
            $this->normalizedMeetingFormQuestionOptions(
                $data['options'] ?? null
            );

        if (
            $this->meetingFormQuestionRequiresOptions(
                $data['question_type']
            )
            && count($options) < 2
        ) {
            throw ValidationException::withMessages([
                'meetingFormQuestionOptions.'
                . $questionId =>
                    'Enter at least two choices, one per line.',
            ]);
        }

        $question->update([
            'question_type' =>
                $data['question_type'],

            'database_field' =>
                $data['question_type']
                === AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
                    ? $data['database_field']
                    : null,

            'question_text' =>
                trim(
                    $data['question_text']
                ),

            'description' =>
                filled(
                    $data['description']
                    ?? null
                )
                    ? trim(
                        $data['description']
                    )
                    : null,

            'options' =>
                $this->meetingFormQuestionRequiresOptions(
                    $data['question_type']
                )
                    ? $options
                    : null,

            'is_required' =>
                $data['question_type']
                === AttendanceMeetingFormQuestion::TYPE_NOTICE
                    ? false
                    : (bool) $data['is_required'],

            'allow_correction' =>
                $data['question_type']
                === AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
                    ? (bool) $data['allow_correction']
                    : false,
        ]);

        $this->hydrateMeetingFormQuestionState(
            $question->fresh()
        );

        Notification::make()
            ->title('Question saved')
            ->success()
            ->send();
    }

    public function deleteMeetingFormQuestion(
        int $questionId
    ): void {
        $this->authorizeManagement();

        $question =
            $this->managedMeetingFormQuestion(
                $questionId
            );

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        $question->delete();

        unset(
            $this->meetingFormQuestionTypes[
                $questionId
            ],
            $this->meetingFormQuestionTexts[
                $questionId
            ],
            $this->meetingFormQuestionDescriptions[
                $questionId
            ],
            $this->meetingFormQuestionRequired[
                $questionId
            ],
            $this->meetingFormQuestionOptions[
                $questionId
            ],
            $this->meetingFormQuestionDatabaseFields[
                $questionId
            ],
            $this->meetingFormQuestionAllowCorrection[
                $questionId
            ],
        );

        Notification::make()
            ->title('Question deleted')
            ->success()
            ->send();
    }

    public function moveMeetingFormQuestion(
        int $questionId,
        string $direction
    ): void {
        $this->authorizeManagement();

        if (
            ! in_array(
                $direction,
                [
                    'up',
                    'down',
                ],
                true
            )
        ) {
            return;
        }

        $question =
            $this->managedMeetingFormQuestion(
                $questionId
            );

        if (! $question) {
            return;
        }

        $questions =
            AttendanceMeetingFormQuestion::query()
                ->where(
                    'attendance_sheet_id',
                    $question->attendance_sheet_id
                )
                ->orderBy(
                    'sort_order'
                )
                ->orderBy(
                    'id'
                )
                ->get()
                ->values();

        $index =
            $questions->search(
                fn (
                    AttendanceMeetingFormQuestion $candidate
                ): bool =>
                    (int) $candidate->id
                    === $questionId
            );

        if ($index === false) {
            return;
        }

        $target =
            $direction === 'up'
                ? $index - 1
                : $index + 1;

        if (
            $target < 0
            || $target >= $questions->count()
        ) {
            return;
        }

        $ordered =
            $questions->all();

        [
            $ordered[$index],
            $ordered[$target],
        ] = [
            $ordered[$target],
            $ordered[$index],
        ];

        DB::transaction(
            function () use (
                $ordered
            ): void {
                foreach (
                    $ordered
                    as $position => $item
                ) {
                    $item->update([
                        'sort_order' =>
                            ($position + 1) * 10,
                    ]);
                }
            }
        );
    }

    private function loadMeetingFormQuestionState(): void
    {
        AttendanceMeetingFormQuestion::query()
            ->whereHas(
                'sheet',
                fn ($query) =>
                    $query->where(
                        'sheet_type',
                        AttendanceSheet::TYPE_CUSTOM
                    )
            )
            ->orderBy(
                'attendance_sheet_id'
            )
            ->orderBy(
                'sort_order'
            )
            ->orderBy(
                'id'
            )
            ->get()
            ->each(
                fn (
                    AttendanceMeetingFormQuestion $question
                ) =>
                    $this
                        ->hydrateMeetingFormQuestionState(
                            $question
                        )
            );
    }

    private function hydrateMeetingFormQuestionState(
        AttendanceMeetingFormQuestion $question
    ): void {
        $questionId =
            (int) $question->id;

        $this->meetingFormQuestionTypes[
            $questionId
        ] =
            $question->question_type;

        $this->meetingFormQuestionTexts[
            $questionId
        ] =
            $question->question_text;

        $this->meetingFormQuestionDescriptions[
            $questionId
        ] =
            $question->description ?? '';

        $this->meetingFormQuestionRequired[
            $questionId
        ] =
            (bool) $question->is_required;

        $this->meetingFormQuestionOptions[
            $questionId
        ] =
            array_values(
                $question->options ?? []
            );

        $this->meetingFormQuestionDatabaseFields[
            $questionId
        ] =
            $question->database_field
            ?? AttendanceMeetingFormQuestion::DATABASE_FIELD_BIRTHDATE;

        $this->meetingFormQuestionAllowCorrection[
            $questionId
        ] =
            (bool) $question->allow_correction;
    }

    public function addNewMeetingFormQuestionOption(
        int $sheetId
    ): void {
        $options =
            $this->newMeetingFormQuestionOptions[
                $sheetId
            ] ?? [];

        if (! is_array($options)) {
            $options = [];
        }

        $options =
            array_values(
                $options
            );

        while (count($options) < 2) {
            $options[] = '';
        }

        $options[] = '';

        $this->newMeetingFormQuestionOptions[
            $sheetId
        ] =
            array_values(
                $options
            );
    }

    public function removeNewMeetingFormQuestionOption(
        int $sheetId,
        int $index
    ): void {
        $options =
            $this->newMeetingFormQuestionOptions[
                $sheetId
            ] ?? [];

        if (! is_array($options)) {
            return;
        }

        unset(
            $options[
                $index
            ]
        );

        $this->newMeetingFormQuestionOptions[
            $sheetId
        ] =
            array_values(
                $options
            );
    }

    public function addMeetingFormQuestionOption(
        int $questionId
    ): void {
        $options =
            $this->meetingFormQuestionOptions[
                $questionId
            ] ?? [];

        if (! is_array($options)) {
            $options = [];
        }

        $options =
            array_values(
                $options
            );

        while (count($options) < 2) {
            $options[] = '';
        }

        $options[] = '';

        $this->meetingFormQuestionOptions[
            $questionId
        ] =
            array_values(
                $options
            );
    }

    public function removeMeetingFormQuestionOption(
        int $questionId,
        int $index
    ): void {
        $options =
            $this->meetingFormQuestionOptions[
                $questionId
            ] ?? [];

        if (! is_array($options)) {
            return;
        }

        unset(
            $options[
                $index
            ]
        );

        $this->meetingFormQuestionOptions[
            $questionId
        ] =
            array_values(
                $options
            );
    }

    private function normalizedMeetingFormQuestionOptions(
        mixed $value
    ): array {
        $options =
            is_array($value)
                ? $value
                : preg_split(
                    '/\\R/',
                    (string) $value
                );

        return collect(
            $options
        )
            ->map(
                fn ($option): string =>
                    trim(
                        (string) $option
                    )
            )
            ->filter(
                fn (string $option): bool =>
                    $option !== ''
            )
            ->unique(
                fn (string $option): string =>
                    mb_strtolower(
                        $option
                    )
            )
            ->values()
            ->all();
    }

    private function meetingFormQuestionRequiresOptions(
        string $type
    ): bool {
        return in_array(
            $type,
            [
                AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE,
                AttendanceMeetingFormQuestion::TYPE_CHECKBOXES,
                AttendanceMeetingFormQuestion::TYPE_DROPDOWN,
            ],
            true
        );
    }

    private function managedMeetingFormQuestion(
        int $questionId
    ): ?AttendanceMeetingFormQuestion {
        return AttendanceMeetingFormQuestion::query()
            ->whereHas(
                'sheet',
                fn ($query) =>
                    $query->where(
                        'sheet_type',
                        AttendanceSheet::TYPE_CUSTOM
                    )
            )
            ->find(
                $questionId
            );
    }


    /*
     * ============================================================
     * PHASE 27J — PERMANENT MEETING SERIES
     * ============================================================
     */

    public function meetingSeriesOptions(): Collection
    {
        /*
         * Cache only for this PHP request/render so rendering
         * multiple Attendance Sheet cards does not repeatedly
         * query the same Meeting Series list.
         */
        static $options = null;

        if ($options instanceof Collection) {
            return $options;
        }

        $options =
            AttendanceMeetingSeries::query()
                ->where(
                    'is_active',
                    true
                )
                ->withCount(
                    'sheets'
                )
                ->orderBy(
                    'name'
                )
                ->get();

        return $options;
    }

    public function meetingSeriesStatus(
        AttendanceMeetingSeries $series
    ): array {
        $today =
            CarbonImmutable::today()
                ->toDateString();

        /*
         * Only active Sheets whose public Meeting Form is enabled
         * can currently serve the permanent public URL.
         */
        $baseQuery =
            AttendanceSession::query()
                ->whereHas(
                    'sheet',
                    function (
                        $query
                    ) use (
                        $series
                    ): void {
                        $query
                            ->where(
                                'attendance_meeting_series_id',
                                $series->id
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'meeting_form_type',
                                AttendanceSheet::MEETING_FORM_NORMAL
                            );
                    }
                )
                ->with(
                    'sheet'
                );

        $todaySessions =
            (clone $baseQuery)
                ->where(
                    'session_date',
                    $today
                )
                ->orderBy(
                    'id'
                )
                ->get();

        if (
            $todaySessions->count()
            > 1
        ) {
            return [
                'status' =>
                    'ambiguous',

                'session' =>
                    null,

                'message' =>
                    'Multiple active Sessions are scheduled today.',
            ];
        }

        if (
            $todaySessions->count()
            === 1
        ) {
            return [
                'status' =>
                    'today',

                'session' =>
                    $todaySessions
                        ->first(),

                'message' =>
                    'Today',
            ];
        }

        $nextDate =
            (clone $baseQuery)
                ->where(
                    'session_date',
                    '>',
                    $today
                )
                ->min(
                    'session_date'
                );

        if (blank($nextDate)) {
            return [
                'status' =>
                    'none',

                'session' =>
                    null,

                'message' =>
                    'No upcoming Session',
            ];
        }

        $nextDate =
            CarbonImmutable::parse(
                $nextDate
            )->toDateString();

        $nextSessions =
            (clone $baseQuery)
                ->where(
                    'session_date',
                    $nextDate
                )
                ->orderBy(
                    'id'
                )
                ->get();

        if (
            $nextSessions->count()
            > 1
        ) {
            return [
                'status' =>
                    'ambiguous',

                'session' =>
                    null,

                'message' =>
                    'Multiple active Sessions are scheduled for '
                    . CarbonImmutable::parse(
                        $nextDate
                    )->format(
                        'M d, Y'
                    )
                    . '.',
            ];
        }

        return [
            'status' =>
                'upcoming',

            'session' =>
                $nextSessions
                    ->first(),

            'message' =>
                'Next scheduled Session',
        ];
    }

    public function createMeetingSeries(
        int $sheetId
    ): void {
        $this->authorizeManagement();

        $sheet =
            $this->managedSheet(
                $sheetId
            );

        if (! $sheet) {
            $this->sheetNotFound();

            return;
        }

        /*
         * Avoid silently abandoning an existing permanent identity.
         * Changing to another existing Series is handled separately.
         */
        if (
            filled(
                $sheet
                    ->attendance_meeting_series_id
            )
        ) {
            Notification::make()
                ->title(
                    'Attendance Sheet already has a permanent link'
                )
                ->body(
                    'Use Change Meeting Series if this Sheet should use a different permanent meeting identity.'
                )
                ->warning()
                ->send();

            return;
        }

        $name =
            trim(
                (string) (
                    $this
                        ->newMeetingSeriesNames[
                            $sheetId
                        ]
                    ?? ''
                )
            );

        if ($name === '') {
            $name =
                trim(
                    (string)
                    $sheet->title
                );
        }

        if ($name === '') {
            Notification::make()
                ->title(
                    'Meeting Series name is required'
                )
                ->warning()
                ->send();

            return;
        }

        $slugInput =
            trim(
                (string) (
                    $this
                        ->newMeetingSeriesSlugs[
                            $sheetId
                        ]
                    ?? ''
                )
            );

        $slug =
            Str::slug(
                $slugInput !== ''
                    ? $slugInput
                    : $name
            );

        if ($slug === '') {
            Notification::make()
                ->title(
                    'Permanent link is invalid'
                )
                ->body(
                    'Enter a name or slug that can produce a public link.'
                )
                ->warning()
                ->send();

            return;
        }

        if (
            mb_strlen($slug)
            > 180
        ) {
            Notification::make()
                ->title(
                    'Permanent link is too long'
                )
                ->body(
                    'Use a shorter permanent slug.'
                )
                ->warning()
                ->send();

            return;
        }

        if (
            AttendanceMeetingSeries::query()
                ->where(
                    'public_slug',
                    $slug
                )
                ->exists()
        ) {
            Notification::make()
                ->title(
                    'Permanent Meeting Series already exists'
                )
                ->body(
                    'Attach this Sheet to the existing Meeting Series instead.'
                )
                ->warning()
                ->send();

            return;
        }

        /*
         * Meeting Series slugs and Session slugs share the same
         * m.overcomers.win/{slug} namespace.
         */
        if (
            AttendanceSession::query()
                ->where(
                    'public_slug',
                    $slug
                )
                ->exists()
        ) {
            Notification::make()
                ->title(
                    'Permanent link conflicts with a Session link'
                )
                ->body(
                    'Choose another permanent slug.'
                )
                ->danger()
                ->send();

            return;
        }

        $series =
            DB::transaction(
                function () use (
                    $sheet,
                    $name,
                    $slug
                ): AttendanceMeetingSeries {
                    $series =
                        AttendanceMeetingSeries::create([
                            'name' =>
                                $name,

                            'public_slug' =>
                                $slug,

                            'is_active' =>
                                true,
                        ]);

                    $sheet->update([
                        'attendance_meeting_series_id' =>
                            $series->id,
                    ]);

                    return $series;
                }
            );

        unset(
            $this
                ->newMeetingSeriesNames[
                    $sheetId
                ],

            $this
                ->newMeetingSeriesSlugs[
                    $sheetId
                ]
        );

        Notification::make()
            ->title(
                'Permanent Meeting Link created'
            )
            ->body(
                $series->publicUrl()
            )
            ->success()
            ->send();
    }

    public function attachMeetingSeries(
        int $sheetId
    ): void {
        $this->authorizeManagement();

        $sheet =
            $this->managedSheet(
                $sheetId
            );

        if (! $sheet) {
            $this->sheetNotFound();

            return;
        }

        $seriesId =
            (int) (
                $this
                    ->meetingSeriesSelections[
                        $sheetId
                    ]
                ?? 0
            );

        if ($seriesId <= 0) {
            Notification::make()
                ->title(
                    'Select a Meeting Series'
                )
                ->warning()
                ->send();

            return;
        }

        $series =
            AttendanceMeetingSeries::query()
                ->where(
                    'is_active',
                    true
                )
                ->find(
                    $seriesId
                );

        if (! $series) {
            Notification::make()
                ->title(
                    'Meeting Series not found'
                )
                ->danger()
                ->send();

            return;
        }

        if (
            (int)
            $sheet
                ->attendance_meeting_series_id
            ===
            (int)
            $series->id
        ) {
            Notification::make()
                ->title(
                    'Already attached'
                )
                ->body(
                    'This Attendance Sheet already uses that permanent link.'
                )
                ->success()
                ->send();

            return;
        }

        /*
         * Prevent an immediately ambiguous permanent link.
         *
         * Semester 1 and Semester 2 are fine because their dates
         * do not overlap. The dangerous case is two active,
         * public Sheets in the same Series having Sessions on
         * the exact same date.
         */
        $conflicts =
            $this->seriesDateConflicts(
                $sheet,
                $series
            );

        if ($conflicts->isNotEmpty()) {
            $dates =
                $conflicts
                    ->pluck(
                        'session_date'
                    )
                    ->map(
                        fn ($date): string =>
                            CarbonImmutable::parse(
                                $date
                            )->format(
                                'M d, Y'
                            )
                    )
                    ->unique()
                    ->implode(
                        ', '
                    );

            Notification::make()
                ->title(
                    'Meeting Series date conflict'
                )
                ->body(
                    'Another active Sheet in this Meeting Series already has a public Session on: '
                    . $dates
                    . '.'
                )
                ->danger()
                ->send();

            return;
        }

        $sheet->update([
            'attendance_meeting_series_id' =>
                $series->id,
        ]);

        Notification::make()
            ->title(
                'Permanent Meeting Series attached'
            )
            ->body(
                $sheet->title
                . ' now uses '
                . $series->publicUrl()
            )
            ->success()
            ->send();
    }

    public function detachMeetingSeries(
        int $sheetId
    ): void {
        $this->authorizeManagement();

        $sheet =
            $this->managedSheet(
                $sheetId
            );

        if (! $sheet) {
            $this->sheetNotFound();

            return;
        }

        if (
            blank(
                $sheet
                    ->attendance_meeting_series_id
            )
        ) {
            return;
        }

        /*
         * Detaching a Semester/Sheet NEVER deletes the Meeting
         * Series. Other semesters and future academic years may
         * still depend on that permanent public URL.
         */
        $sheet->update([
            'attendance_meeting_series_id' =>
                null,
        ]);

        unset(
            $this
                ->meetingSeriesSelections[
                    $sheetId
                ]
        );

        Notification::make()
            ->title(
                'Meeting Series detached'
            )
            ->body(
                'The permanent Meeting Series and its URL were preserved.'
            )
            ->success()
            ->send();
    }

    private function seriesDateConflicts(
        AttendanceSheet $sheet,
        AttendanceMeetingSeries $series
    ): Collection {
        /*
         * An archived Sheet or disabled public form cannot
         * currently conflict with public resolution.
         */
        if (
            ! $sheet->is_active
            ||
            ! $sheet->meetingFormEnabled()
        ) {
            return collect();
        }

        $dates =
            $sheet
                ->sessions()
                ->pluck(
                    'session_date'
                )
                ->map(
                    fn ($date): string =>
                        CarbonImmutable::parse(
                            $date
                        )->toDateString()
                )
                ->unique()
                ->values();

        if ($dates->isEmpty()) {
            return collect();
        }

        return AttendanceSession::query()
            ->where(
                'attendance_sheet_id',
                '!=',
                $sheet->id
            )
            ->whereIn(
                'session_date',
                $dates->all()
            )
            ->whereHas(
                'sheet',
                function (
                    $query
                ) use (
                    $series
                ): void {
                    $query
                        ->where(
                            'attendance_meeting_series_id',
                            $series->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->where(
                            'meeting_form_type',
                            AttendanceSheet::MEETING_FORM_NORMAL
                        );
                }
            )
            ->orderBy(
                'session_date'
            )
            ->get();
    }

    private function managedSheet(
        int $sheetId
    ): ?AttendanceSheet {
        return AttendanceSheet::query()
            ->where(
                'sheet_type',
                AttendanceSheet::TYPE_CUSTOM
            )
            ->find(
                $sheetId
            );
    }

    private function authorizeManagement(): void
    {
        abort_unless(
            auth()->user()
                ?->canManageRecords(),
            403
        );
    }

    private function sheetNotFound(): void
    {
        Notification::make()
            ->title(
                'Attendance Sheet not found'
            )
            ->danger()
            ->send();
    }
}
