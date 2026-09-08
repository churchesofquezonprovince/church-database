<?php

namespace App\Http\Controllers;


use App\Models\Person;
use App\Models\AttendanceMeetingResponse;
use App\Models\CampusContact;
use App\Models\Locality;
use App\Support\ActivityLogger;
use App\Support\LocalityOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceMeetingResponsePromotionController extends Controller
{

private function assignCampusResponseToPerson(
    AttendanceMeetingResponse $response,
    CampusContact $contact,
    Person $person
): void {
    $response->forceFill([
        'respondent_type' =>
            AttendanceMeetingResponse::RESPONDENT_PERSON,

        'person_id' =>
            $person->id,

        /*
         * Preserve the Campus Contact so the identity journey
         * remains Guest -> Campus -> Person when applicable.
         */
        'campus_contact_id' =>
            $contact->id,

        'respondent_name' =>
            $person->display_name,
    ])->save();
}

private function possibleCampusPersonMatches(
    array $data
): Collection {
    return Person::query()
        ->whereRaw(
            'LOWER(firstname) = ?',
            [
                mb_strtolower(
                    trim(
                        (string)
                        $data['firstname']
                    )
                ),
            ]
        )
        ->whereRaw(
            'LOWER(lastname) = ?',
            [
                mb_strtolower(
                    trim(
                        (string)
                        $data['lastname']
                    )
                ),
            ]
        )
        ->when(
            filled($data['sex'] ?? null),
            fn ($query) =>
                $query->where(
                    'sex',
                    $data['sex']
                )
        )
        ->when(
            filled($data['locality_id'] ?? null),
            fn ($query) =>
                $query->where(
                    'locality_id',
                    $data['locality_id']
                )
        )
        ->orderBy('lastname')
        ->orderBy('firstname')
        ->limit(10)
        ->get();
}

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
                'This response is no longer a Guest pre-listed entry.',
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

'person_locality_id' => [
    'required',
    'integer',
    'exists:localities,id',
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

    $locality = $this->configuredLocality(
        $data['person_locality_id'] ?? null,
        'person_locality_id'
    );

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

        'locality_id' =>
            $locality?->id,

        'locality' =>
            $locality?->name,

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

            $person->locality_id =
                $normalized['locality_id'];

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
             * Promote the pre-listed entry's current identity.
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
                'This response is no longer a Guest pre-listed entry.',
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
                    'This response is no longer a Guest pre-listed entry.',
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

            'locality_id' => [
                'nullable',
                'integer',
                'exists:localities,id',
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

        $locality = $this->configuredLocality(
            $data['locality_id'] ?? null,
            'locality_id'
        );

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

            'locality_id' =>
                $locality?->id,

            'locality' =>
                $locality?->name,

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
                 * They tell us how this pre-listed entry originally entered
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


public function linkCampusToPerson(
    Request $request,
    AttendanceMeetingResponse $response
): RedirectResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    if (
        $response->respondent_type
        !== AttendanceMeetingResponse::RESPONDENT_CAMPUS
    ) {
        return back()->withErrors([
            'meeting_response_campus_person_link' =>
                'This response is no longer a Campus pre-listed entry.',
        ]);
    }

    $data = $request->validate([
        'link_campus_response_id' => [
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
        (int) $data['link_campus_response_id']
        !== (int) $response->id
    ) {
        abort(404);
    }

    $contact = $response
        ->campusContact()
        ->first();

    if (! $contact) {
        return back()->withErrors([
            'meeting_response_campus_person_link' =>
                'The Campus Contact for this pre-listed entry could not be found.',
        ]);
    }

    $person = Person::query()
        ->with([
            'churchProfile',
            'educationProfile',
        ])
        ->findOrFail(
            (int) $data['person_id']
        );

    /*
     * If this Campus Contact was linked somewhere else after
     * the pre-listed entry was submitted, do not silently replace it.
     */
    if (
        $contact->person_id
        && (int) $contact->person_id !== (int) $person->id
    ) {
        return back()->withErrors([
            'meeting_response_campus_person_link' =>
                'This Campus Contact is already linked to another Person.',
        ]);
    }

    /*
     * One Person may only have one Campus Contact.
     */
    $otherCampusContact =
        CampusContact::query()
            ->where(
                'person_id',
                $person->id
            )
            ->where(
                'id',
                '!=',
                $contact->id
            )
            ->first();

    if ($otherCampusContact) {
        return back()
            ->withInput()
            ->withErrors([
                'meeting_response_campus_person_link' =>
                    $person->display_name
                    . ' is already linked to another Campus Contact. '
                    . 'This pre-listed entry was not changed.',
            ]);
    }

    /*
     * Never silently merge two independently submitted RSVPs.
     */
    $existingResponse =
        $this->personResponseConflict(
            $response,
            $person
        );

    if ($existingResponse) {
        return back()
            ->withInput()
            ->withErrors([
                'meeting_response_campus_person_link' =>
                    $person->display_name
                    . ' already has a pre-listed response for this meeting. '
                    . 'This Campus response was not changed.',
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

        'respondent_name' =>
            $response->respondent_name,
    ];

    DB::transaction(
        function () use (
            $response,
            $contact,
            $person
        ): void {
            /*
             * Use the same rule as the Campus Database:
             * fill only blank Person fields from Campus.
             */
            $person->firstname =
                $person->firstname
                    ?: $contact->firstname;

            $person->lastname =
                $person->lastname
                    ?: $contact->lastname;

            $person->sex =
                $person->sex
                    ?: $contact->sex;

            $person->locality_id =
                $person->locality_id
                    ?: $contact->locality_id;

            $person->contact_number =
                $person->contact_number
                    ?: $contact->contact_number;

            $person->email =
                $person->email
                    ?: $contact->email;

            $person->facebook_account =
                $person->facebook_account
                    ?: $contact->facebook_account;

            $person->save();

            if (
                filled($contact->school_campus)
                || filled($contact->course_strand)
                || filled($contact->grade_level)
                || $person->educationProfile()->exists()
            ) {
                $education = $person
                    ->educationProfile()
                    ->firstOrNew([]);

                $education->school_workplace =
                    $education->school_workplace
                        ?: $contact->school_campus;

                $education->course_strand =
                    $education->course_strand
                        ?: $contact->course_strand;

                $education->grade_level =
                    $education->grade_level
                        ?: $contact->grade_level;

                $education->save();
            }

            $person
                ->refresh()
                ->load('educationProfile');

            $this->syncCampusContactMirror(
                $contact,
                $person
            );

            $this->assignCampusResponseToPerson(
                $response,
                $contact,
                $person
            );
        }
    );

    ActivityLogger::log(
        action:
            'attendance_meeting_response.campus_linked_to_person',

        subject:
            $response,

        description:
            'Linked Campus pre-listed entry to existing Person.',

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

            'respondent_name' =>
                $response->respondent_name,
        ],
    );

    return back()
        ->with(
            'meeting_response_campus_promoted_to_person',
            true
        )
        ->with(
            'meeting_response_campus_person_name',
            $person->display_name
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

    private function configuredLocality(
    mixed $id,
    string $field
): ?Locality {
    if (blank($id)) {
        return null;
    }

    $locality = LocalityOptions::activeConfiguredLocality(
        (int) $id
    );

    if (! $locality) {
        throw ValidationException::withMessages([
            $field =>
                'Select an active configured Locality.',
        ]);
    }

    return $locality;
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


public function createPersonFromCampus(
    Request $request,
    AttendanceMeetingResponse $response
): RedirectResponse {
    abort_unless(
        auth()->user()?->canManageRecords(),
        403
    );

    if (
        $response->respondent_type
        !== AttendanceMeetingResponse::RESPONDENT_CAMPUS
    ) {
        return back()->withErrors([
            'meeting_response_campus_person_create' =>
                'This response is no longer a Campus pre-listed entry.',
        ]);
    }

    $data = $request->validate([
        'create_campus_person_response_id' => [
            'required',
            'integer',
        ],

        'campus_person_firstname' => [
            'required',
            'string',
            'max:100',
        ],

        'campus_person_lastname' => [
            'required',
            'string',
            'max:100',
        ],

        'campus_person_sex' => [
            'required',
            'in:Male,Female',
        ],

        'campus_person_locality_id' => [
            'required',
            'integer',
            'exists:localities,id',
        ],

        'campus_person_school_campus' => [
            'nullable',
            'string',
            'max:255',
        ],

        'campus_person_course_strand' => [
            'nullable',
            'string',
            'max:255',
        ],

        'campus_person_grade_level' => [
            'nullable',
            'string',
            'max:100',
        ],

        'campus_person_contact_number' => [
            'nullable',
            'string',
            'max:20',
        ],

        'campus_person_email' => [
            'nullable',
            'email',
            'max:255',
        ],

        'campus_person_facebook_account' => [
            'nullable',
            'string',
            'max:255',
        ],

        'create_campus_person_anyway' => [
            'nullable',
            'boolean',
        ],
    ]);

    if (
        (int) $data['create_campus_person_response_id']
        !== (int) $response->id
    ) {
        abort(404);
    }

    $contact = $response
        ->campusContact()
        ->first();

    if (! $contact) {
        return back()->withErrors([
            'meeting_response_campus_person_create' =>
                'The Campus Contact for this pre-listed entry could not be found.',
        ]);
    }

    if ($contact->person_id) {
        return back()->withErrors([
            'meeting_response_campus_person_create' =>
                'This Campus Contact is already linked to the People Database.',
        ]);
    }

    /*
     * Admin-reviewed Campus information.
     */
    $locality = $this->configuredLocality(
        $data['campus_person_locality_id'] ?? null,
        'campus_person_locality_id'
    );

    $normalized = [
        'firstname' =>
            $this->nullIfBlank(
                $data['campus_person_firstname']
                    ?? null
            ),

        'lastname' =>
            $this->nullIfBlank(
                $data['campus_person_lastname']
                    ?? null
            ),

        'sex' =>
            $this->nullIfBlank(
                $data['campus_person_sex']
                    ?? null
            ),

        'locality_id' =>
            $locality?->id,

        'locality' =>
            $locality?->name,

        'school_campus' =>
            $this->nullIfBlank(
                $data['campus_person_school_campus']
                    ?? null
            ),

        'course_strand' =>
            $this->nullIfBlank(
                $data['campus_person_course_strand']
                    ?? null
            ),

        'grade_level' =>
            $this->nullIfBlank(
                $data['campus_person_grade_level']
                    ?? null
            ),

        'contact_number' =>
            $this->nullIfBlank(
                $data['campus_person_contact_number']
                    ?? null
            ),

        'email' =>
            $this->nullIfBlank(
                $data['campus_person_email']
                    ?? null
            ),

        'facebook_account' =>
            $this->nullIfBlank(
                $data['campus_person_facebook_account']
                    ?? null
            ),
    ];

    /*
     * Check People Database using the corrected values
     * before creating anything.
     */
    if (
        ! $request->boolean(
            'create_campus_person_anyway'
        )
    ) {
        $matches =
            $this->possibleCampusPersonMatches(
                $normalized
            );

        if ($matches->isNotEmpty()) {
            $names = $matches
                ->take(5)
                ->map(
                    fn (Person $person): string =>
                        collect([
                            $person->display_name,
                            $person->locality,
                        ])
                            ->filter()
                            ->implode(' · ')
                )
                ->implode(', ');

            return back()
                ->withInput()
                ->withErrors([
                    'meeting_response_campus_person_create' =>
                        'Possible Person already exists: '
                        . $names
                        . '. Use "Link to Existing Person" instead, '
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

        'respondent_name' =>
            $response->respondent_name,

        'campus_contact' => [
            'firstname' =>
                $contact->firstname,

            'lastname' =>
                $contact->lastname,

            'sex' =>
                $contact->sex,

            'locality' =>
                $contact->locality,

            'school_campus' =>
                $contact->school_campus,

            'course_strand' =>
                $contact->course_strand,

            'grade_level' =>
                $contact->grade_level,
        ],
    ];

    $person = DB::transaction(
        function () use (
            $response,
            $contact,
            $normalized
        ): Person {
            /*
             * First save the administrator-reviewed information
             * to the Campus Database.
             */
            $contact->update(
                $normalized
            );

            /*
             * Then create the canonical Person using the same
             * reviewed information.
             */
            $person = new Person();

            $person->firstname =
                $normalized['firstname'];

            $person->lastname =
                $normalized['lastname'];

            $person->sex =
                $normalized['sex'];

            $person->locality_id =
                $normalized['locality_id'];

            $person->contact_number =
                $normalized['contact_number'];

            $person->email =
                $normalized['email'];

            $person->facebook_account =
                $normalized['facebook_account'];

            $person->save();

            $churchProfile = $person
                ->churchProfile()
                ->firstOrNew([]);

            $churchProfile->status =
                'Gospel Friend';

            $churchProfile->save();

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
             * Campus Contact remains part of the identity chain.
             */
            $contact->update([
                'person_id' =>
                    $person->id,
            ]);

            /*
             * Pre-listed entry becomes Person-based, while retaining its
             * Campus Contact and original Guest provenance.
             */
            $this->assignCampusResponseToPerson(
                $response,
                $contact,
                $person
            );

            return $person;
        }
    );

    ActivityLogger::log(
        action:
            'attendance_meeting_response.campus_created_person',

        subject:
            $response,

        description:
            'Created Person from Campus pre-listed entry.',

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

            'respondent_name' =>
                $response->respondent_name,

            'person_status' =>
                'Gospel Friend',

            'campus_contact' =>
                $normalized,
        ],
    );

    return back()
        ->with(
            'meeting_response_campus_promoted_to_person',
            true
        )
        ->with(
            'meeting_response_campus_person_name',
            $person->display_name
        );
}

}
