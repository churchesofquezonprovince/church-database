<?php

namespace App\Http\Controllers;


use App\Models\Person;
use App\Models\AttendanceMeetingResponse;
use App\Models\CampusContact;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceMeetingResponsePromotionController extends Controller
{

public function createGuestPerson(
    Request $request,
    AttendanceMeetingResponse $response
): RedirectResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    if (
        $response->respondent_type
        !== AttendanceMeetingResponse::RESPONDENT_GUEST
    ) {
        return back()->withErrors([
            'meeting_response_person_create' =>
                'This response is no longer a Guest RSVP.',
        ]);
    }

    $data = $request->validate([
        'create_person_response_id' => [
            'required',
            'integer',
        ],

        'person_firstname' => [
            'required',
            'string',
            'max:100',
        ],

        'person_lastname' => [
            'required',
            'string',
            'max:100',
        ],

'person_sex' => [
    'required',
    'in:Male,Female',
],

'person_locality' => [
    'required',
    'string',
    'max:150',
],

        'person_school_campus' => [
            'nullable',
            'string',
            'max:255',
        ],

        'person_course_strand' => [
            'nullable',
            'string',
            'max:255',
        ],

        'person_grade_level' => [
            'nullable',
            'string',
            'max:100',
        ],

        'person_contact_number' => [
            'nullable',
            'string',
            'max:20',
        ],

        'person_email' => [
            'nullable',
            'email',
            'max:255',
        ],

        'person_facebook_account' => [
            'nullable',
            'string',
            'max:255',
        ],

        'create_person_anyway' => [
            'nullable',
            'boolean',
        ],
    ]);

    if (
        (int) $data['create_person_response_id']
        !== (int) $response->id
    ) {
        abort(404);
    }

    $normalized = [
        'firstname' =>
            $this->nullIfBlank(
                $data['person_firstname'] ?? null
            ),

        'lastname' =>
            $this->nullIfBlank(
                $data['person_lastname'] ?? null
            ),

        'sex' =>
            $this->nullIfBlank(
                $data['person_sex'] ?? null
            ),

        'locality' =>
            $this->nullIfBlank(
                $data['person_locality'] ?? null
            ),

        'school_campus' =>
            $this->nullIfBlank(
                $data['person_school_campus'] ?? null
            ),

        'course_strand' =>
            $this->nullIfBlank(
                $data['person_course_strand'] ?? null
            ),

        'grade_level' =>
            $this->nullIfBlank(
                $data['person_grade_level'] ?? null
            ),

        'contact_number' =>
            $this->nullIfBlank(
                $data['person_contact_number'] ?? null
            ),

        'email' =>
            $this->nullIfBlank(
                $data['person_email'] ?? null
            ),

        'facebook_account' =>
            $this->nullIfBlank(
                $data['person_facebook_account'] ?? null
            ),
    ];

    /*
     * Check the People Database first.
     *
     * Do not automatically create a duplicate Person when
     * the same First + Last name already exists.
     */
    if (! $request->boolean('create_person_anyway')) {
        $matches = $this->possiblePersonMatches(
            $normalized
        );

        if ($matches->isNotEmpty()) {
            $names = $matches
                ->take(5)
                ->map(function (Person $person): string {
                    $details = collect([
                        $person->display_name,
                        $person->locality,
                    ])
                        ->filter()
                        ->implode(' · ');

                    return $details;
                })
                ->implode(', ');

            return back()
                ->withInput()
                ->withErrors([
                    'meeting_response_person_create' =>
                        'Possible Person already exists: '
                        . $names
                        . '. Use "Link to Existing Person" above, '
                        . 'or check "Create anyway" if this is a different person.',
                ]);
        }
    }

    $oldValues = [
        'respondent_type' =>
            $response->respondent_type,

        'original_source' =>
            $response->original_source,

        'person_id' =>
            $response->person_id,

        'campus_contact_id' =>
            $response->campus_contact_id,

        'guest_name' =>
            $response->guest_name,

        'respondent_name' =>
            $response->respondent_name,
    ];

    $person = DB::transaction(
        function () use (
            $response,
            $normalized
        ): Person {
            $person = new Person();

            $person->firstname =
                $normalized['firstname'];

            $person->lastname =
                $normalized['lastname'];

            $person->sex =
                $normalized['sex'];

            $person->locality =
                $normalized['locality'];

            $person->contact_number =
                $normalized['contact_number'];

            $person->email =
                $normalized['email'];

            $person->facebook_account =
                $normalized['facebook_account'];

            $person->save();

            /*
             * Guest promoted directly into People:
             * treat as Gospel Friend by default.
             */
            $churchProfile = $person
                ->churchProfile()
                ->firstOrNew([]);

            $churchProfile->status =
                'Gospel Friend';

            $churchProfile->save();

            /*
             * Preserve optional school information in the
             * Person's Education Profile.
             */
            if (
                filled($normalized['school_campus'])
                || filled($normalized['course_strand'])
                || filled($normalized['grade_level'])
            ) {
                $education = $person
                    ->educationProfile()
                    ->firstOrNew([]);

                $education->school_workplace =
                    $normalized['school_campus'];

                $education->course_strand =
                    $normalized['course_strand'];

                $education->grade_level =
                    $normalized['grade_level'];

                $education->save();
            }

            /*
             * Promote the RSVP's current identity.
             *
             * Preserve:
             * - original_source
             * - guest_name
             * - guest_profile
             */
            $response->forceFill([
                'respondent_type' =>
                    AttendanceMeetingResponse::RESPONDENT_PERSON,

                'person_id' =>
                    $person->id,

                'campus_contact_id' =>
                    null,

                'respondent_name' =>
                    $person->display_name,
            ])->save();

            return $person;
        }
    );

    ActivityLogger::log(
        action:
            'attendance_meeting_response.created_person',

        subject:
            $response,

        description:
            'Created Person from Guest meeting response.',

        oldValues:
            $oldValues,

        newValues: [
            'respondent_type' =>
                $response->respondent_type,

            'original_source' =>
                $response->original_source,

            'person_id' =>
                $response->person_id,

            'campus_contact_id' =>
                $response->campus_contact_id,

            'guest_name' =>
                $response->guest_name,

            'respondent_name' =>
                $response->respondent_name,

            'person_status' =>
                'Gospel Friend',
        ],
    );

    return back()
        ->with(
            'meeting_response_person_created',
            true
        )
        ->with(
            'meeting_response_created_person_name',
            $person->display_name
        );
}

