<?php

namespace App\Http\Controllers;

use App\Models\CampusWorkTerm;
use App\Models\StudentNucleusMembership;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentNucleusExportController extends Controller
{
    public function __invoke(CampusWorkTerm $term): StreamedResponse
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

        $safeAcademicYear = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $term->academic_year
        );

        $safeSemester = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $term->semester
        );

        $filename = sprintf(
            'student-nucleus-ay-%s-%s.csv',
            trim((string) $safeAcademicYear, '-'),
            trim((string) $safeSemester, '-')
        );

        return response()->streamDownload(
            function () use ($memberships, $term): void {
                $output = fopen('php://output', 'wb');

                if ($output === false) {
                    return;
                }

                /*
                 * UTF-8 BOM helps Microsoft Excel correctly display
                 * names and other Unicode characters.
                 */
                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, [
                    'Academic Year',
                    'Semester',
                    'School',
                    'No.',
                    'Name of Student',
                    'Locality',
                    'Course',
                    'Grade/Year Level',
                    'Contact Number',
                    'Email',
                    'Spiritual Condition',
                ]);

                $groupedMembers = $memberships
                    ->groupBy(
                        fn (StudentNucleusMembership $membership): string =>
                            $membership->person?->educationProfile?->school_workplace
                            ?: 'School not recorded'
                    )
                    ->sortKeysUsing(function (string $a, string $b): int {
                if ($a === 'School not recorded') {
                    return -1;
                }

                if ($b === 'School not recorded') {
                    return 1;
                }

                return strcasecmp($a, $b);
            });

                foreach ($groupedMembers as $school => $schoolMembers) {
                    foreach ($schoolMembers->values() as $index => $membership) {
                        $person = $membership->person;
                        $education = $person?->educationProfile;

                        fputcsv($output, [
                            $term->academic_year,
                            $term->semester,
                            $school,
                            $index + 1,
                            $person?->display_name ?? 'Unknown person',
                            $person?->locality ?? '',
                            $education?->course_strand ?? '',
                            $education?->grade_level ?? '',
                            $person?->contact_number ?? '',
                            $person?->email ?? '',
                            $membership->spiritual_condition ?? '',
                        ]);
                    }
                }

                fclose($output);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }
}
