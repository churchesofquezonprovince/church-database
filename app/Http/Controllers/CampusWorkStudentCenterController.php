<?php

namespace App\Http\Controllers;

use App\Models\CampusContact;
use App\Models\CampusWorkStudentCenter;
use App\Models\CampusWorkStudentCenterMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CampusWorkStudentCenterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        CampusWorkStudentCenter::create($this->validatedCenterData($request));

        return back()->with('student_center_saved', true);
    }

    public function update(Request $request, CampusWorkStudentCenter $studentCenter): RedirectResponse
    {
        $this->authorizeManage();

        $studentCenter->update($this->validatedCenterData($request));

        return back()->with('student_center_updated', true);
    }

    public function destroy(CampusWorkStudentCenter $studentCenter): RedirectResponse
    {
        $this->authorizeManage();

        $studentCenter->delete();

        return back()->with('student_center_deleted', true);
    }

    public function storeMembers(Request $request, CampusWorkStudentCenter $studentCenter): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'campus_contact_ids' => ['required', 'array', 'min:1'],
            'campus_contact_ids.*' => ['integer', 'exists:campus_contacts,id'],
        ]);

        $added = 0;

        foreach ($validated['campus_contact_ids'] as $contactId) {
            $contact = CampusContact::query()->find($contactId);

            if (! $contact) {
                continue;
            }

            $member = CampusWorkStudentCenterMember::query()->firstOrCreate([
                'campus_work_student_center_id' => $studentCenter->id,
                'campus_contact_id' => $contact->id,
            ]);

            if ($member->wasRecentlyCreated) {
                $added++;
            }
        }

        return back()
            ->with('student_center_members_saved', true)
            ->with('student_center_members_added', $added);
    }

    public function destroyMember(
        CampusWorkStudentCenter $studentCenter,
        CampusWorkStudentCenterMember $member
    ): RedirectResponse {
        $this->authorizeManage();

        abort_unless($member->campus_work_student_center_id === $studentCenter->id, 404);

        $member->delete();

        return back()->with('student_center_member_removed', true);
    }

    protected function validatedCenterData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'school_campus' => ['nullable', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:150'],
            'place' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $validated[$key] = trim($value) === '' ? null : trim($value);
            }
        }

        return $validated;
    }

    protected function authorizeManage(): void
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);
    }
}