public function linkGuestToPerson(
    Request $request,
    AttendanceMeetingResponse $response
): RedirectResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    if (
        $response->respondent_type
        !== AttendanceMeetingResponse::RESPONDENT_GUEST
    ) {
        return back()->withErrors([
            'meeting_response_person_link' =>
                'This response is no longer a Guest RSVP.',
        ]);
    }

    $data = $request->validate([
        'link_response_id' => [
            'required',
            'integer',
        ],

        'person_id' => [
            'required',
            'integer',
            'exists:persons,id',
        ],
    ]);

    if (
        (int) $data['link_response_id']
        !== (int) $response->id
    ) {
        abort(404);
    }

    $person = Person::query()
        ->findOrFail(
            (int) $data['person_id']
        );

    /*
     * Do not silently merge two independently submitted
     * responses.
     */
    $existingResponse =
        AttendanceMeetingResponse::query()
            ->where(
                'attendance_session_id',
                $response->attendance_session_id
            )
            ->where(
                'person_id',
                $person->id
            )
            ->where(
                'id',
                '!=',
                $response->id
            )
            ->first();

    if ($existingResponse) {
        return back()
            ->withInput()
            ->withErrors([
                'meeting_response_person_link' =>
                    $person->display_name
                    . ' already has a response for this meeting. '
                    . 'The Guest response was not changed.',
            ]);
    }

    $oldValues = [
        'respondent_type' =>
            $response->respondent_type,

        'original_source' =>
            $response->original_source,

        'person_id' =>
            $response->person_id,

        'campus_contact_id' =>
            $response->campus_contact_id,

        'guest_name' =>
            $response->guest_name,

        'respondent_name' =>
            $response->respondent_name,
    ];

    DB::transaction(
        function () use (
            $response,
            $person
        ): void {
            /*
             * Keep guest_name and guest_profile for history.
             *
             * original_source also remains "guest".
             */
            $response->forceFill([
                'respondent_type' =>
                    AttendanceMeetingResponse::RESPONDENT_PERSON,

                'person_id' =>
                    $person->id,

                'campus_contact_id' =>
                    null,

                'respondent_name' =>
                    $person->display_name,
            ])->save();
        }
    );

    ActivityLogger::log(
        action:
            'attendance_meeting_response.linked_to_person',

        subject:
            $response,

        description:
            'Linked Guest meeting response to existing Person.',

        oldValues:
            $oldValues,

        newValues: [
            'respondent_type' =>
                $response->respondent_type,

            'original_source' =>
                $response->original_source,

            'person_id' =>
                $response->person_id,

            'campus_contact_id' =>
                $response->campus_contact_id,

            'guest_name' =>
                $response->guest_name,

            'respondent_name' =>
                $response->respondent_name,
        ],
    );

    return back()
        ->with(
            'meeting_response_linked_to_person',
            true
        )
        ->with(
            'meeting_response_linked_person_name',
            $person->display_name
        );
}

