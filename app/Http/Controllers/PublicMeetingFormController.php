<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMeetingFormAnswer;
use App\Models\AttendanceMeetingFormQuestion;
use App\Models\AttendanceMeetingProfileCorrection;
use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceSheet;
use App\Models\AttendanceMeetingSeries;
use App\Models\AttendanceSession;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\DeveloperSetting;
use App\Models\School;
use App\Models\Province;
use App\Models\Person;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\LocalityOptions;
use App\Support\MeetingFormDatabaseFieldMatcher;
use App\Support\MeetingFormDatabaseFieldRegistry;
use App\Support\MeetingFormRespondentResolver;
use App\Support\MeetingFormProfileCorrectionRecorder;
use App\Support\MeetingFormReferenceProposalRecorder;
use Illuminate\View\View;

class PublicMeetingFormController extends Controller
{
    public function show(string $slug): View
    {
        $session = $this->publicSession($slug);
        $sheet = $session->sheet;

        if (
            $sheet->meeting_form_type
            === AttendanceSheet::MEETING_FORM_GOOGLE
        ) {
            return view(
                'meeting.google-form',
                [
                    'session' =>
                        $session,

                    'sheet' =>
                        $sheet,

                    'questions' =>
                        $sheet
                            ->meetingFormQuestions()
                            ->get(),

                    'publicSlug' =>
                        trim($slug),

                    'localityGroups' =>
                        LocalityOptions::groupedActiveConfigured(),

                    'schools' =>
                        School::query()
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy('name')
                            ->pluck(
                                'name',
                                'id'
                            )
                            ->all(),

                    'provinces' =>
                        Province::query()
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy('name')
                            ->pluck(
                                'name',
                                'id'
                            )
                            ->all(),
                ]
            );
        }

        return view('meeting.show', [
            'session' => $session,
            'sheet' => $sheet,

            /*
             * Preserve the public identity used to reach
             * this page.
             *
             * This may be either:
             *
             * - an exact Session slug
             * - a permanent Meeting Series slug
             */
            'publicSlug' => trim($slug),
        ]);
    }

