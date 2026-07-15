<?php

namespace App\Http\Controllers;

use App\Models\CampusContact;
use App\Models\Person;
use App\Support\ActivityLogger;
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

        CampusContact::query()->create(
            $this->normalizedData($data)
        );

        return back()->with('campus_contact_created', true);
    }

    public function update(
        Request $request,
        CampusContact $contact
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $this->validatedData($request);

        $contact->update(
            $this->normalizedData($data)
        );

        return back()->with('campus_contact_updated', true);
    }

    public function destroy(
        CampusContact $contact
    ): RedirectResponse {
        abort_unless(auth()->user()?->canDeleteRecords(), 403);

        /*
         * Deleting a Campus Contact does not delete its linked Person.
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
                'contact' => 'This Campus Contact is already linked to the People Database.',
            ]);
        }

        $requiredForPeople = [
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'sex' => 'Sex',
            'locality' => 'Locality',
        ];

        $missingFields = collect($requiredForPeople)
            ->filter(
                fn (string $label, string $field): bool =>
                    blank($contact->{$field})
            )
            ->values();

        if ($missingFields->isNotEmpty()) {
            return back()->withErrors([
                'contact' =>
                    'Cannot add this Campus Contact to the People Database. '
                    . 'Please complete: '
                    . $missingFields->implode(', ')
                    . '.',
            ]);
        }

        $person = DB::transaction(function () use ($contact): Person {
            $person = new Person();

            $person->firstname = $contact->firstname;
            $person->lastname = $contact->lastname;
            $person->sex = $contact->sex;
            $person->locality = $contact->locality;

            $person->contact_number = $contact->contact_number;
            $person->email = $contact->email;
            $person->facebook_account = $contact->facebook_account;

            $person->save();

            /*
             * Person::saved() already ensures that a Church Profile exists.
             * Override its initial Unknown status with Gospel Friend.
             */
            $churchProfile = $person->churchProfile()->firstOrNew([]);

            $churchProfile->status = 'Gospel Friend';
            $churchProfile->save();

            /*
             * Create Education Profile when campus information exists.
             */
            if (
                filled($contact->school_campus)
                || filled($contact->course_strand)
                || filled($contact->grade_level)
            ) {
                $education = $person->educationProfile()->firstOrNew([]);

                $education->school_workplace = $contact->school_campus;
                $education->course_strand = $contact->course_strand;
                $education->grade_level = $contact->grade_level;

                $education->save();
            }

            $contact->update([
                'person_id' => $person->id,
            ]);

            ActivityLogger::log(
                action: 'campus_contact.added_to_people',
                subject: $person,
                description: 'Added Campus Contact to the People Database as Gospel Friend.',
                newValues: [
                    'campus_contact_id' => $contact->id,
                    'person_id' => $person->id,
                    'firstname' => $person->firstname,
                    'lastname' => $person->lastname,
                    'sex' => $person->sex,
                    'locality' => $person->locality,
                    'school_campus' => $contact->school_campus,
                    'status' => 'Gospel Friend',
                ],
            );

            return $person;
        });

        return back()
            ->with('campus_contact_added_to_people', true)
            ->with('campus_contact_added_person_id', $person->id);
    }

    private function validatedData(Request $request): array
    {
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

    private function normalizedData(array $data): array
    {
        return [
            'firstname' => $this->nullIfBlank(
                $data['firstname'] ?? null
            ),

            'lastname' => $this->nullIfBlank(
                $data['lastname'] ?? null
            ),

            'sex' => $this->nullIfBlank(
                $data['sex'] ?? null
            ),

            'locality' => $this->nullIfBlank(
                $data['locality'] ?? null
            ),

            'school_campus' => $this->nullIfBlank(
                $data['school_campus'] ?? null
            ),

            'course_strand' => $this->nullIfBlank(
                $data['course_strand'] ?? null
            ),

            'grade_level' => $this->nullIfBlank(
                $data['grade_level'] ?? null
            ),

            'contact_number' => $this->nullIfBlank(
                $data['contact_number'] ?? null
            ),

            'email' => $this->nullIfBlank(
                $data['email'] ?? null
            ),

            'facebook_account' => $this->nullIfBlank(
                $data['facebook_account'] ?? null
            ),

            'notes' => $this->nullIfBlank(
                $data['notes'] ?? null
            ),
        ];
    }

    private function nullIfBlank(mixed $value): ?string
    {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