public function searchPeople(
    Request $request
): \Illuminate\Http\JsonResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    $search = trim(
        (string) $request->query('q', '')
    );

    if (mb_strlen($search) < 2) {
        return response()->json([
            'results' => [],
        ]);
    }

    $like = '%' . $search . '%';

    $people = Person::query()
        ->where(function ($query) use ($like): void {
            $query
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
        ->limit(20)
        ->get()
        ->map(
            fn (Person $person): array => [
                'id' =>
                    (int) $person->id,

                'name' =>
                    $person->display_name,

                'locality' =>
                    $person->locality,
            ]
        )
        ->values();

    return response()->json([
        'results' => $people,
    ]);
}

    public function promoteGuestToCampus(
        Request $request,
        AttendanceMeetingResponse $response
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        if (
            $response->respondent_type
            !== AttendanceMeetingResponse::RESPONDENT_GUEST
        ) {
            return back()->withErrors([
                'meeting_response_promotion' =>
                    'This response is no longer a Guest RSVP.',
            ]);
        }

        $data = $request->validate([
            'promotion_response_id' => [
                'required',
                'integer',
            ],

            'firstname' => [
                'nullable',
                'string',
                'max:100',
            ],

            'lastname' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sex' => [
                'nullable',
                'in:Male,Female',
            ],

            'locality' => [
                'nullable',
                'string',
                'max:150',
            ],

            'school_campus' => [
                'nullable',
                'string',
                'max:255',
            ],

            'course_strand' => [
                'nullable',
                'string',
                'max:255',
            ],

            'grade_level' => [
                'nullable',
                'string',
                'max:100',
            ],

            'contact_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'facebook_account' => [
                'nullable',
                'string',
                'max:255',
            ],

            'create_anyway' => [
                'nullable',
                'boolean',
            ],
        ]);

        if (
            (int) $data['promotion_response_id']
            !== (int) $response->id
        ) {
            abort(404);
        }

        $normalized = [
            'firstname' =>
                $this->nullIfBlank(
                    $data['firstname'] ?? null
                ),

            'lastname' =>
                $this->nullIfBlank(
                    $data['lastname'] ?? null
                ),

            'sex' =>
                $this->nullIfBlank(
                    $data['sex'] ?? null
                ),

            'locality' =>
                $this->nullIfBlank(
                    $data['locality'] ?? null
                ),

            'school_campus' =>
                $this->nullIfBlank(
                    $data['school_campus'] ?? null
                ),

            'course_strand' =>
                $this->nullIfBlank(
                    $data['course_strand'] ?? null
                ),

            'grade_level' =>
                $this->nullIfBlank(
                    $data['grade_level'] ?? null
                ),

            'contact_number' =>
                $this->nullIfBlank(
                    $data['contact_number'] ?? null
                ),

            'email' =>
                $this->nullIfBlank(
                    $data['email'] ?? null
                ),

            'facebook_account' =>
                $this->nullIfBlank(
                    $data['facebook_account'] ?? null
                ),
        ];

        /*
         * The public Guest form does not require structured
         * First/Last Name.
         *
         * At promotion time, however, require at least one
         * structured name so we do not create an unusable
         * "Unnamed contact" Campus record.
         */
        if (
            blank($normalized['firstname'])
            && blank($normalized['lastname'])
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'meeting_response_promotion' =>
                        'Enter at least a First Name or Last Name before adding this Guest to the Campus Database.',
                ]);
        }

        /*
         * Use the same general duplicate philosophy as the
         * Campus Contact page: same first name is enough to
         * warn the administrator.
         */
        if (! $request->boolean('create_anyway')) {
            $matches = $this->possibleCampusMatches(
                $normalized
            );

            if ($matches->isNotEmpty()) {
                $names = $matches
                    ->take(5)
                    ->pluck('display_name')
                    ->implode(', ');

                return back()
                    ->withInput()
                    ->withErrors([
                        'meeting_response_promotion' =>
                            'Possible Campus Contact already exists: '
                            . $names
                            . '. Review the match first, or check "Create anyway" if this is a different person.',
                    ]);
            }
        }

        $oldValues = [
            'respondent_type' =>
                $response->respondent_type,

            'original_source' =>
                $response->original_source,

            'person_id' =>
                $response->person_id,

            'campus_contact_id' =>
                $response->campus_contact_id,

            'guest_name' =>
                $response->guest_name,

            'respondent_name' =>
                $response->respondent_name,
        ];

        $contact = DB::transaction(
            function () use (
                $response,
                $normalized
            ): CampusContact {
                $contact = CampusContact::query()
                    ->create($normalized);

                /*
                 * Preserve guest_name and guest_profile.
                 *
                 * They tell us how this RSVP originally entered
                 * the system.
                 */
                $response->forceFill([
                    'respondent_type' =>
                        AttendanceMeetingResponse::RESPONDENT_CAMPUS,

                    'person_id' =>
                        null,

                    'campus_contact_id' =>
                        $contact->id,

                    'respondent_name' =>
                        $contact->display_name,
                ])->save();

                return $contact;
            }
        );

        ActivityLogger::log(
            action:
                'attendance_meeting_response.promoted_to_campus',

            subject:
                $response,

            description:
                'Promoted Guest meeting response to Campus Contact.',

            oldValues:
                $oldValues,

            newValues: [
                'respondent_type' =>
                    $response->respondent_type,

                'original_source' =>
                    $response->original_source,

                'person_id' =>
                    $response->person_id,

                'campus_contact_id' =>
                    $response->campus_contact_id,

                'campus_contact_name' =>
                    $contact->display_name,

                'guest_name' =>
                    $response->guest_name,

                'respondent_name' =>
                    $response->respondent_name,
            ],
        );

        return back()
            ->with(
                'meeting_response_promoted_to_campus',
                true
            )
            ->with(
                'meeting_response_promoted_name',
                $contact->display_name
            );
    }

