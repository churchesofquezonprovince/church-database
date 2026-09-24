<?php

namespace App\Http\Controllers;

use App\Models\GospelContact;
use App\Models\Person;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GospelContactController extends Controller
{
    public function store(
        Request $request
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $data = $this->validatedData($request);
        $normalized = $this->normalizedData($data);

        if (! $request->boolean('create_anyway')) {
            $matches =
                $this->possibleGospelContactMatches(
                    $normalized
                );

            if ($matches->isNotEmpty()) {
                return back()
                    ->withInput()
                    ->with(
                        'gospel_contact_possible_duplicates',
                        $matches
                            ->map(
                                fn (
                                    GospelContact $contact
                                ): array => [
                                    'id' =>
                                        $contact->id,

                                    'name' =>
                                        $contact
                                            ->display_name,

                                    'sex' =>
                                        $contact
                                            ->effective_sex,

                                    'locality' =>
                                        $contact
                                            ->effective_locality,

                                    'contact_place' =>
                                        $contact
                                            ->contact_place,

                                    'people_status' =>
                                        $contact
                                            ->person_id
                                            ? 'Linked to People'
                                            : 'Not linked',
                                ]
                            )
                            ->values()
                            ->all()
                    )
                    ->with(
                        'gospel_contact_possible_duplicate_input',
                        $normalized
                    );
            }
        }

        $contact = GospelContact::query()
            ->create($normalized);

        ActivityLogger::log(
            action:
                'gospel_contact.created',
            subject:
                $contact,
            description:
                'Created a Gospel Contact.',
            oldValues: [],
            newValues:
                $contact->only([
                    'firstname',
                    'lastname',
                    'sex',
                    'locality_id',
                    'contact_number',
                    'email',
                    'facebook_account',
                    'address',
                    'contact_place',
                    'notes',
                ]),
        );

        return back()->with(
            'gospel_contact_created',
            true
        );
    }

    public function update(
        Request $request,
        GospelContact $contact
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        $data = $this->validatedData($request);
        $normalized =
            $this->normalizedData($data);

        if ($contact->person_id) {
            $missingFields =
                collect([
                    'firstname' =>
                        'First Name',

                    'lastname' =>
                        'Last Name',

                    'sex' =>
                        'Sex',

                    'locality_id' =>
                        'Locality',
                ])
                    ->filter(
                        fn (
                            string $label,
                            string $field
                        ): bool =>
                            blank(
                                $normalized[$field]
                                    ?? null
                            )
                    )
                    ->values();

            if ($missingFields->isNotEmpty()) {
                return back()->withErrors([
                    'contact' =>
                        'This Gospel Contact is linked '
                        . 'to the People Database. '
                        . 'Please complete: '
                        . $missingFields
                            ->implode(', ')
                        . '.',
                ]);
            }
        }

        DB::transaction(
            function () use (
                $contact,
                $normalized
            ): void {
                $oldValues =
                    $contact->only([
                        'firstname',
                        'lastname',
                        'sex',
                        'locality_id',
                        'contact_number',
                        'email',
                        'facebook_account',
                        'address',
                        'contact_place',
                        'notes',
                    ]);

                if (! $contact->person_id) {
                    $contact->update(
                        $normalized
                    );
                } else {
                    $person =
                        $contact
                            ->person()
                            ->firstOrFail();

                    $person->firstname =
                        $normalized[
                            'firstname'
                        ];

                    $person->lastname =
                        $normalized[
                            'lastname'
                        ];

                    $person->sex =
                        $normalized['sex'];

                    $person->locality_id =
                        $normalized[
                            'locality_id'
                        ];

                    $person->contact_number =
                        $normalized[
                            'contact_number'
                        ];

                    $person->email =
                        $normalized['email'];

                    $person->facebook_account =
                        $normalized[
                            'facebook_account'
                        ];

                    $person->home_address =
                        $normalized['address'];

                    $person->save();

                    /*
                     * Contact Place and Notes remain
                     * Gospel-work-specific.
                     */
                    $contact->update(
                        $normalized
                    );
                }

                $contact->refresh();

                ActivityLogger::log(
                    action:
                        'gospel_contact.updated',
                    subject:
                        $contact,
                    description:
                        'Updated a Gospel Contact.',
                    oldValues:
                        $oldValues,
                    newValues:
                        $contact->only([
                            'firstname',
                            'lastname',
                            'sex',
                            'locality_id',
                            'contact_number',
                            'email',
                            'facebook_account',
                            'address',
                            'contact_place',
                            'notes',
                        ]),
                );
            }
        );

        return back()->with(
            'gospel_contact_updated',
            true
        );
    }

    public function destroy(
        GospelContact $contact
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canDeleteRecords(),
            403
        );

        DB::transaction(
            function () use ($contact): void {
                ActivityLogger::log(
                    action:
                        'gospel_contact.deleted',
                    subject:
                        $contact,
                    description:
                        'Deleted a Gospel Contact.',
                    oldValues:
                        $contact->only([
                            'firstname',
                            'lastname',
                            'sex',
                            'locality_id',
                            'contact_place',
                        ]),
                    newValues: [],
                );

                /*
                 * Linked Person is never deleted.
                 */
                $contact->delete();
            }
        );

        return back()->with(
            'gospel_contact_deleted',
            true
        );
    }

    public function addToPeople(
        GospelContact $contact
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        if ($contact->person_id) {
            return back()->withErrors([
                'contact' =>
                    'This Gospel Contact is already '
                    . 'linked to the People Database.',
            ]);
        }

        $missingFields =
            $this->missingRequiredPeopleFields(
                $contact
            );

        if ($missingFields->isNotEmpty()) {
            return back()->withErrors([
                'contact' =>
                    'Cannot add this Gospel Contact '
                    . 'to the People Database. '
                    . 'Please complete: '
                    . $missingFields
                        ->implode(', ')
                    . '.',
            ]);
        }

        $matches =
            $this->possiblePeopleMatches(
                $contact
            );

        $matches->loadMissing([
            'churchProfile',
        ]);

        if ($matches->isNotEmpty()) {
            return back()
                ->with(
                    'gospel_contact_possible_match_contact_id',
                    $contact->id
                )
                ->with(
                    'gospel_contact_possible_matches',
                    $matches
                        ->map(
                            fn (
                                Person $person
                            ): array => [
                                'id' =>
                                    $person->id,

                                'name' =>
                                    $person
                                        ->display_name,

                                'sex' =>
                                    $person->sex,

                                'locality' =>
                                    $person
                                        ->locality,

                                'status' =>
                                    $person
                                        ->churchProfile
                                        ?->status,

                                'contact_origin' =>
                                    $person
                                        ->churchProfile
                                        ?->contact_origin,
                            ]
                        )
                        ->values()
                        ->all()
                );
        }

        return $this
            ->createNewPersonFromContact(
                $contact
            );
    }

    public function createNewPersonAnyway(
        GospelContact $contact
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        if ($contact->person_id) {
            return back()->withErrors([
                'contact' =>
                    'This Gospel Contact is already '
                    . 'linked to the People Database.',
            ]);
        }

        $missingFields =
            $this->missingRequiredPeopleFields(
                $contact
            );

        if ($missingFields->isNotEmpty()) {
            return back()->withErrors([
                'contact' =>
                    'Cannot create the Person. '
                    . 'Please complete: '
                    . $missingFields
                        ->implode(', ')
                    . '.',
            ]);
        }

        return $this
            ->createNewPersonFromContact(
                $contact
            );
    }

    public function linkExistingPerson(
        Request $request,
        GospelContact $contact
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        if ($contact->person_id) {
            return back()->withErrors([
                'contact' =>
                    'This Gospel Contact is already '
                    . 'linked to the People Database.',
            ]);
        }

        $data = $request->validate([
            'person_id' => [
                'required',
                'integer',
                'exists:persons,id',

                Rule::unique(
                    'gospel_contacts',
                    'person_id'
                ),
            ],
        ]);

        $person = Person::query()
            ->with('churchProfile')
            ->findOrFail(
                $data['person_id']
            );

        if (
            filled($contact->household_id)
            && filled($person->household_id)
            && (int) $contact->household_id
                !== (int) $person->household_id
        ) {
            return back()->withErrors([
                'contact' =>
                    'Household mismatch: this Gospel Contact '
                    . 'and the selected Person belong to '
                    . 'different households. Resolve the '
                    . 'household assignment first.',
            ]);
        }

        DB::transaction(
            function () use (
                $contact,
                $person
            ): void {
                /*
                 * Fill only blank Person fields.
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

                $person->household_id =
                    $person->household_id
                    ?: $contact->household_id;

                $person->contact_number =
                    $person->contact_number
                    ?: $contact->contact_number;

                $person->email =
                    $person->email
                    ?: $contact->email;

                $person->facebook_account =
                    $person
                        ->facebook_account
                    ?: $contact
                        ->facebook_account;

                $person->home_address =
                    $person->home_address
                    ?: $contact->address;

                $person->save();

                /*
                 * Existing Person:
                 * preserve church status.
                 *
                 * Only fill Contact Origin when it is
                 * not already meaningfully recorded.
                 */
                $profile =
                    $person
                        ->churchProfile()
                        ->firstOrNew([]);

                if (
                    blank(
                        $profile
                            ->contact_origin
                    )
                    || $profile
                        ->contact_origin
                        === 'Unknown'
                ) {
                    $profile->contact_origin =
                        'Gospel Preaching';
                }

                $profile->save();

                $this
                    ->syncContactMirrorFromPerson(
                        $contact,
                        $person
                    );

                ActivityLogger::log(
                    action:
                        'gospel_contact.linked_existing_person',
                    subject:
                        $person,
                    description:
                        'Linked Gospel Contact to an existing Person.',
                    oldValues: [],
                    newValues: [
                        'gospel_contact_id' =>
                            $contact->id,

                        'person_id' =>
                            $person->id,
                    ],
                );
            }
        );

        return back()->with(
            'gospel_contact_linked_existing_person',
            true
        );
    }

    public function addExistingPeople(
        Request $request
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

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

        $alreadyLinked =
            GospelContact::query()
                ->whereIn(
                    'person_id',
                    $data['person_ids']
                )
                ->pluck('person_id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->all();

        $people = Person::query()
            ->whereIn(
                'id',
                $data['person_ids']
            )
            ->whereNotIn(
                'id',
                $alreadyLinked
            )
            ->get();

        $added = 0;

        DB::transaction(
            function () use (
                $people,
                &$added
            ): void {
                foreach ($people as $person) {
                    GospelContact::query()
                        ->create([
                            'person_id' =>
                                $person->id,

                            'firstname' =>
                                $person
                                    ->firstname,

                            'lastname' =>
                                $person
                                    ->lastname,

                            'sex' =>
                                $person->sex,

                            'locality_id' =>
                                $person
                                    ->locality_id,

                            'contact_number' =>
                                $person
                                    ->contact_number,

                            'email' =>
                                $person->email,

                            'facebook_account' =>
                                $person
                                    ->facebook_account,

                            'address' =>
                                $person
                                    ->home_address
                                ?: $person
                                    ->permanent_address,
                        ]);

                    $added++;
                }
            }
        );

        return back()
            ->with(
                'gospel_contact_existing_people_added',
                true
            )
            ->with(
                'gospel_contact_existing_people_added_count',
                $added
            );
    }

    private function possibleGospelContactMatches(
        array $data
    ) {
        $firstname =
            $this->nullIfBlank(
                $data['firstname']
                    ?? null
            );

        if (blank($firstname)) {
            return collect();
        }

        $key = mb_strtolower(
            trim(
                (string) $firstname
            )
        );

        return GospelContact::query()
            ->with([
                'person.churchProfile',
            ])
            ->where(
                function ($query) use ($key): void {
                    $query
                        ->whereRaw(
                            'LOWER(firstname) = ?',
                            [$key]
                        )
                        ->orWhereHas(
                            'person',
                            function ($query) use ($key): void {
                                $query->whereRaw(
                                    'LOWER(firstname) = ?',
                                    [$key]
                                );
                            }
                        );
                }
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(10)
            ->get();
    }

    private function possiblePeopleMatches(
        GospelContact $contact
    ) {
        return Person::query()
            ->with('churchProfile')
            ->whereDoesntHave(
                'gospelContact'
            )
            ->whereRaw(
                'LOWER(firstname) = ?',
                [
                    mb_strtolower(
                        trim(
                            (string)
                            $contact
                                ->firstname
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
                            $contact
                                ->lastname
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
                filled(
                    $contact->locality_id
                ),
                fn (Builder $query): Builder =>
                    $query->where(
                        'locality_id',
                        $contact->locality_id
                    )
            )
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(10)
            ->get();
    }

    private function createNewPersonFromContact(
        GospelContact $contact
    ): RedirectResponse {
        $person = DB::transaction(
            function () use (
                $contact
            ): Person {
                $person = new Person();

                $person->firstname =
                    $contact->firstname;

                $person->lastname =
                    $contact->lastname;

                $person->sex =
                    $contact->sex;

                $person->locality_id =
                    $contact->locality_id;

                $person->household_id =
                    $contact->household_id;

                $person->contact_number =
                    $contact
                        ->contact_number;

                $person->email =
                    $contact->email;

                $person->facebook_account =
                    $contact
                        ->facebook_account;

                $person->home_address =
                    $contact->address;

                $person->save();

                $profile =
                    $person
                        ->churchProfile()
                        ->firstOrNew([]);

                /*
                 * New Person promoted directly from
                 * Gospel Contacts.
                 */
                $profile->status =
                    'Gospel Friend';

                $profile->contact_origin =
                    'Gospel Preaching';

                /*
                 * Do NOT guess first_contact_date.
                 * We do not yet have a trustworthy
                 * first-contact date on GospelContact.
                 */
                $profile->save();

                $contact->update([
                    'person_id' =>
                        $person->id,

                    'household_id' =>
                        null,
                ]);

                ActivityLogger::log(
                    action:
                        'gospel_contact.added_to_people',
                    subject:
                        $person,
                    description:
                        'Added Gospel Contact to the People Database as Gospel Friend.',
                    oldValues: [],
                    newValues: [
                        'gospel_contact_id' =>
                            $contact->id,

                        'person_id' =>
                            $person->id,

                        'status' =>
                            'Gospel Friend',

                        'contact_origin' =>
                            'Gospel Preaching',
                    ],
                );

                return $person;
            }
        );

        return back()
            ->with(
                'gospel_contact_added_to_people',
                true
            )
            ->with(
                'gospel_contact_added_person_id',
                $person->id
            );
    }

    private function missingRequiredPeopleFields(
        GospelContact $contact
    ) {
        return collect([
            'firstname' =>
                'First Name',

            'lastname' =>
                'Last Name',

            'sex' =>
                'Sex',

            'locality_id' =>
                'Locality',
        ])
            ->filter(
                fn (
                    string $label,
                    string $field
                ): bool =>
                    blank(
                        $contact->{$field}
                    )
            )
            ->values();
    }

    private function syncContactMirrorFromPerson(
        GospelContact $contact,
        Person $person
    ): void {
        $contact->update([
            'person_id' =>
                $person->id,

            'household_id' =>
                null,

            'firstname' =>
                $person->firstname,

            'lastname' =>
                $person->lastname,

            'sex' =>
                $person->sex,

            'locality_id' =>
                $person->locality_id,

            'contact_number' =>
                $person->contact_number,

            'email' =>
                $person->email,

            'facebook_account' =>
                $person
                    ->facebook_account,

            'address' =>
                $person->home_address
                ?: $person
                    ->permanent_address,
        ]);
    }

    private function validatedData(
        Request $request
    ): array {
        return $request->validate([
            'firstname' => [
                'required',
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

            'locality_id' => [
                'nullable',
                'integer',
                'exists:localities,id',
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

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'contact_place' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);
    }

    private function normalizedData(
        array $data
    ): array {
        return [
            'firstname' =>
                $this->nullIfBlank(
                    $data['firstname']
                        ?? null
                ),

            'lastname' =>
                $this->nullIfBlank(
                    $data['lastname']
                        ?? null
                ),

            'sex' =>
                $this->nullIfBlank(
                    $data['sex']
                        ?? null
                ),

            'locality_id' =>
                filled(
                    $data['locality_id']
                        ?? null
                )
                    ? (int)
                        $data[
                            'locality_id'
                        ]
                    : null,

            'contact_number' =>
                $this->nullIfBlank(
                    $data['contact_number']
                        ?? null
                ),

            'email' =>
                $this->nullIfBlank(
                    $data['email']
                        ?? null
                ),

            'facebook_account' =>
                $this->nullIfBlank(
                    $data['facebook_account']
                        ?? null
                ),

            'address' =>
                $this->nullIfBlank(
                    $data['address']
                        ?? null
                ),

            'contact_place' =>
                $this->nullIfBlank(
                    $data['contact_place']
                        ?? null
                ),

            'notes' =>
                $this->nullIfBlank(
                    $data['notes']
                        ?? null
                ),
        ];
    }

    private function nullIfBlank(
        mixed $value
    ): ?string {
        if (blank($value)) {
            return null;
        }

        return trim(
            (string) $value
        );
    }
}