    public function search(
        Request $request,
        string $slug
    ): JsonResponse {
        /*
         * Also verifies that the public meeting form
         * is currently active.
         */
        $session =
            $this->publicSession($slug);

        $query = trim(
            (string) $request->query('q', '')
        );

        /*
         * Do not expose a downloadable full directory.
         *
         * The search still covers the entire People and
         * Campus databases, but requires at least two
         * typed characters.
         */
        if (mb_strlen($query) < 3) {
            return response()->json([
                'results' => [],
            ]);
        }

        $like = '%' . $query . '%';

        /*
         * -------------------------------------------------
         * PEOPLE DATABASE
         * -------------------------------------------------
         */
        $people = Person::query()
            ->where(function ($builder) use ($like): void {
                $builder
                    ->where('firstname', 'like', $like)
                    ->orWhere('middlename', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhere('nickname', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(
                            ' ',
                            firstname,
                            lastname
                        ) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(
                            ' ',
                            lastname,
                            firstname
                        ) LIKE ?",
                        [$like]
                    );
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(10)
            ->get()
            ->map(
                fn (Person $person): array => [
                    'token' =>
                        $this->publicIdentityToken(
                            $session,
                            AttendanceMeetingResponse::RESPONDENT_PERSON,
                            (int) $person->id
                        ),

                    'name' =>
                        $person->display_name,

                    'source' =>
                        'Existing Record',
                ]
            );

        /*
         * -------------------------------------------------
         * CAMPUS DATABASE
         * -------------------------------------------------
         *
         * Campus Contacts already linked to People are NOT
         * shown again. Their Person record is canonical.
         */
        $campus = CampusContact::query()
            ->whereNull('person_id')
            ->where(function ($builder) use ($like): void {
                $builder
                    ->where('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(
                            ' ',
                            firstname,
                            lastname
                        ) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(
                            ' ',
                            lastname,
                            firstname
                        ) LIKE ?",
                        [$like]
                    );
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(10)
            ->get()
            ->map(
                fn (CampusContact $contact): array => [
                    'token' =>
                        $this->publicIdentityToken(
                            $session,
                            AttendanceMeetingResponse::RESPONDENT_CAMPUS,
                            (int) $contact->id
                        ),

                    'name' =>
                        $contact->display_name,

                    'source' =>
                        'Existing Record',
                ]
            );

        /*
         * -------------------------------------------------
         * GOSPEL CONTACTS
         * -------------------------------------------------
         *
         * As with Campus Contacts, linked Gospel Contacts
         * are represented by their canonical Person record
         * and must not appear a second time.
         */
        $gospel = GospelContact::query()
            ->whereNull('person_id')
            ->where(function ($builder) use ($like): void {
                $builder
                    ->where('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhereRaw(
                        "CONCAT_WS(
                            ' ',
                            firstname,
                            lastname
                        ) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(
                            ' ',
                            lastname,
                            firstname
                        ) LIKE ?",
                        [$like]
                    );
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(10)
            ->get()
            ->map(
                fn (GospelContact $contact): array => [
                    'token' =>
                        $this->publicIdentityToken(
                            $session,
                            AttendanceMeetingResponse::RESPONDENT_GOSPEL,
                            (int) $contact->id
                        ),

                    'name' =>
                        $contact->display_name,

                    'source' =>
                        'Existing Record',
                ]
            );

        /*
         * Return only a small number of neutral public
         * results. The browser never receives the database
         * source or the underlying numeric record ID.
         */
        $results = $people
            ->concat($campus)
            ->concat($gospel)
            ->sortBy(
                fn (array $row): string =>
                    mb_strtolower($row['name'])
            )
            ->take(10)
            ->values();

        return response()->json([
            'results' => $results,
        ]);
    }

    public function autofill(
        Request $request,
        string $slug,
        MeetingFormRespondentResolver $resolver,
        MeetingFormDatabaseFieldMatcher $matcher
    ): JsonResponse {
        $session =
            $this->publicSession(
                $slug
            );

        abort_unless(
            $session->sheet
                && $session
                    ->sheet
                    ->meeting_form_type
                    === AttendanceSheet::MEETING_FORM_GOOGLE,
            404
        );

        $data = $request->validate([
            'respondent_token' => [
                'required',
                'string',
                'max:4096',
            ],

            'answers' => [
                'nullable',
                'array',
            ],
        ]);

        $identityToken =
            $this->decodePublicIdentityToken(
                $session,
                $data['respondent_token']
            );

        $identity =
            $resolver->resolve(
                $identityToken['type'],
                $identityToken['id']
            );

        if (! $identity) {
            throw ValidationException::withMessages([
                'respondent_token' =>
                    'Please select your name again.',
            ]);
        }

        /*
         * A previously submitted pending Database Field change
         * becomes the effective value for future public-form
         * autofill until it is approved, rejected, replaced,
         * or reverted to the canonical database value.
         *
         * The canonical database itself is NOT modified here.
         */
        $existingResponse =
            $this->existingMeetingResponseForIdentity(
                $session,
                $identity
            );

        $pendingValues =
            $this->pendingDatabaseFieldValues(
                $existingResponse
            );

        $questions =
            $session
                ->sheet
                ->meetingFormQuestions()
                ->where(
                    'question_type',
                    AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
                )
                ->get();

        $questionMap =
            $questions->keyBy(
                fn (
                    AttendanceMeetingFormQuestion $question
                ): string =>
                    (string) $question->id
            );

        $submittedFields = [];

        foreach (
            ($data['answers'] ?? [])
            as $questionId => $value
        ) {
            $question =
                $questionMap->get(
                    (string) $questionId
                );

            if (
                ! $question
                || blank(
                    $question->database_field
                )
                || is_array($value)
                || is_object($value)
            ) {
                continue;
            }

            $field =
                (string)
                $question->database_field;

            /*
             * Duplicate Database Field questions never earn
             * more than one hidden match.
             *
             * Prefer a non-blank submitted value.
             */
            if (
                ! array_key_exists(
                    $field,
                    $submittedFields
                )
                || blank(
                    $submittedFields[$field]
                )
            ) {
                $submittedFields[$field] =
                    $value;
            }
        }

        /*
         * Start with positive matches against canonical data.
         */
        $matches =
            $matcher->matchingFields(
                $identity,
                $submittedFields
            );

        /*
         * Also accept the respondent's latest still-pending
         * proposed value.
         *
         * Example:
         *
         * canonical workplace = Southern Luzon State University
         * pending workplace   = SLSU
         *
         * On a later visit, entering SLSU is treated as the
         * respondent's current effective value.
         */
        foreach (
            $submittedFields
            as $field => $submittedValue
        ) {
            if (
                in_array(
                    $field,
                    $matches,
                    true
                )
                || ! MeetingFormDatabaseFieldRegistry
                    ::isAutofillMatchEligible(
                        $field
                    )
                || ! array_key_exists(
                    $field,
                    $pendingValues
                )
            ) {
                continue;
            }

            $normalizedSubmitted =
                $matcher->normalizedValue(
                    $field,
                    $submittedValue
                );

            $normalizedPending =
                $matcher->normalizedValue(
                    $field,
                    $pendingValues[$field]
                );

            if (
                $normalizedSubmitted !== null
                && $normalizedPending !== null
                && hash_equals(
                    $normalizedPending,
                    $normalizedSubmitted
                )
            ) {
                $matches[] =
                    $field;
            }
        }

        $matches =
            array_values(
                array_unique(
                    $matches
                )
            );

        $threshold =
            max(
                0,
                min(
                    5,
                    DeveloperSetting::integer(
                        DeveloperSetting::KEY_MEETING_FORM_AUTOFILL_MATCHES,
                        2
                    )
                )
            );

        /*
         * Absolutely no stored profile values leave the server
         * until the configured number of DISTINCT positive
         * matches has been reached.
         *
         * Wrong/different values do not subtract anything.
         */
        if (
            $threshold < 1
            || count($matches) < $threshold
        ) {
            return response()->json([
                'autofill' =>
                    false,

                'values' =>
                    [],
            ]);
        }

        $values = [];

        foreach ($questions as $question) {
            $field =
                (string)
                $question->database_field;

            /*
             * Pending participant-supplied information wins
             * over the older canonical value for this public
             * form's future autofill.
             */
            if (
                array_key_exists(
                    $field,
                    $pendingValues
                )
            ) {
                $value =
                    $pendingValues[$field];
            } else {
                $resolved =
                    $resolver->fieldValue(
                        $identity,
                        $field
                    );

                if (
                    ! (
                        $resolved[
                            'has_existing_value'
                        ]
                        ?? false
                    )
                ) {
                    continue;
                }

                $value =
                    $resolved['value']
                    ?? null;
            }

            if (is_array($value)) {
                $value =
                    collect($value)
                        ->filter(
                            fn ($item): bool =>
                                filled($item)
                        )
                        ->implode(', ');
            }

            if (
                $value === null
                || $value === ''
            ) {
                continue;
            }

            $values[
                (string) $question->id
            ] = $value;
        }

        return response()->json([
            'autofill' =>
                true,

            'values' =>
                $values,
        ]);
    }


    public function store(
        Request $request,
        string $slug
    ): RedirectResponse {
        $session = $this->publicSession($slug);

        if (
            $session->sheet
                ?->meeting_form_type
            === AttendanceSheet::MEETING_FORM_GOOGLE
        ) {
            return $this->storeGoogleForm(
                $request,
                $session
            );
        }

        $data = $request->validate([
            'respondent_type' => [
                'required',
                'in:existing,guest',
            ],

            'respondent_token' => [
                'nullable',
                'string',
                'max:4096',
            ],

            'guest_name' => [
                'nullable',
                'string',
                'max:255',
            ],

'guest_profile_enabled' => [
    'nullable',
    'boolean',
],

'guest_profile' => [
    'nullable',
    'array',
],

'guest_profile.firstname' => [
    'nullable',
    'string',
    'max:100',
],

'guest_profile.lastname' => [
    'nullable',
    'string',
    'max:100',
],

'guest_profile.sex' => [
    'nullable',
    'in:Male,Female',
],

'guest_profile.locality' => [
    'nullable',
    'string',
    'max:150',
],

'guest_profile.school_campus' => [
    'nullable',
    'string',
    'max:255',
],

'guest_profile.course_strand' => [
    'nullable',
    'string',
    'max:255',
],

'guest_profile.grade_level' => [
    'nullable',
    'string',
    'max:100',
],

'guest_profile.contact_number' => [
    'nullable',
    'string',
    'max:20',
],

'guest_profile.email' => [
    'nullable',
    'email',
    'max:255',
],

'guest_profile.facebook_account' => [
    'nullable',
    'string',
    'max:255',
],

            'response' => [
                'required',
                'in:yes,no',
            ],
        ]);

        $type =
            $data['respondent_type'];

        if ($type === 'existing') {
            $identity =
                $this->decodePublicIdentityToken(
                    $session,
                    $data['respondent_token']
                        ?? null
                );

            $type =
                $identity['type'];

            /*
             * From this point onward, the existing storage
             * code may use respondent_id internally.
             *
             * This numeric ID never came from the browser.
             */
            $data['respondent_id'] =
                $identity['id'];
        }

        /*
         * -------------------------------------------------
         * PEOPLE DATABASE
         * -------------------------------------------------
         */
        if (
            $type
            === AttendanceMeetingResponse::RESPONDENT_PERSON
        ) {
            if (blank($data['respondent_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'respondent_type' =>
                        'Please select your name.',
                ]);
            }

            $person = Person::query()->find(
                (int) $data['respondent_id']
            );

            if (! $person) {
                throw ValidationException::withMessages([
                    'respondent_type' =>
                        'The selected person could not be found.',
                ]);
            }

            $meetingResponse =
                AttendanceMeetingResponse::query()
                    ->firstOrNew([
                        'attendance_session_id' =>
                            $session->id,

                        'person_id' =>
                            $person->id,
                    ]);

                    if (! $meetingResponse->exists) {
    $meetingResponse->original_source =
        AttendanceMeetingResponse::RESPONDENT_PERSON;
}

            $meetingResponse->forceFill([
                'respondent_type' =>
                    AttendanceMeetingResponse::RESPONDENT_PERSON,

                'person_id' =>
                    $person->id,

                'campus_contact_id' =>
                    null,

                'guest_name' =>
                    null,

                'respondent_name' =>
                    $person->display_name,

                'submitted_form_type' =>
                    AttendanceMeetingResponse::FORM_NORMAL,

                'response' =>
                    $data['response'],

                'responded_at' =>
                    now(),
            ])->save();

            return $this->savedResponseRedirect(
                $session,
                $person->display_name,
                $data['response'],
            );
        }

        /*
         * -------------------------------------------------
         * CAMPUS DATABASE
         * -------------------------------------------------
         */
        if (
            $type
            === AttendanceMeetingResponse::RESPONDENT_CAMPUS
        ) {
            if (blank($data['respondent_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'respondent_type' =>
                        'Please select your name.',
                ]);
            }

            $contact = CampusContact::query()
                ->with('person')
                ->find(
                    (int) $data['respondent_id']
                );

            if (! $contact) {
                throw ValidationException::withMessages([
                    'respondent_type' =>
                        'The selected Campus Contact could not be found.',
                ]);
            }

            /*
             * If the Campus Contact became linked to People
             * after the search result was displayed, use the
             * canonical Person instead.
             */
            if ($contact->person_id && $contact->person) {
                $person = $contact->person;

                $meetingResponse =
                    AttendanceMeetingResponse::query()
                        ->firstOrNew([
                            'attendance_session_id' =>
                                $session->id,

                            'person_id' =>
                                $person->id,
                        ]);

                /*
                 * The Campus search result became Person-linked
                 * after it was displayed. Person is now canonical,
                 * but preserve the Campus origin/identity journey
                 * for a newly created response.
                 */
                if (! $meetingResponse->exists) {
                    $meetingResponse->original_source =
                        AttendanceMeetingResponse::RESPONDENT_CAMPUS;

                    $meetingResponse->campus_contact_id =
                        $contact->id;
                }

                $meetingResponse->forceFill([
                    'respondent_type' =>
                        AttendanceMeetingResponse::RESPONDENT_PERSON,

                    'person_id' =>
                        $person->id,

                    'guest_name' =>
                        null,

                    'respondent_name' =>
                        $person->display_name,

                    'submitted_form_type' =>
                        AttendanceMeetingResponse::FORM_NORMAL,

                    'response' =>
                        $data['response'],

                    'responded_at' =>
                        now(),
                ])->save();

                return $this->savedResponseRedirect(
                    $session,
                    $person->display_name,
                    $data['response'],
                );
            }

            $meetingResponse =
                AttendanceMeetingResponse::query()
                    ->firstOrNew([
                        'attendance_session_id' =>
                            $session->id,

                        'campus_contact_id' =>
                            $contact->id,
                    ]);
if (! $meetingResponse->exists) {
    $meetingResponse->original_source =
        AttendanceMeetingResponse::RESPONDENT_CAMPUS;
}
            $meetingResponse->forceFill([
                'respondent_type' =>
                    AttendanceMeetingResponse::RESPONDENT_CAMPUS,

                'person_id' =>
                    null,

                'campus_contact_id' =>
                    $contact->id,

                'guest_name' =>
                    null,

                'respondent_name' =>
                    $contact->display_name,

                'submitted_form_type' =>
                    AttendanceMeetingResponse::FORM_NORMAL,

                'response' =>
                    $data['response'],

                'responded_at' =>
                    now(),
            ])->save();

            return $this->savedResponseRedirect(
                $session,
                $contact->display_name,
                $data['response'],
            );
        }

        /*
         * -------------------------------------------------
         * GOSPEL CONTACTS
         * -------------------------------------------------
         */
        if (
            $type
            === AttendanceMeetingResponse::RESPONDENT_GOSPEL
        ) {
            $contact = GospelContact::query()
                ->with('person')
                ->find(
                    (int) $data['respondent_id']
                );

            if (! $contact) {
                throw ValidationException::withMessages([
                    'respondent_type' =>
                        'The selected record could not be found.',
                ]);
            }

            /*
             * A Gospel Contact may have become linked to a
             * Person after the search result was issued.
             *
             * Person remains canonical.
             */
            if ($contact->person_id && $contact->person) {
                $person =
                    $contact->person;

                $meetingResponse =
                    AttendanceMeetingResponse::query()
                        ->firstOrNew([
                            'attendance_session_id' =>
                                $session->id,

                            'person_id' =>
                                $person->id,
                        ]);

                if (! $meetingResponse->exists) {
                    $meetingResponse->original_source =
                        AttendanceMeetingResponse::RESPONDENT_GOSPEL;

                    $meetingResponse->gospel_contact_id =
                        $contact->id;
                }

                $meetingResponse->forceFill([
                    'respondent_type' =>
                        AttendanceMeetingResponse::RESPONDENT_PERSON,

                    'person_id' =>
                        $person->id,

                    'guest_name' =>
                        null,

                    'respondent_name' =>
                        $person->display_name,

                    'submitted_form_type' =>
                        AttendanceMeetingResponse::FORM_NORMAL,

                    'response' =>
                        $data['response'],

                    'responded_at' =>
                        now(),
                ])->save();

                return $this->savedResponseRedirect(
                    $session,
                    $person->display_name,
                    $data['response'],
                );
            }

            $meetingResponse =
                AttendanceMeetingResponse::query()
                    ->firstOrNew([
                        'attendance_session_id' =>
                            $session->id,

                        'gospel_contact_id' =>
                            $contact->id,
                    ]);

            if (! $meetingResponse->exists) {
                $meetingResponse->original_source =
                    AttendanceMeetingResponse::RESPONDENT_GOSPEL;
            }

            $meetingResponse->forceFill([
                'respondent_type' =>
                    AttendanceMeetingResponse::RESPONDENT_GOSPEL,

                'person_id' =>
                    null,

                'campus_contact_id' =>
                    null,

                'gospel_contact_id' =>
                    $contact->id,

                'guest_name' =>
                    null,

                'respondent_name' =>
                    $contact->display_name,

                'submitted_form_type' =>
                    AttendanceMeetingResponse::FORM_NORMAL,

                'response' =>
                    $data['response'],

                'responded_at' =>
                    now(),
            ])->save();

            return $this->savedResponseRedirect(
                $session,
                $contact->display_name,
                $data['response'],
            );
        }

        /*
         * -------------------------------------------------
         * MANUALLY ENTERED NAME
         * -------------------------------------------------
         */
        $guestName = trim(
            (string) ($data['guest_name'] ?? '')
        );

        if (mb_strlen($guestName) < 2) {
            throw ValidationException::withMessages([
                'guest_name' =>
                    'Please enter your full name.',
            ]);
        }

        /*
         * A guest can correct their YES/NO response by
         * submitting the same name again.
         *
         * This is deliberately isolated from People and
         * Campus databases.
         */
$meetingResponse =
    AttendanceMeetingResponse::query()
        ->where(
            'attendance_session_id',
            $session->id
        )
        ->where(
            'respondent_type',
            AttendanceMeetingResponse::RESPONDENT_GUEST
        )
        ->whereRaw(
            'LOWER(guest_name) = ?',
            [mb_strtolower($guestName)]
        )
        ->first();

/*
 * If optional information is not enabled during a
 * resubmission, preserve whatever profile was already
 * supplied previously.
 */
$guestProfile =
    $meetingResponse?->guest_profile;

if ($request->boolean('guest_profile_enabled')) {
    $guestProfile = collect(
        $data['guest_profile'] ?? []
    )
        ->map(
            fn ($value) =>
                is_string($value)
                    ? trim($value)
                    : $value
        )
        ->filter(
            fn ($value): bool =>
                filled($value)
        )
        ->all();

    if ($guestProfile === []) {
        $guestProfile = null;
    }
}

if (! $meetingResponse) {
    $meetingResponse =
        new AttendanceMeetingResponse();

    $meetingResponse->original_source =
        AttendanceMeetingResponse::RESPONDENT_GUEST;
}

$meetingResponse->forceFill([
    'attendance_session_id' =>
        $session->id,

    'respondent_type' =>
        AttendanceMeetingResponse::RESPONDENT_GUEST,

    'person_id' =>
        null,

    'campus_contact_id' =>
        null,

    'gospel_contact_id' =>
        null,

    'guest_name' =>
        $guestName,

    'guest_profile' =>
        $guestProfile,

    'respondent_name' =>
        $guestName,

    'submitted_form_type' =>
        AttendanceMeetingResponse::FORM_NORMAL,

    'response' =>
        $data['response'],

    'responded_at' =>
        now(),
])->save();

        return $this->savedResponseRedirect(
            $session,
            $guestName,
            $data['response'],
        );
    }

    private function existingMeetingResponseForIdentity(
        AttendanceSession $session,
        array $identity
    ): ?AttendanceMeetingResponse {
        $person =
            $identity['person']
            ?? null;

        if ($person) {
            return AttendanceMeetingResponse::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'person_id',
                    $person->id
                )
                ->first();
        }

        $selectedType =
            $identity['selected_type']
            ?? null;

        if (
            $selectedType
            === AttendanceMeetingResponse::RESPONDENT_CAMPUS
            && filled(
                $identity[
                    'campus_contact'
                ]?->id
            )
        ) {
            return AttendanceMeetingResponse::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'campus_contact_id',
                    $identity[
                        'campus_contact'
                    ]->id
                )
                ->first();
        }

        if (
            $selectedType
            === AttendanceMeetingResponse::RESPONDENT_GOSPEL
            && filled(
                $identity[
                    'gospel_contact'
                ]?->id
            )
        ) {
            return AttendanceMeetingResponse::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'gospel_contact_id',
                    $identity[
                        'gospel_contact'
                    ]->id
                )
                ->first();
        }

        return null;
    }


    private function pendingDatabaseFieldValues(
        ?AttendanceMeetingResponse $response
    ): array {
        if (! $response) {
            return [];
        }

        return $response
            ->profileCorrections()
            ->where(
                'status',
                AttendanceMeetingProfileCorrection::STATUS_PENDING
            )
            ->orderByDesc('id')
            ->get()
            /*
             * If old duplicate pending rows ever exist, the
             * newest proposal wins.
             */
            ->unique(
                'database_field'
            )
            ->mapWithKeys(
                function (
                    AttendanceMeetingProfileCorrection $change
                ): array {
                    $value =
                        $change->proposed_value_json
                        ?? $change->proposed_value_text;

                    return [
                        $change->database_field =>
                            $value,
                    ];
                }
            )
            ->all();
    }


    private function normalizeGoogleReferenceProposal(
        AttendanceMeetingFormQuestion $question,
        mixed $rawValue,
        mixed $rawProposal,
        array &$errors
    ): ?array {
        $field =
            (string)
            $question->database_field;

        if (
            ! $question->isDatabaseField()
            || ! MeetingFormDatabaseFieldRegistry
                ::allowsReferenceProposal(
                    $field
                )
            || (string) $rawValue
                !== MeetingFormDatabaseFieldRegistry
                    ::REFERENCE_PROPOSAL_VALUE
        ) {
            return null;
        }

        $proposal =
            is_array($rawProposal)
                ? $rawProposal
                : [];

        $label =
            trim(
                preg_replace(
                    '/\\s+/',
                    ' ',
                    (string)
                    ($proposal['label'] ?? '')
                )
            );

        $maxLength =
            $field === 'locality'
                ? 150
                : 255;

        if (
            mb_strlen($label) < 2
            || mb_strlen($label) > $maxLength
        ) {
            $errors[
                'reference_proposals.'
                . $question->id
                . '.label'
            ] =
                $field === 'school'
                    ? 'Enter the actual School / Campus name.'
                    : 'Enter the actual Locality name.';
        }

        $provinceName =
            trim(
                preg_replace(
                    '/\\s+/',
                    ' ',
                    (string)
                    (
                        $proposal[
                            'province_name'
                        ]
                        ?? ''
                    )
                )
            );

        if (
            mb_strlen($provinceName) < 2
            || mb_strlen($provinceName) > 150
        ) {
            $errors[
                'reference_proposals.'
                . $question->id
                . '.province_name'
            ] =
                'Enter the Province for this '
                . (
                    $field === 'school'
                        ? 'School / Campus.'
                        : 'Locality.'
                );
        }

        /*
         * If the typed Province uniquely matches an
         * existing Province, remember its ID now.
         *
         * Otherwise leave the ID null. The administrator
         * will resolve it during proposal review.
         */
        $matchingProvinces =
            $provinceName !== ''
                ? Province::query()
                    ->whereRaw(
                        'LOWER(name) = ?',
                        [
                            mb_strtolower(
                                $provinceName
                            ),
                        ]
                    )
                    ->get()
                : collect();

        $province =
            $matchingProvinces->count() === 1
                ? $matchingProvinces->first()
                : null;

        $cityMunicipality =
            $field === 'school'
                ? trim(
                    preg_replace(
                        '/\\s+/',
                        ' ',
                        (string)
                        (
                            $proposal[
                                'city_municipality'
                            ]
                            ?? ''
                        )
                    )
                )
                : '';

        if (
            mb_strlen(
                $cityMunicipality
            ) > 150
        ) {
            $errors[
                'reference_proposals.'
                . $question->id
                . '.city_municipality'
            ] =
                'City / Municipality may not exceed 150 characters.';
        }

        return [
            'label' =>
                $label,

            'province_id' =>
                $province?->id,

            'province_name' =>
                $provinceName,

            'city_municipality' =>
                $cityMunicipality !== ''
                    ? $cityMunicipality
                    : null,
        ];
    }


    private function storeGoogleForm(
        Request $request,
        AttendanceSession $session
    ): RedirectResponse {
        $data = $request->validate([
            'respondent_type' => [
                'required',
                'in:existing,guest',
            ],

            'respondent_token' => [
                'nullable',
                'string',
                'max:4096',
            ],

            'guest_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'answers' => [
                'nullable',
                'array',
            ],

            'reference_proposals' => [
                'nullable',
                'array',
            ],
        ]);

        $resolver =
            app(
                MeetingFormRespondentResolver::class
            );

        $identity = null;

        if (
            $data['respondent_type']
            === 'existing'
        ) {
            $token =
                $this->decodePublicIdentityToken(
                    $session,
                    $data[
                        'respondent_token'
                    ] ?? null
                );

            $identity =
                $resolver->resolve(
                    $token['type'],
                    $token['id']
                );

            if (! $identity) {
                throw ValidationException::withMessages([
                    'respondent_type' =>
                        'Please select your name again.',
                ]);
            }
        }

        $guestName =
            trim(
                (string)
                ($data['guest_name'] ?? '')
            );

        if (
            ! $identity
            && mb_strlen($guestName) < 2
        ) {
            throw ValidationException::withMessages([
                'guest_name' =>
                    'Please enter your full name.',
            ]);
        }

        $questions =
            $session
                ->sheet
                ->meetingFormQuestions()
                ->get();

        $rawAnswers =
            is_array(
                $data['answers'] ?? null
            )
                ? $data['answers']
                : [];

        $rawReferenceProposals =
            is_array(
                $data[
                    'reference_proposals'
                ] ?? null
            )
                ? $data[
                    'reference_proposals'
                ]
                : [];

        $errors = [];
        $normalizedAnswers = [];
        $normalizedReferenceProposals = [];

        foreach ($questions as $question) {
            /*
             * Notice blocks are display-only form items.
             *
             * They never require an answer and never create an
             * AttendanceMeetingFormAnswer row.
             */
            if ($question->isNotice()) {
                continue;
            }

            $questionId =
                (string) $question->id;

            $rawValue =
                $rawAnswers[
                    $questionId
                ]
                ?? null;

            $referenceProposal =
                $this->normalizeGoogleReferenceProposal(
                    $question,
                    $rawValue,
                    $rawReferenceProposals[
                        $questionId
                    ] ?? null,
                    $errors
                );

            if ($referenceProposal !== null) {
                $normalized =
                    null;

                $blank =
                    false;

                $normalizedReferenceProposals[
                    (int) $question->id
                ] =
                    $referenceProposal;
            } else {
                $normalized =
                    $this->normalizeGoogleFormAnswer(
                        $question,
                        $rawValue,
                        $errors
                    );

                $blank =
                    $this->googleFormAnswerIsBlank(
                        $normalized
                    );
            }

            if (
                $question->is_required
                && $blank
            ) {
                $satisfiedByExistingDatabaseValue =
                    false;

                if (
                    $identity
                    && $question->isDatabaseField()
                    && filled(
                        $question->database_field
                    )
                ) {
                    $resolved =
                        $resolver->fieldValue(
                            $identity,
                            (string)
                            $question->database_field
                        );

                    $satisfiedByExistingDatabaseValue =
                        (bool) (
                            $resolved[
                                'has_existing_value'
                            ]
                            ?? false
                        );
                }

                if (
                    ! $satisfiedByExistingDatabaseValue
                ) {
                    $errors[
                        'answers.'
                        . $question->id
                    ] =
                        $question->question_text
                        . ' is required.';
                }
            }

            if (
                ! $blank
                && $referenceProposal === null
            ) {
                $normalizedAnswers[
                    (int) $question->id
                ] = $normalized;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(
                $errors
            );
        }

        $meetingResponse =
            DB::transaction(
                function () use (
                    $session,
                    $identity,
                    $guestName,
                    $questions,
                    $normalizedAnswers,
                    $normalizedReferenceProposals,
                    $rawAnswers
                ): AttendanceMeetingResponse {
                    $response =
                        $this
                            ->googleMeetingResponse(
                                $session,
                                $identity,
                                $guestName
                            );

                    $questionIds =
                        $questions
                            ->pluck('id')
                            ->map(
                                fn ($id): int =>
                                    (int) $id
                            )
                            ->all();

                    if ($questionIds !== []) {
                        $response
                            ->formAnswers()
                            ->whereIn(
                                'attendance_meeting_form_question_id',
                                $questionIds
                            )
                            ->delete();
                    }

                    foreach (
                        $normalizedAnswers
                        as $questionId => $answer
                    ) {
                        AttendanceMeetingFormAnswer::query()
                            ->create([
                                'attendance_meeting_response_id' =>
                                    $response->id,

                                'attendance_meeting_form_question_id' =>
                                    $questionId,

                                'answer_text' =>
                                    $answer[
                                        'answer_text'
                                    ],

                                'answer_json' =>
                                    $answer[
                                        'answer_json'
                                    ],
                            ]);
                    }

                    /*
                     * Meeting answers are stored independently
                     * from canonical People / Church / Education
                     * data.
                     *
                     * Any proposed Database Field change is only
                     * placed into the pending review queue.
                     */
                    /*
                     * A "not listed" sentinel is not a real
                     * School/Locality ID and must never enter
                     * the ordinary profile-correction queue.
                     */
                    $profileRawAnswers =
                        $rawAnswers;

                    foreach (
                        array_keys(
                            $normalizedReferenceProposals
                        )
                        as $proposalQuestionId
                    ) {
                        unset(
                            $profileRawAnswers[
                                $proposalQuestionId
                            ],
                            $profileRawAnswers[
                                (string)
                                $proposalQuestionId
                            ]
                        );
                    }

                    app(
                        MeetingFormProfileCorrectionRecorder::class
                    )->record(
                        response:
                            $response,

                        identity:
                            $identity,

                        questions:
                            $questions,

                        rawAnswers:
                            $profileRawAnswers,
                    );

                    app(
                        MeetingFormReferenceProposalRecorder::class
                    )->record(
                        response:
                            $response,

                        questions:
                            $questions,

                        proposals:
                            $normalizedReferenceProposals,
                    );

                    return $response;
                }
            );

        return redirect()
            ->to(
                $request->url()
            )
            ->with(
                'meeting_google_form_saved',
                true
            )
            ->with(
                'meeting_response_name',
                $meetingResponse
                    ->respondent_name
            );
    }


    private function googleMeetingResponse(
        AttendanceSession $session,
        ?array $identity,
        string $guestName
    ): AttendanceMeetingResponse {
        if ($identity) {
            $selectedType =
                $identity[
                    'selected_type'
                ];

            $person =
                $identity['person']
                ?? null;

            if ($person) {
                $response =
                    AttendanceMeetingResponse::query()
                        ->firstOrNew([
                            'attendance_session_id' =>
                                $session->id,

                            'person_id' =>
                                $person->id,
                        ]);

                if (! $response->exists) {
                    $response->original_source =
                        $selectedType;

                    $response->response =
                        null;

                    if (
                        $selectedType
                        === AttendanceMeetingResponse::RESPONDENT_CAMPUS
                    ) {
                        $response->campus_contact_id =
                            $identity[
                                'campus_contact'
                            ]?->id;
                    }

                    if (
                        $selectedType
                        === AttendanceMeetingResponse::RESPONDENT_GOSPEL
                    ) {
                        $response->gospel_contact_id =
                            $identity[
                                'gospel_contact'
                            ]?->id;
                    }
                }

                $response->forceFill([
                    'respondent_type' =>
                        AttendanceMeetingResponse::RESPONDENT_PERSON,

                    'person_id' =>
                        $person->id,

                    'guest_name' =>
                        null,

                    'respondent_name' =>
                        $person->display_name,

                    'submitted_form_type' =>
                        AttendanceMeetingResponse::FORM_GOOGLE,

                    'response' =>
                        null,

                    'responded_at' =>
                        now(),
                ])->save();

                return $response;
            }

            if (
                $selectedType
                === AttendanceMeetingResponse::RESPONDENT_CAMPUS
            ) {
                $contact =
                    $identity[
                        'campus_contact'
                    ];

                $response =
                    AttendanceMeetingResponse::query()
                        ->firstOrNew([
                            'attendance_session_id' =>
                                $session->id,

                            'campus_contact_id' =>
                                $contact->id,
                        ]);

                if (! $response->exists) {
                    $response->original_source =
                        AttendanceMeetingResponse::RESPONDENT_CAMPUS;

                    $response->response =
                        null;
                }

                $response->forceFill([
                    'respondent_type' =>
                        AttendanceMeetingResponse::RESPONDENT_CAMPUS,

                    'person_id' =>
                        null,

                    'campus_contact_id' =>
                        $contact->id,

                    'gospel_contact_id' =>
                        null,

                    'guest_name' =>
                        null,

                    'respondent_name' =>
                        $contact->display_name,

                    'submitted_form_type' =>
                        AttendanceMeetingResponse::FORM_GOOGLE,

                    'response' =>
                        null,

                    'responded_at' =>
                        now(),
                ])->save();

                return $response;
            }

            if (
                $selectedType
                === AttendanceMeetingResponse::RESPONDENT_GOSPEL
            ) {
                $contact =
                    $identity[
                        'gospel_contact'
                    ];

                $response =
                    AttendanceMeetingResponse::query()
                        ->firstOrNew([
                            'attendance_session_id' =>
                                $session->id,

                            'gospel_contact_id' =>
                                $contact->id,
                        ]);

                if (! $response->exists) {
                    $response->original_source =
                        AttendanceMeetingResponse::RESPONDENT_GOSPEL;

                    $response->response =
                        null;
                }

                $response->forceFill([
                    'respondent_type' =>
                        AttendanceMeetingResponse::RESPONDENT_GOSPEL,

                    'person_id' =>
                        null,

                    'campus_contact_id' =>
                        null,

                    'gospel_contact_id' =>
                        $contact->id,

                    'guest_name' =>
                        null,

                    'respondent_name' =>
                        $contact->display_name,

                    'submitted_form_type' =>
                        AttendanceMeetingResponse::FORM_GOOGLE,

                    'response' =>
                        null,

                    'responded_at' =>
                        now(),
                ])->save();

                return $response;
            }
        }

        $response =
            AttendanceMeetingResponse::query()
                ->where(
                    'attendance_session_id',
                    $session->id
                )
                ->where(
                    'respondent_type',
                    AttendanceMeetingResponse::RESPONDENT_GUEST
                )
                ->whereRaw(
                    'LOWER(guest_name) = ?',
                    [
                        mb_strtolower(
                            $guestName
                        ),
                    ]
                )
                ->first();

        if (! $response) {
            $response =
                new AttendanceMeetingResponse();

            $response->attendance_session_id =
                $session->id;

            $response->original_source =
                AttendanceMeetingResponse::RESPONDENT_GUEST;

            $response->response =
                null;
        }

        $response->forceFill([
            'respondent_type' =>
                AttendanceMeetingResponse::RESPONDENT_GUEST,

            'person_id' =>
                null,

            'campus_contact_id' =>
                null,

            'gospel_contact_id' =>
                null,

            'guest_name' =>
                $guestName,

            'respondent_name' =>
                $guestName,

            'submitted_form_type' =>
                AttendanceMeetingResponse::FORM_GOOGLE,

            'response' =>
                null,

            'responded_at' =>
                now(),
        ])->save();

        return $response;
    }


    private function normalizeGoogleFormAnswer(
        AttendanceMeetingFormQuestion $question,
        mixed $value,
        array &$errors
    ): array {
        $fieldKey =
            'answers.'
            . $question->id;

        if (
            $question->question_type
            === AttendanceMeetingFormQuestion::TYPE_CHECKBOXES
        ) {
            $values =
                is_array($value)
                    ? collect($value)
                        ->map(
                            fn ($item): string =>
                                trim(
                                    (string) $item
                                )
                        )
                        ->filter()
                        ->unique()
                        ->values()
                        ->all()
                    : [];

            $allowed =
                array_values(
                    $question->options
                    ?? []
                );

            foreach ($values as $selected) {
                if (
                    ! in_array(
                        $selected,
                        $allowed,
                        true
                    )
                ) {
                    $errors[$fieldKey] =
                        'One of the selected choices is invalid.';

                    break;
                }
            }

            return [
                'answer_text' =>
                    null,

                'answer_json' =>
                    $values,
            ];
        }

        if (is_array($value)) {
            $errors[$fieldKey] =
                'This answer is invalid.';

            return [
                'answer_text' =>
                    null,

                'answer_json' =>
                    null,
            ];
        }

        $value =
            trim(
                (string)
                ($value ?? '')
            );

        if (
            in_array(
                $question->question_type,
                [
                    AttendanceMeetingFormQuestion::TYPE_MULTIPLE_CHOICE,
                    AttendanceMeetingFormQuestion::TYPE_DROPDOWN,
                ],
                true
            )
            && $value !== ''
            && ! in_array(
                $value,
                array_values(
                    $question->options
                    ?? []
                ),
                true
            )
        ) {
            $errors[$fieldKey] =
                'The selected choice is invalid.';
        }

        if (
            $question->question_type
            === AttendanceMeetingFormQuestion::TYPE_DATABASE_FIELD
            && $value !== ''
        ) {
            $definition =
                MeetingFormDatabaseFieldRegistry::definition(
                    $question->database_field
                );

            $input =
                $definition['input']
                ?? 'text';

            if (
                $input === 'date'
                && ! preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $value
                )
            ) {
                $errors[$fieldKey] =
                    'Enter a valid date.';
            }

            if (
                $input === 'locality'
                && (
                    ! ctype_digit($value)
                    || ! LocalityOptions::activeConfiguredLocality(
                        (int) $value
                    )
                )
            ) {
                $errors[$fieldKey] =
                    'Select a valid Locality.';
            }

            if (
                $input === 'school'
                && (
                    ! ctype_digit($value)
                    || ! School::query()
                        ->whereKey(
                            (int) $value
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->exists()
                )
            ) {
                $errors[$fieldKey] =
                    'Select a valid School / Campus.';
            }

            if (
                $input === 'email'
                && ! filter_var(
                    $value,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $errors[$fieldKey] =
                    'Enter a valid email address.';
            }
        }

        $maxLength =
            $question->question_type
                === AttendanceMeetingFormQuestion::TYPE_PARAGRAPH
                ? 20000
                : 5000;

        if (
            mb_strlen($value)
            > $maxLength
        ) {
            $errors[$fieldKey] =
                'This answer is too long.';
        }

        return [
            'answer_text' =>
                $value !== ''
                    ? $value
                    : null,

            'answer_json' =>
                null,
        ];
    }


    private function googleFormAnswerIsBlank(
        array $answer
    ): bool {
        if (
            is_array(
                $answer['answer_json']
                ?? null
            )
        ) {
            return collect(
                $answer['answer_json']
            )
                ->filter(
                    fn ($item): bool =>
                        filled($item)
                )
                ->isEmpty();
        }

        return blank(
            $answer['answer_text']
            ?? null
        );
    }


    private function savedResponseRedirect(
        AttendanceSession $session,
        string $name,
        string $response
    ): RedirectResponse {
        /*
         * Preserve whichever public identity the visitor used.
         *
         * Session URL:
         *   /9-10-26-example
         *
         * Permanent Meeting Series URL:
         *   /campus-meeting-sc-lucban
         *
         * A submission through the permanent link must remain
         * on the permanent link.
         */
        $publicSlug =
            trim(
                (string)
                request()->route(
                    'slug',
                    $session->public_slug
                )
            );

        if ($publicSlug === '') {
            $publicSlug =
                (string)
                $session->public_slug;
        }

        $url =
            request()->getHost()
                === 'm.overcomers.win'
                    ? 'https://m.overcomers.win/'
                        . $publicSlug
                    : secure_url(
                        '/meeting/'
                        . $publicSlug
                    );

return redirect()
    ->to($url)
            ->with(
                'meeting_response_saved',
                true
            )
            ->with(
                'meeting_response_name',
                $name
            )
            ->with(
                'meeting_response_value',
                $response
            );
    }

    private function publicIdentityToken(
        AttendanceSession $session,
        string $type,
        int $id
    ): string {
        return Crypt::encryptString(
            json_encode(
                [
                    'session_id' =>
                        (int) $session->id,

                    'type' =>
                        $type,

                    'id' =>
                        $id,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    private function decodePublicIdentityToken(
        AttendanceSession $session,
        ?string $token
    ): array {
        if (blank($token)) {
            throw ValidationException::withMessages([
                'respondent_type' =>
                    'Please select your name again.',
            ]);
        }

        try {
            $payload =
                json_decode(
                    Crypt::decryptString(
                        $token
                    ),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
        } catch (
            DecryptException
            | \JsonException
        ) {
            throw ValidationException::withMessages([
                'respondent_type' =>
                    'Your name selection is invalid or expired. '
                    . 'Please select your name again.',
            ]);
        }

        $type =
            $payload['type']
            ?? null;

        $id =
            $payload['id']
            ?? null;

        $sessionId =
            $payload['session_id']
            ?? null;

        if (
            (int) $sessionId
                !== (int) $session->id
            || ! in_array(
                $type,
                [
                    AttendanceMeetingResponse::RESPONDENT_PERSON,
                    AttendanceMeetingResponse::RESPONDENT_CAMPUS,
                    AttendanceMeetingResponse::RESPONDENT_GOSPEL,
                ],
                true
            )
            || ! is_numeric($id)
            || (int) $id < 1
        ) {
            throw ValidationException::withMessages([
                'respondent_type' =>
                    'Your name selection is invalid. '
                    . 'Please select your name again.',
            ]);
        }

        return [
            'type' =>
                $type,

            'id' =>
                (int) $id,
        ];
    }

    private function publicSession(
        string $slug
    ): AttendanceSession {
        $slug =
            trim($slug);

        /*
         * Session slugs and permanent Meeting Series slugs
         * intentionally share one public namespace:
         *
         * m.overcomers.win/{slug}
         *
         * Never silently choose one if bad data creates a
         * cross-table collision.
         */
        $session =
            AttendanceSession::query()
                ->where(
                    'public_slug',
                    $slug
                )
                ->with('sheet')
                ->first();

        $series =
            AttendanceMeetingSeries::query()
                ->where(
                    'public_slug',
                    $slug
                )
                ->first();

        if ($session && $series) {
            abort(
                409,
                'This public meeting link is ambiguous. '
                . 'Please contact the meeting administrator.'
            );
        }

        /*
         * Existing exact Session links retain their original
         * behavior and can identify a historical occurrence.
         */
        if ($session) {
            abort_unless(
                $session->sheet
                    && $session->sheet->is_active
                    && $session
                        ->sheet
                        ->meetingFormEnabled(),
                404
            );

            return $session;
        }

        if (! $series) {
            abort(404);
        }

        abort_unless(
            $series->is_active,
            404
        );

        return $this->resolveSeriesSession(
            $series
        );
    }

    private function resolveSeriesSession(
        AttendanceMeetingSeries $series
    ): AttendanceSession {
        $today =
            CarbonImmutable::today()
                ->toDateString();

        /*
         * A Sheet may represent:
         *
         * - Semester 1
         * - Semester 2
         * - another academic year
         *
         * They can all belong to one permanent Meeting Series.
         *
         * Only active Sheets with an enabled meeting form
         * participate in public resolution.
         */
        $baseQuery =
            AttendanceSession::query()
                ->whereHas(
                    'sheet',
                    function ($query) use (
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
                            ->whereIn(
                                'meeting_form_type',
                                [
                                    AttendanceSheet::MEETING_FORM_NORMAL,
                                    AttendanceSheet::MEETING_FORM_GOOGLE,
                                ]
                            );
                    }
                )
                ->with('sheet');

        /*
         * Rule 1:
         * If the series has a Session today, use today.
         *
         * We intentionally do not switch to next week merely
         * because the meeting time has passed. The whole
         * calendar day belongs to today's meeting.
         */
        $todaySessions =
            (clone $baseQuery)
                ->where(
                    'session_date',
                    $today
                )
                ->orderBy('id')
                ->get();

        if ($todaySessions->count() > 1) {
            $this->abortAmbiguousSeriesDate(
                $series,
                $today
            );
        }

        if ($todaySessions->count() === 1) {
            return $todaySessions->first();
        }

        /*
         * Rule 2:
         * Otherwise choose the nearest future Session across
         * every linked Semester / Academic-Year Sheet.
         */
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
            abort(
                404,
                'No upcoming meeting is currently scheduled '
                . 'for this permanent meeting link.'
            );
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
                ->orderBy('id')
                ->get();

        if ($nextSessions->count() > 1) {
            $this->abortAmbiguousSeriesDate(
                $series,
                $nextDate
            );
        }

        $session =
            $nextSessions->first();

        if (! $session) {
            abort(404);
        }

        return $session;
    }

    private function abortAmbiguousSeriesDate(
        AttendanceMeetingSeries $series,
        string $date
    ): never {
        $label =
            CarbonImmutable::parse(
                $date
            )->format(
                'F j, Y'
            );

        abort(
            409,
            'The permanent meeting "'
            . $series->name
            . '" has multiple active Sessions scheduled for '
            . $label
            . '. Please contact the meeting administrator.'
        );
    }

}