private function possiblePersonMatches(
    array $data
): Collection {
    $firstname = $this->nullIfBlank(
        $data['firstname'] ?? null
    );

    $lastname = $this->nullIfBlank(
        $data['lastname'] ?? null
    );

    if (
        blank($firstname)
        || blank($lastname)
    ) {
        return collect();
    }

    return Person::query()
        ->whereRaw(
            'LOWER(firstname) = ?',
            [
                mb_strtolower(
                    trim((string) $firstname)
                ),
            ]
        )
        ->whereRaw(
            'LOWER(lastname) = ?',
            [
                mb_strtolower(
                    trim((string) $lastname)
                ),
            ]
        )
        ->orderBy('lastname')
        ->orderBy('firstname')
        ->limit(10)
        ->get();
}

    private function possibleCampusMatches(
        array $data
    ): Collection {
        $firstname = $this->nullIfBlank(
            $data['firstname'] ?? null
        );

        if (blank($firstname)) {
            return collect();
        }

        $firstnameKey = mb_strtolower(
            trim((string) $firstname)
        );

        return CampusContact::query()
            ->with('person')
            ->where(function ($query) use (
                $firstnameKey
            ): void {
                $query
                    ->whereRaw(
                        'LOWER(firstname) = ?',
                        [$firstnameKey]
                    )
                    ->orWhereHas(
                        'person',
                        function ($personQuery) use (
                            $firstnameKey
                        ): void {
                            $personQuery->whereRaw(
                                'LOWER(firstname) = ?',
                                [$firstnameKey]
                            );
                        }
                    );
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(10)
            ->get();
    }

    private function nullIfBlank(
        mixed $value
    ): mixed {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
