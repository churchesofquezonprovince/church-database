<?php

namespace App\Http\Controllers;

use App\Models\CampusWorkTerm;
use App\Models\StudentNucleusMembership;
use Illuminate\View\View;

class StudentNucleusPrintController extends Controller
{
    public function __invoke(CampusWorkTerm $term): View
    {
        abort_unless(auth()->user()?->canManageRecords(), 403);

        $memberships = StudentNucleusMembership::query()
            ->where('campus_work_term_id', $term->id)
            ->with([
                'person.educationProfile',
            ])
            ->get()
            ->sortBy(function (StudentNucleusMembership $membership): string {
                $person = $membership->person;

                return mb_strtolower(implode('|', [
                    $person?->educationProfile?->school_workplace ?? '',
                    $person?->lastname ?? '',
                    $person?->firstname ?? '',
                ]));
            })
            ->values();

        $groupedMembers = $memberships
            ->groupBy(
                fn (StudentNucleusMembership $membership): string =>
                    $membership->person?->educationProfile?->school_workplace
                    ?: 'School not recorded'
            )
            ->sortKeys();

        return view('reports.student-nucleus-print', [
            'term' => $term,
            'memberships' => $memberships,
            'groupedMembers' => $groupedMembers,
        ]);
    }
}
