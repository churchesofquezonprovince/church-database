<?php

namespace App\Http\Controllers;

use App\Models\StudentNucleusMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentNucleusController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'campus_work_term_id' => [
                'required',
                'integer',
                'exists:campus_work_terms,id',
            ],
            'person_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'person_ids.*' => [
                'integer',
                'exists:persons,id',
            ],
        ]);

        $termId = (int) $data['campus_work_term_id'];

        $personIds = collect($data['person_ids'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $added = 0;

        foreach ($personIds as $personId) {
            $membership = StudentNucleusMembership::query()->firstOrCreate([
                'campus_work_term_id' => $termId,
                'person_id' => $personId,
            ]);

            if ($membership->wasRecentlyCreated) {
                $added++;
            }
        }

        return back()
            ->with('student_nucleus_members_saved', true)
            ->with('student_nucleus_members_added', $added);
    }

    public function update(
        Request $request,
        StudentNucleusMembership $membership
    ): RedirectResponse {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $data = $request->validate([
            'spiritual_condition' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $membership->update([
            'spiritual_condition' => filled($data['spiritual_condition'] ?? null)
                ? trim((string) $data['spiritual_condition'])
                : null,
        ]);

        return back()->with(
            'student_nucleus_spiritual_condition_saved',
            true
        );
    }

    public function destroy(
        StudentNucleusMembership $membership
    ): RedirectResponse {
        abort_unless(auth()->user()?->canDeleteRecords(), 403);

        $membership->delete();

        return back()->with(
            'student_nucleus_member_removed',
            true
        );
    }
}
