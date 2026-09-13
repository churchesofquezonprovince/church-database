<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceMeetingSeries;
use App\Models\AttendanceSession;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Person;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicMeetingFormController extends Controller
{
    public function show(string $slug): View
    {
        $session = $this->publicSession($slug);

        return view('meeting.show', [
            'session' => $session,
            'sheet' => $session->sheet,

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

    public function store(
        Request $request,
        string $slug
    ): RedirectResponse {
        $session = $this->publicSession($slug);

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
                            ->where(
                                'meeting_form_type',
                                'normal'
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