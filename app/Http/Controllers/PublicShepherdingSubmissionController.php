<?php

namespace App\Http\Controllers;

use App\Models\Locality;
use App\Models\MinistryBook;
use App\Models\PublicShepherdingSubmission;
use App\Models\ShepherdingActivityType;
use App\Models\ShepherdingContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicShepherdingSubmissionController
{
    public function show(): View
    {
        return view(
            'shepherding.public-dashboard',
            [
                'localities' =>
                    Locality::query()
                        ->orderBy('name')
                        ->get([
                            'id',
                            'name',
                        ]),

                'activityTypes' =>
                    ShepherdingActivityType::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->orderBy('category')
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get(),

                'ministryBooks' =>
                    MinistryBook::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->with([
                            'lessons' =>
                                fn ($query) =>
                                    $query
                                        ->where(
                                            'is_active',
                                            true
                                        )
                                        ->orderBy(
                                            'sort_order'
                                        )
                                        ->orderBy(
                                            'code'
                                        ),
                        ])
                        ->orderBy(
                            'sort_order'
                        )
                        ->orderBy(
                            'code'
                        )
                        ->get(),

                'outcomeOptions' =>
                    ShepherdingContact
                        ::outcomeOptions(),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        /*
         * Simple honeypot.
         */
        if (
            filled(
                $request->input(
                    'website'
                )
            )
        ) {
            return back()->with(
                'public_shepherding_saved',
                true
            );
        }

        $data = $request->validate([
            'submitted_by_name' => [
                'required',
                'string',
                'max:255',
            ],

            'submitted_by_contact' => [
                'nullable',
                'string',
                'max:255',
            ],

            'contact_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'contact_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'locality_id' => [
                'nullable',
                'integer',
                'exists:localities,id',
            ],

            'outcome' => [
                'required',
                Rule::in(
                    array_keys(
                        ShepherdingContact
                            ::outcomeOptions()
                    )
                ),
            ],

            'contact_targets_text' => [
                'required',
                'string',
                'max:5000',
            ],

            'participant_names_text' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'activity_type_ids' => [
                'nullable',
                'array',
            ],

            'activity_type_ids.*' => [
                'integer',
                Rule::exists(
                    'shepherding_activity_types',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'ministry_lesson_ids' => [
                'nullable',
                'array',
            ],

            'ministry_lesson_ids.*' => [
                'integer',
                Rule::exists(
                    'ministry_lessons',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'bible_references_text' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'morning_revival_text' => [
                'nullable',
                'string',
                'max:500',
            ],

            'hymns_text' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $bibleReferences =
            collect(
                preg_split(
                    '/\R+/',
                    (string) (
                        $data[
                            'bible_references_text'
                        ]
                        ?? ''
                    )
                )
            )
                ->map(
                    fn ($value) =>
                        trim(
                            (string) $value
                        )
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

        $submission =
            PublicShepherdingSubmission
                ::query()
                ->create([
                    'public_id' =>
                        (string) Str::uuid(),

                    'submitted_by_name' =>
                        trim(
                            $data[
                                'submitted_by_name'
                            ]
                        ),

                    'submitted_by_contact' =>
                        filled(
                            $data[
                                'submitted_by_contact'
                            ]
                            ?? null
                        )
                            ? trim(
                                $data[
                                    'submitted_by_contact'
                                ]
                            )
                            : null,

                    'contact_date' =>
                        $data[
                            'contact_date'
                        ],

                    'contact_time' =>
                        $data[
                            'contact_time'
                        ]
                        ?? null,

                    'locality_id' =>
                        $data[
                            'locality_id'
                        ]
                        ?? null,

                    'outcome' =>
                        $data[
                            'outcome'
                        ],

                    'contact_targets_text' =>
                        trim(
                            $data[
                                'contact_targets_text'
                            ]
                        ),

                    'participant_names_text' =>
                        filled(
                            $data[
                                'participant_names_text'
                            ]
                            ?? null
                        )
                            ? trim(
                                $data[
                                    'participant_names_text'
                                ]
                            )
                            : null,

                    'activity_type_ids' =>
                        collect(
                            $data[
                                'activity_type_ids'
                            ]
                            ?? []
                        )
                            ->map(
                                fn ($id) =>
                                    (int) $id
                            )
                            ->unique()
                            ->values()
                            ->all(),

                    'ministry_lesson_ids' =>
                        collect(
                            $data[
                                'ministry_lesson_ids'
                            ]
                            ?? []
                        )
                            ->map(
                                fn ($id) =>
                                    (int) $id
                            )
                            ->unique()
                            ->values()
                            ->all(),

                    'bible_references' =>
                        $bibleReferences,

                    'morning_revival_text' =>
                        filled(
                            $data[
                                'morning_revival_text'
                            ]
                            ?? null
                        )
                            ? trim(
                                $data[
                                    'morning_revival_text'
                                ]
                            )
                            : null,

                    'hymns_text' =>
                        filled(
                            $data[
                                'hymns_text'
                            ]
                            ?? null
                        )
                            ? trim(
                                $data[
                                    'hymns_text'
                                ]
                            )
                            : null,

                    'notes' =>
                        filled(
                            $data['notes']
                            ?? null
                        )
                            ? trim(
                                $data['notes']
                            )
                            : null,

                    'status' =>
                        PublicShepherdingSubmission
                            ::STATUS_PENDING,
                ]);

        return redirect()
            ->route(
                'shepherding.public.show'
            )
            ->with(
                'public_shepherding_saved',
                true
            )
            ->with(
                'public_shepherding_reference',
                $submission->public_id
            );
    }
}
