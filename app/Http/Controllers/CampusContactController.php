<?php

namespace App\Http\Controllers;

use App\Filament\Pages\CampusContacts;
use App\Models\CampusContact;
use App\Models\Person;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampusContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedData($request);
        $normalized = $this->normalizedData($data);

        /*
         * Campus Contact duplicate detector:
         * Before creating a new Campus Contact, warn if another
         * Campus Contact already has the same first name.
         */
        if (! $request->boolean('create_anyway')) {
            $matches = $this->possibleCampusContactMatches(
                $normalized
            );

            if ($matches->isNotEmpty()) {
                return back()
                    ->withInput()
                    ->with(
                        'campus_contact_possible_duplicates',
                        $matches
                            ->map(
                                fn (
                                    CampusContact $contact
                                ): array => [
                                    'id' => $contact->id,
                                    'name' => $contact->display_name,
                                    'firstname' =>
                                        $contact->effective_firstname,
                                    'lastname' =>
                                        $contact->effective_lastname,
                                    'sex' =>
                                        $contact->effective_sex,
                                    'locality' =>
                                        $contact->effective_locality,
                                    'school' =>
                                        $contact
                                            ->effective_school_campus,
                                    'course' =>
                                        $contact
                                            ->effective_course_strand,
                                    'year_level' =>
                                        $contact
                                            ->effective_grade_level,
                                    'people_status' =>
                                        $contact->person_id
                                            ? 'Linked to People'
                                            : 'Not linked',
                                    'reason' =>
                                        filled($normalized['lastname'])
                                        && strcasecmp(
                                            (string)
                                            $contact
                                                ->effective_lastname,
                                            (string)
                                            $normalized['lastname']
                                        ) === 0
                                            ? 'Same first and last name'
                                            : 'Same first name',
                                ]
                            )
                            ->values()
                            ->all()
                    )
                    ->with(
                        'campus_contact_possible_duplicate_input',
                        $normalized
                    );
            }
        }

        CampusContact::query()->create($normalized);

        return back()->with('campus_contact_created', true);
    }

    public function update(
        Request $request,
        CampusContact $contact
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedData($request);
        $normalized = $this->normalizedData($data);

        /*
         * Once linked to People, the four core People fields
         * must remain complete because editing this Campus Contact
         * also updates the linked Person.
         */
        if ($contact->person_id) {
            $requiredPeopleFields = [
                'firstname' => 'First Name',
                'lastname' => 'Last Name',
                'sex' => 'Sex',
                'locality' => 'Locality',
            ];

            $missingFields = collect($requiredPeopleFields)
                ->filter(
                    fn (
                        string $label,
                        string $field
                    ): bool =>
                        blank($normalized[$field] ?? null)
                )
                ->values();

            if ($missingFields->isNotEmpty()) {
                return back()->withErrors([
                    'contact' =>
                        'This Campus Contact is linked to the '
                        . 'People Database. Please complete: '
                        . $missingFields->implode(', ')
                        . '.',
                ]);
            }
        }

        DB::transaction(function () use (
            $contact,
            $normalized
        ): void {
            /*
             * Unlinked contact:
             * Update Campus Contact only.
             */
            if (! $contact->person_id) {
                $contact->update($normalized);

                return;
            }

            /*
             * Linked contact:
             * Person Database is the source of truth for shared fields.
             */
            $person = $contact->person()->firstOrFail();

            $person->firstname = $normalized['firstname'];
            $person->lastname = $normalized['lastname'];
            $person->sex = $normalized['sex'];
            $person->locality = $normalized['locality'];
            $person->contact_number = $normalized['contact_number'];
            $person->email = $normalized['email'];
            $person->facebook_account = $normalized['facebook_account'];

            $person->save();

            /*
             * Update Education Profile for campus-related fields.
             */
            if (
                filled($normalized['school_campus'])
                || filled($normalized['course_strand'])
                || filled($normalized['grade_level'])
                || $person->educationProfile()->exists()
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
             * Keep Campus Contact mirror synchronized.
             * Notes remain Campus Contact-specific.
             */
            $contact->update($normalized);
        });

        return back()->with('campus_contact_updated', true);
    }

    public function destroy(
        CampusContact $contact
    ): RedirectResponse {
        abort_unless(auth()->user()?->canDeleteRecords(), 403);

        /*
         * The linked Person is never deleted.
         */
        $contact->delete();

        return back()->with('campus_contact_deleted', true);
    }

    public function addToPeople(
        CampusContact $contact
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        if ($contact->person_id) {
            return back()->withErrors([
                'contact' =>
                    'This Campus Contact is already linked '
                    . 'to the People Database.',
            ]);
        }

        $missingFields = $this->missingRequiredPeopleFields(
            $contact
        );

        if ($missingFields->isNotEmpty()) {
            return back()->withErrors([
                'contact' =>
                    'Cannot add this Campus Contact to the '
                    . 'People Database. Please complete: '
                    . $missingFields->implode(', ')
                    . '.',
            ]);
        }

        /*
         * Before creating a new Person, search for possible matches.
         */
        $matches = $this->possiblePeopleMatches($contact);

        if ($matches->isNotEmpty()) {
            return back()
                ->with(
                    'campus_contact_possible_match_contact_id',
                    $contact->id
                )
                ->with(
                    'campus_contact_possible_matches',
                    $matches
                        ->map(fn (Person $person): array => [
                            'id' => $person->id,
                            'name' => $person->display_name,
                            'sex' => $person->sex,
                            'locality' => $person->locality,
                            'school' =>
                                $person
                                    ->educationProfile
                                    ?->school_workplace,
                            'status' =>
                                $person
                                    ->churchProfile
                                    ?->status,
                        ])
                        ->values()
                        ->all()
                );
        }

        return $this->createNewPersonFromContact($contact);
    }

    public function createNewPersonAnyway(
        CampusContact $contact
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        if ($contact->person_id) {
            return back()->withErrors([
                'contact' =>
                    'This Campus Contact is already linked '
                    . 'to the People Database.',
            ]);
        }

        $missingFields = $this->missingRequiredPeopleFields(
            $contact
        );

        if ($missingFields->isNotEmpty()) {
            return back()->withErrors([
                'contact' =>
                    'Cannot create the Person. Please complete: '
                    . $missingFields->implode(', ')
                    . '.',
            ]);
        }

        return $this->createNewPersonFromContact($contact);
    }

    public function linkExistingPerson(
        Request $request,
        CampusContact $contact
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        if ($contact->person_id) {
            return back()->withErrors([
                'contact' =>
                    'This Campus Contact is already linked '
                    . 'to the People Database.',
            ]);
        }

        $data = $request->validate([
            'person_id' => [
                'required',
                'integer',
                'exists:persons,id',

                Rule::unique(
                    'campus_contacts',
                    'person_id'
                ),
            ],
        ]);

        $person = Person::query()
            ->with([
                'churchProfile',
                'educationProfile',
            ])
            ->findOrFail($data['person_id']);

        DB::transaction(function () use (
            $contact,
            $person
        ): void {
            /*
             * Fill only blank Person fields from Campus Contact.
             * Never overwrite existing Person values automatically.
             */
            $person->firstname = $person->firstname
                ?: $contact->firstname;

            $person->lastname = $person->lastname
                ?: $contact->lastname;

            $person->sex = $person->sex
                ?: $contact->sex;

            $person->locality = $person->locality
                ?: $contact->locality;

            $person->contact_number = $person->contact_number
                ?: $contact->contact_number;

            $person->email = $person->email
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

            /*
             * Preserve the existing Person's church status.
             * Do not change it to Gospel Friend.
             */
            $person->refresh()->load('educationProfile');

            $this->syncContactMirrorFromPerson(
                $contact,
                $person
            );
        });

        return back()->with(
            'campus_contact_linked_existing_person',
            true
        );
    }

    public function addExistingPeople(
        Request $request
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'person_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'person_ids.*' => [
                'integer',
                'distinct',
                'exists:persons,id',
            ],
        ]);

        $alreadyLinkedPersonIds = CampusContact::query()
            ->whereIn('person_id', $data['person_ids'])
            ->pluck('person_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $people = Person::query()
            ->with([
                'churchProfile',
                'educationProfile',
            ])
            ->whereIn('id', $data['person_ids'])
            ->whereNotIn('id', $alreadyLinkedPersonIds)
            ->get();

        $added = 0;

        DB::transaction(function () use (
            $people,
            &$added
        ): void {
            foreach ($people as $person) {
                CampusContact::query()->create([
                    'person_id' => $person->id,

                    'firstname' =>
                        $person->firstname,

                    'lastname' =>
                        $person->lastname,

                    'sex' =>
                        $person->sex,

                    'locality' =>
                        $person->locality,

                    'school_campus' =>
                        $person
                            ->educationProfile
                            ?->school_workplace,

                    'course_strand' =>
                        $person
                            ->educationProfile
                            ?->course_strand,

                    'grade_level' =>
                        $person
                            ->educationProfile
                            ?->grade_level,

                    'contact_number' =>
                        $person->contact_number,

                    'email' =>
                        $person->email,

                    'facebook_account' =>
                        $person->facebook_account,
                ]);

                $added++;
            }
        });

        return back()
            ->with(
                'campus_contact_existing_people_added',
                true
            )
            ->with(
                'campus_contact_existing_people_added_count',
                $added
            );
    }

    private function possibleCampusContactMatches(
        array $data
    ) {
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
            ->with([
                'person.churchProfile',
                'person.educationProfile',
            ])
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


    private function createNewPersonFromContact(
        CampusContact $contact
    ): RedirectResponse {
        $person = DB::transaction(
            function () use ($contact): Person {
                $person = new Person();

                $person->firstname = $contact->firstname;
                $person->lastname = $contact->lastname;
                $person->sex = $contact->sex;
                $person->locality = $contact->locality;

                $person->contact_number =
                    $contact->contact_number;

                $person->email =
                    $contact->email;

                $person->facebook_account =
                    $contact->facebook_account;

                $person->save();

                /*
                 * New Person created from a Campus Contact:
                 * Gospel Friend by default.
                 */
                $churchProfile = $person
                    ->churchProfile()
                    ->firstOrNew([]);

                $churchProfile->status = 'Gospel Friend';
                $churchProfile->save();

                if (
                    filled($contact->school_campus)
                    || filled($contact->course_strand)
                    || filled($contact->grade_level)
                ) {
                    $education = $person
                        ->educationProfile()
                        ->firstOrNew([]);

                    $education->school_workplace =
                        $contact->school_campus;

                    $education->course_strand =
                        $contact->course_strand;

                    $education->grade_level =
                        $contact->grade_level;

                    $education->save();
                }

                $contact->update([
                    'person_id' => $person->id,
                ]);

                ActivityLogger::log(
                    action:
                        'campus_contact.added_to_people',

                    subject: $person,

                    description:
                        'Added Campus Contact to the '
                        . 'People Database as Gospel Friend.',

                    newValues: [
                        'campus_contact_id' =>
                            $contact->id,

                        'person_id' =>
                            $person->id,

                        'status' =>
                            'Gospel Friend',
                    ],
                );

                return $person;
            }
        );

        return back()
            ->with(
                'campus_contact_added_to_people',
                true
            )
            ->with(
                'campus_contact_added_person_id',
                $person->id
            );
    }

    private function possiblePeopleMatches(
        CampusContact $contact
    ) {
        return Person::query()
            ->with([
                'churchProfile',
                'educationProfile',
            ])
            ->whereDoesntHave(
                'campusContact'
            )
            ->whereRaw(
                'LOWER(firstname) = ?',
                [
                    mb_strtolower(
                        trim(
                            (string) $contact->firstname
                        )
                    ),
                ]
            )
            ->whereRaw(
                'LOWER(lastname) = ?',
                [
                    mb_strtolower(
                        trim(
                            (string) $contact->lastname
                        )
                    ),
                ]
            )
            ->when(
                filled($contact->sex),
                fn (Builder $query): Builder =>
                    $query->where(
                        'sex',
                        $contact->sex
                    )
            )
            ->when(
                filled($contact->locality),
                fn (Builder $query): Builder =>
                    $query->whereRaw(
                        'LOWER(locality) = ?',
                        [
                            mb_strtolower(
                                trim(
                                    (string)
                                    $contact->locality
                                )
                            ),
                        ]
                    )
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(10)
            ->get();
    }

    private function missingRequiredPeopleFields(
        CampusContact $contact
    ) {
        return collect([
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'sex' => 'Sex',
            'locality' => 'Locality',
        ])
            ->filter(
                fn (
                    string $label,
                    string $field
                ): bool =>
                    blank($contact->{$field})
            )
            ->values();
    }

    private function syncContactMirrorFromPerson(
        CampusContact $contact,
        Person $person
    ): void {
        $contact->update([
            'person_id' => $person->id,

            'firstname' =>
                $person->firstname,

            'lastname' =>
                $person->lastname,

            'sex' =>
                $person->sex,

            'locality' =>
                $person->locality,

            'school_campus' =>
                $person
                    ->educationProfile
                    ?->school_workplace,

            'course_strand' =>
                $person
                    ->educationProfile
                    ?->course_strand,

            'grade_level' =>
                $person
                    ->educationProfile
                    ?->grade_level,

            'contact_number' =>
                $person->contact_number,

            'email' =>
                $person->email,

            'facebook_account' =>
                $person->facebook_account,
        ]);
    }

    private function validatedData(
        Request $request
    ): array {
        return $request->validate([
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
                Rule::in([
                    'Male',
                    'Female',
                ]),
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

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);
    }

    private function normalizedData(
        array $data
    ): array {
        return [
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

            'notes' =>
                $this->nullIfBlank(
                    $data['notes'] ?? null
                ),
        ];
    }

    private function nullIfBlank(
        mixed $value
    ): ?string {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
