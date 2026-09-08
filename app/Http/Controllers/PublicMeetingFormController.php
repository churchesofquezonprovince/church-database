<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceSession;
use App\Models\CampusContact;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        if (mb_strlen($query) < 2) {
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
            ->limit(15)
            ->get()
            ->map(
                fn (Person $person): array => [
                    'type' =>
                        AttendanceMeetingResponse::RESPONDENT_PERSON,

                    'id' =>
                        (int) $person->id,

                    'name' =>
                        $person->display_name,

                    'source' =>
                        'People Database',
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
            ->limit(15)
            ->get()
            ->map(
                fn (CampusContact $contact): array => [
                    'type' =>
                        AttendanceMeetingResponse::RESPONDENT_CAMPUS,

                    'id' =>
                        (int) $contact->id,

                    'name' =>
                        $contact->display_name,

                    'source' =>
                        'Campus Database',
                ]
            );

        /*
         * Merge the two sources and return only the best
         * 20 display results.
         */
        $results = $people
            ->concat($campus)
            ->sortBy(
                fn (array $row): string =>
                    mb_strtolower($row['name'])
            )
            ->take(20)
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
                'in:person,campus,guest',
            ],

            'respondent_id' => [
                'nullable',
                'integer',
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

        $type = $data['respondent_type'];

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
$url =
    request()->getHost() === 'm.overcomers.win'
        ? 'https://m.overcomers.win/'
            . $session->public_slug
        : secure_url(
            '/meeting/'
            . $session->public_slug
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

    private function publicSession(
        string $slug
    ): AttendanceSession {
        $session = AttendanceSession::query()
            ->where('public_slug', $slug)
            ->with('sheet')
            ->firstOrFail();

        abort_unless(
            $session->sheet
                && $session->sheet->is_active
                && $session->sheet->meetingFormEnabled(),
            404
        );

        return $session;
    }
}