<?php

namespace App\Http\Controllers;

use App\Filament\Pages\CampusContacts;
use App\Filament\Pages\StudentNucleus;
use App\Models\CampusWorkTerm;
use App\Models\CampusContactTermMembership;
use App\Models\StudentNucleusMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CampusWorkTermController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                'max:20',
            ],
            'semester' => [
                'required',
                'string',
                'max:50',
                Rule::unique('campus_work_terms', 'semester')
                    ->where(
                        fn ($query) => $query->where(
                            'academic_year',
                            $request->input('academic_year')
                        )
                    ),
            ],
            'set_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $shouldActivate = $request->boolean('set_active')
            || ! CampusWorkTerm::query()
                ->where('is_active', true)
                ->where('is_archived', false)
                ->exists();

        $term = DB::transaction(function () use ($data, $shouldActivate): CampusWorkTerm {
            if ($shouldActivate) {
                CampusWorkTerm::query()->update([
                    'is_active' => false,
                ]);
            }

            return CampusWorkTerm::query()->create([
                'academic_year' => $data['academic_year'],
                'semester' => trim($data['semester']),
                'is_active' => $shouldActivate,
                'is_archived' => false,
            ]);
        });

        return redirect()
            ->to(
                $this->returnUrl(
                    $request,
                    $term
                )
            )
            ->with('campus_work_term_created', true);
    }

    public function activate(
        Request $request,
        CampusWorkTerm $term
    ): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        if ($term->is_archived) {
            return back()->withErrors([
                'term' => 'Restore this academic term before setting it as active.',
            ]);
        }

        DB::transaction(function () use ($term): void {
            CampusWorkTerm::query()->update([
                'is_active' => false,
            ]);

            $term->update([
                'is_active' => true,
                'is_archived' => false,
            ]);
        });

        return redirect()
            ->to(
                $this->returnUrl(
                    $request,
                    $term
                )
            )
            ->with('campus_work_term_activated', true);
    }

    public function copyMembers(
        Request $request,
        CampusWorkTerm $term
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        if ($term->is_archived) {
            return back()->withErrors([
                'term' => 'Students cannot be copied into an archived academic term.',
            ]);
        }

        $data = $request->validate([
            'source_term_id' => [
                'required',
                'integer',
                'exists:campus_work_terms,id',
            ],
        ]);

        $sourceTermId = (int) $data['source_term_id'];

        if ($sourceTermId === (int) $term->id) {
            return back()->withErrors([
                'source_term_id' => 'Choose a different academic term to copy from.',
            ]);
        }

        $sourcePersonIds = StudentNucleusMembership::query()
            ->where('campus_work_term_id', $sourceTermId)
            ->pluck('person_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($sourcePersonIds->isEmpty()) {
            return back()->withErrors([
                'source_term_id' => 'The selected source term has no Student Nucleus members.',
            ]);
        }

        $added = 0;

        DB::transaction(function () use ($term, $sourcePersonIds, &$added): void {
            foreach ($sourcePersonIds as $personId) {
                $membership = StudentNucleusMembership::query()->firstOrCreate(
                    [
                        'campus_work_term_id' => $term->id,
                        'person_id' => $personId,
                    ],
                    [
                        'spiritual_condition' => null,
                    ]
                );

                if ($membership->wasRecentlyCreated) {
                    $added++;
                }
            }
        });

        return back()
            ->with('campus_work_term_members_copied', true)
            ->with('campus_work_term_members_added', $added);
    }

    public function copyContacts(
        Request $request,
        CampusWorkTerm $term
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        if ($term->is_archived) {
            return back()->withErrors([
                'term' =>
                    'Campus Contacts cannot be copied into '
                    . 'an archived academic term.',
            ]);
        }

        $data = $request->validate([
            'source_term_id' => [
                'required',
                'integer',
                'exists:campus_work_terms,id',
            ],
        ]);

        $sourceTermId =
            (int) $data['source_term_id'];

        if ($sourceTermId === (int) $term->id) {
            return back()->withErrors([
                'source_term_id' =>
                    'Choose a different academic term '
                    . 'to copy from.',
            ]);
        }

        $sourceContactIds =
            CampusContactTermMembership::query()
                ->where(
                    'campus_work_term_id',
                    $sourceTermId
                )
                ->pluck('campus_contact_id')
                ->map(
                    fn ($id): int => (int) $id
                )
                ->unique()
                ->values();

        if ($sourceContactIds->isEmpty()) {
            return back()->withErrors([
                'source_term_id' =>
                    'The selected source term has no '
                    . 'Campus Contacts.',
            ]);
        }

        $added = 0;

        DB::transaction(
            function () use (
                $term,
                $sourceContactIds,
                &$added
            ): void {
                foreach (
                    $sourceContactIds as $contactId
                ) {
                    $membership =
                        CampusContactTermMembership::query()
                            ->firstOrCreate([
                                'campus_work_term_id' =>
                                    $term->id,

                                'campus_contact_id' =>
                                    $contactId,
                            ]);

                    if (
                        $membership->wasRecentlyCreated
                    ) {
                        $added++;
                    }
                }
            }
        );

        return back()
            ->with(
                'campus_work_term_contacts_copied',
                true
            )
            ->with(
                'campus_work_term_contacts_added',
                $added
            );
    }

    public function archive(
        Request $request,
        CampusWorkTerm $term
    ): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        if ($term->is_active) {
            return back()->withErrors([
                'term' => 'The active term cannot be archived. Set another term as active first.',
            ]);
        }

        $term->update([
            'is_archived' => true,
            'is_active' => false,
        ]);

        return redirect()
            ->to(
                $this->returnUrl($request)
            )
            ->with('campus_work_term_archived', true);
    }

    public function restore(
        Request $request,
        CampusWorkTerm $term
    ): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $term->update([
            'is_archived' => false,
        ]);

        return redirect()
            ->to(
                $this->returnUrl(
                    $request,
                    $term
                )
            )
            ->with('campus_work_term_restored', true);
    }
    private function returnUrl(
        Request $request,
        ?CampusWorkTerm $term = null
    ): string {
        $returnTo = (string) $request->input(
            'return_to',
            'student-nucleus'
        );

        $baseUrl =
            $returnTo === 'campus-contacts'
                ? CampusContacts::getUrl()
                : StudentNucleus::getUrl();

        if (! $term) {
            return $baseUrl;
        }

        return $baseUrl
            . '?'
            . http_build_query([
                'termId' => $term->id,
            ]);
    }

}
