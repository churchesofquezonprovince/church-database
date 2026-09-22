<?php

use App\Http\Controllers\CampusWorkStudentCenterController;

use App\Http\Controllers\CampusContactImportController;
use App\Http\Controllers\CampusContactController;
use App\Http\Controllers\GospelContactController;
use App\Http\Controllers\CampusWorkDashboardController;
use App\Http\Controllers\CampusWorkActivityController;
use App\Http\Controllers\CampusWorkActivityAttendanceController;
use App\Http\Controllers\StudentNucleusExportController;
use App\Http\Controllers\CampusWorkTermController;
use App\Http\Controllers\StudentNucleusPrintController;
use App\Http\Controllers\StudentNucleusController;
use App\Http\Controllers\AttendanceSheetRecordController;
use App\Http\Controllers\AttendanceSheetParticipantController;
use App\Http\Controllers\AttendanceSheetStatusController;
use App\Http\Controllers\AttendanceReportExportController;
use App\Http\Controllers\AttendanceReportSessionController;
use App\Http\Controllers\AttendanceMeetingResponseController;
use App\Http\Controllers\AttendanceMeetingResponseParticipantController;
use App\Http\Controllers\AttendanceMeetingResponsePromotionController;
use App\Http\Controllers\PermanentMeetingOtherAttendeeController;
use App\Http\Controllers\AttendanceSheetController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\PeopleImportController;
use App\Http\Controllers\PrayerMeetingAttendanceController;
use App\Http\Controllers\PrayerMeetingItemController;
use App\Http\Controllers\PublicMeetingFormController;
use App\Http\Controllers\PublicShepherdingSubmissionController;
use App\Http\Controllers\PublicImmichAlbumController;

use App\Http\Controllers\LordsTableAttendanceController;

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->get('/quezonprovinceactivities/test-site', function () {
        return view('test-site.manila-clock');
    })
    ->name('quezonprovinceactivities.test-site');

/*
 * ============================================================
 * SHORT PUBLIC MEETING LINKS
 * ============================================================
 *
 * Example:
 * https://m.overcomers.win/9-4-26-ceficoccampusmeeting
 */
Route::domain('m.overcomers.win')
    ->middleware(['web'])
    ->group(function (): void {
        Route::get(
            '/{slug}/name-search',
            [
                PublicMeetingFormController::class,
                'search',
            ]
        )
            ->middleware('throttle:30,1')
            ->name('meeting.short.search');

        Route::post(
            '/{slug}/database-autofill',
            [
                PublicMeetingFormController::class,
                'autofill',
            ]
        )
            ->middleware('throttle:60,1')
            ->name('meeting.short.autofill');

        Route::get(
            '/{slug}',
            [
                PublicMeetingFormController::class,
                'show',
            ]
        )
            ->name('meeting.short.show');

        Route::post(
            '/{slug}',
            [
                PublicMeetingFormController::class,
                'store',
            ]
        )
            ->middleware('throttle:60,1')
            ->name('meeting.short.store');
    });


Route::redirect('/', '/quezonprovinceactivities');


/*
|--------------------------------------------------------------------------
| PUBLIC SHEPHERDING RECORD DASHBOARD
|--------------------------------------------------------------------------
|
| Submissions are staged for Admin review. They do not become canonical
| Shepherding Records until approved from Shepherding Records.
|
*/
Route::middleware([
    'web',
    'throttle:60,1',
])
    ->get(
        '/shepherding',
        [
            PublicShepherdingSubmissionController::class,
            'show',
        ]
    )
    ->name('shepherding.public.show');

Route::middleware([
    'web',
    'throttle:10,1',
])
    ->post(
        '/shepherding',
        [
            PublicShepherdingSubmissionController::class,
            'store',
        ]
    )
    ->name('shepherding.public.store');



/*
|--------------------------------------------------------------------------
| PUBLIC IMMICH ALBUMS
|--------------------------------------------------------------------------
|
| Public, read-only gallery. Only albums with an active Immich
| Shared Link are exposed here.
|
*/
Route::middleware([
    'web',
    'throttle:120,1',
])
    ->get(
        '/albums',
        PublicImmichAlbumController::class
    )
    ->name('immich-albums.public');




Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets')
    ->name('quezonprovinceactivities.attendance-sheets.')
    ->group(function (): void {
        Route::post('/lords-table', [LordsTableAttendanceController::class, 'store'])
            ->name('lords-table.store');

        Route::post('/prayer-meeting', [PrayerMeetingAttendanceController::class, 'store'])
            ->name('prayer-meeting.store');
    });



Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/posts/prayer-meeting-items')
    ->name('quezonprovinceactivities.posts.prayer-meeting-items.')
    ->group(function (): void {
        Route::post('/lines', [PrayerMeetingItemController::class, 'storeLine'])
            ->name('lines.store');

        Route::patch('/lines/{line}', [PrayerMeetingItemController::class, 'updateLine'])
            ->name('lines.update');

        Route::delete('/lines/{line}', [PrayerMeetingItemController::class, 'destroyLine'])
            ->name('lines.destroy');

        Route::post('/{item}/snapshots', [PrayerMeetingItemController::class, 'storeSnapshot'])
            ->name('snapshots.store');

        Route::get('/snapshots/{snapshot}', [PrayerMeetingItemController::class, 'printSnapshot'])
            ->name('snapshots.print');

        Route::delete('/snapshots/{snapshot}', [PrayerMeetingItemController::class, 'destroySnapshot'])
            ->name('snapshots.destroy');


        Route::get('/print', [PrayerMeetingItemController::class, 'print'])
            ->name('print');
    });

Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/imports')
    ->name('quezonprovinceactivities.imports.')
    ->group(function (): void {
        Route::post('/people', [PeopleImportController::class, 'store'])
            ->name('people');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/exports')
    ->name('quezonprovinceactivities.exports.')
    ->group(function (): void {
        Route::get('/people-import-template', function () {
            $headers = [
                'firstname',
                'middlename',
                'lastname',
                'suffix',
                'sex',
                'nickname',
                'birthdate',
                'birthplace',
                'locality',
                'permanent_address',
                'home_address',
                'geocoordinates',
                'email',
                'contact_number',
                'facebook_account',
                'school_workplace',
                'workplace',
                'category',
                'baptism_date',
                'service',
                'status',
            ];

            return response()->streamDownload(function () use ($headers): void {
                $handle = fopen('php://output', 'w');

                fputcsv($handle, $headers);
                fputcsv($handle, [
                    'Juan',
                    'Reyes',
                    'Santos',
                    '',
                    'Male',
                    'Juan',
                    '2000-01-31',
                    'Lucena City',
                    'Lucena City',
                    'Sample permanent address',
                    'Sample home address',
                    '13.9414, 121.6236',
                    'juan@example.com',
                    '09171234567',
                    'https://www.facebook.com/juan.santos',
                    'Southern Luzon State University',
                    '',
                    'Student',
                    '2024-01',
                    'Young People',
                    'Active',
                ]);

                fclose($handle);
            }, 'people-import-template.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        })->name('people-import-template');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/exports')
    ->name('quezonprovinceactivities.exports.')
    ->group(function (): void {
        Route::get('/people', [ReportExportController::class, 'people'])
            ->name('people');

        Route::get('/households', [ReportExportController::class, 'households'])
            ->name('households');

        Route::get('/locality-summary', [ReportExportController::class, 'localitySummary'])
            ->name('locality-summary');

        Route::get('/shepherding', [ReportExportController::class, 'shepherding'])
            ->name('shepherding');

        Route::get('/missing-people', [ReportExportController::class, 'missingPeople'])
            ->name('missing-people');

        Route::get('/missing-households', [ReportExportController::class, 'missingHouseholds'])
            ->name('missing-households');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets')
    ->name('quezonprovinceactivities.attendance-sheets.')
    ->group(function (): void {
        Route::post('/', [AttendanceSheetController::class, 'store'])
            ->name('store');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/permanent-meeting')
    ->name('quezonprovinceactivities.attendance-sheets.permanent-meeting.')
    ->group(function (): void {
        Route::post('/{session}/other-attendees', [PermanentMeetingOtherAttendeeController::class, 'store'])
            ->name('other-attendees.store');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/reports')
    ->name('quezonprovinceactivities.attendance-sheets.reports.')
    ->group(function (): void {
        Route::get('/print', [AttendanceReportExportController::class, 'print'])
            ->name('print');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/reports')
    ->name('quezonprovinceactivities.attendance-sheets.reports.')
    ->group(function (): void {
        Route::get('/export', [AttendanceReportExportController::class, 'export'])
            ->name('export');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/reports')
    ->name('quezonprovinceactivities.attendance-sheets.reports.')
    ->group(function (): void {
        Route::delete('/sessions/{attendanceSession}', [AttendanceReportSessionController::class, 'destroy'])
            ->name('sessions.destroy');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/permanent-meeting')
    ->name('quezonprovinceactivities.attendance-sheets.permanent-meeting.')
    ->group(function (): void {
        Route::delete('/{session}/other-attendees/{person}', [PermanentMeetingOtherAttendeeController::class, 'destroy'])
            ->name('other-attendees.destroy');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/sheets')
    ->name('quezonprovinceactivities.attendance-sheets.sheets.')
    ->group(function (): void {
        Route::post('/{sheet}/toggle-active', [AttendanceSheetStatusController::class, 'toggleActive'])
            ->name('toggle-active');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/sheets')
    ->name('quezonprovinceactivities.attendance-sheets.sheets.')
    ->group(function (): void {
        Route::delete('/{sheet}', [AttendanceSheetStatusController::class, 'destroy'])
            ->name('destroy');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/sheets')
    ->name('quezonprovinceactivities.attendance-sheets.sheets.')
    ->group(function (): void {
        Route::patch('/{sheet}', [AttendanceSheetStatusController::class, 'update'])
            ->name('update');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets')
    ->name('quezonprovinceactivities.attendance-sheets.')
    ->group(function (): void {
        Route::post(
            '/{sheet}/sessions',
            [
                \App\Http\Controllers\AttendanceSheetSessionController::class,
                'store',
            ]
        )->name('sessions.store');

        Route::delete(
            '/{sheet}/sessions/{session}',
            [
                \App\Http\Controllers\AttendanceSheetSessionController::class,
                'destroy',
            ]
        )->name('sessions.destroy');

        Route::post('/{sheet}/participants', [AttendanceSheetParticipantController::class, 'store'])
            ->name('participants.store');

        Route::delete(
            '/{sheet}/participants',
            [
                AttendanceSheetParticipantController::class,
                'destroyAll',
            ]
        )->name('participants.destroy-all');

        Route::delete('/{sheet}/participants/{participant}', [AttendanceSheetParticipantController::class, 'destroy'])
            ->name('participants.destroy');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/attendance-sheets/records')
    ->name('quezonprovinceactivities.attendance-sheets.records.')
    ->group(function (): void {
        Route::post('/{session}', [AttendanceSheetRecordController::class, 'store'])
            ->name('store');
    });

Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/{response}/promote-to-campus',
        [
            AttendanceMeetingResponsePromotionController::class,
            'promoteGuestToCampus',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.promote-to-campus'
    );


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/campus-work/student-nucleus')
    ->name('quezonprovinceactivities.campus-work.student-nucleus.')
    ->group(function (): void {
        Route::post('/', [StudentNucleusController::class, 'store'])
            ->name('store');

        Route::patch('/{membership}', [StudentNucleusController::class, 'update'])
            ->name('update');

        Route::delete('/{membership}', [StudentNucleusController::class, 'destroy'])
            ->name('destroy');
    });


Route::middleware(['web', 'auth'])
    ->get(
        'quezonprovinceactivities/campus-work/student-nucleus/print/{term}',
        StudentNucleusPrintController::class
    )
    ->name('quezonprovinceactivities.campus-work.student-nucleus.print');

Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/campus-work/terms')
    ->name('quezonprovinceactivities.campus-work.terms.')
    ->group(function (): void {
        Route::post('/', [CampusWorkTermController::class, 'store'])
            ->name('store');

        Route::post('/{term}/activate', [CampusWorkTermController::class, 'activate'])
            ->name('activate');

        Route::post('/{term}/copy-members', [CampusWorkTermController::class, 'copyMembers'])
            ->name('copy-members');

        Route::post(
            '/{term}/copy-contacts',
            [CampusWorkTermController::class, 'copyContacts']
        )->name('copy-contacts');

        Route::post('/{term}/archive', [CampusWorkTermController::class, 'archive'])
            ->name('archive');

        Route::post('/{term}/restore', [CampusWorkTermController::class, 'restore'])
            ->name('restore');
    });


Route::middleware(['web', 'auth'])
    ->get(
        'quezonprovinceactivities/campus-work/student-nucleus/export/{term}',
        StudentNucleusExportController::class
    )
    ->name('quezonprovinceactivities.campus-work.student-nucleus.export');


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/campus-work/activities')
    ->name('quezonprovinceactivities.campus-work.activities.')
    ->group(function (): void {
        Route::post('/', [CampusWorkActivityController::class, 'store'])
            ->name('store');

        Route::patch('/{activity}', [CampusWorkActivityController::class, 'update'])
            ->name('update');

        Route::post(
            '/{activity}/attendance/one-time',
            [
                CampusWorkActivityAttendanceController::class,
                'generateOneTime',
            ]
        )->name('attendance.one-time');

        Route::post(
            '/{activity}/attendance/recurring',
            [
                CampusWorkActivityAttendanceController::class,
                'generateRecurring',
            ]
        )->name('attendance.recurring');

        Route::post(
            '/{activity}/attendance/link',
            [
                CampusWorkActivityAttendanceController::class,
                'linkExisting',
            ]
        )->name('attendance.link');

        Route::delete(
            '/{activity}/attendance/link',
            [
                CampusWorkActivityAttendanceController::class,
                'unlink',
            ]
        )->name('attendance.unlink');

        Route::delete('/{activity}', [CampusWorkActivityController::class, 'destroy'])
            ->name('destroy');
    });



Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/campus-work/dashboard')
    ->name('quezonprovinceactivities.campus-work.dashboard.')
    ->group(function (): void {
        Route::patch('/main-book', [CampusWorkDashboardController::class, 'updateMainBook'])
            ->name('main-book.update');

        Route::post('/readings', [CampusWorkDashboardController::class, 'storeReading'])
            ->name('readings.store');

        Route::patch('/readings/{item}', [CampusWorkDashboardController::class, 'updateReading'])
            ->name('readings.update');

        Route::delete('/readings/{item}', [CampusWorkDashboardController::class, 'destroyReading'])
            ->name('readings.destroy');
    });

Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/campus-work/contacts')
    ->name('quezonprovinceactivities.campus-work.contacts.')
    ->group(function (): void {
        Route::post(
            '/import',
            [CampusContactImportController::class, 'import']
        )->name('import');

        Route::get(
            '/import-template',
            [CampusContactImportController::class, 'template']
        )->name('import-template');
        Route::post('/existing-people', [CampusContactController::class, 'addExistingPeople'])
            ->name('existing-people.store');

        Route::post('/', [CampusContactController::class, 'store'])
            ->name('store');

        Route::patch('/{contact}', [CampusContactController::class, 'update'])
            ->name('update');

        Route::delete('/{contact}', [CampusContactController::class, 'destroy'])
            ->name('destroy');

        Route::post(
            '/{contact}/term-membership',
            [CampusContactController::class, 'addToTerm']
        )->name('term-membership.store');

        Route::delete(
            '/{contact}/term-membership',
            [CampusContactController::class, 'removeFromTerm']
        )->name('term-membership.destroy');

        Route::post('/{contact}/add-to-people', [CampusContactController::class, 'addToPeople'])
            ->name('add-to-people');

        Route::post('/{contact}/link-existing-person', [CampusContactController::class, 'linkExistingPerson'])
            ->name('link-existing-person');

        Route::delete(
            '/{contact}/unlink-person',
            [CampusContactController::class, 'unlinkPerson']
        )->name('unlink-person');

        Route::post('/{contact}/create-new-person-anyway', [CampusContactController::class, 'createNewPersonAnyway'])
            ->name('create-new-person-anyway');

    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/campus-work/contacts')
    ->name('quezonprovinceactivities.campus-work.contacts.')
    ->group(function (): void {
        Route::post('/', [CampusContactController::class, 'store'])
            ->name('store');

        Route::patch('/{contact}', [CampusContactController::class, 'update'])
            ->name('update');

        Route::delete('/{contact}', [CampusContactController::class, 'destroy'])
            ->name('destroy');

        Route::post('/{contact}/add-to-people', [CampusContactController::class, 'addToPeople'])
            ->name('add-to-people');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/gospel-work/contacts')
    ->name('quezonprovinceactivities.gospel-work.contacts.')
    ->group(function (): void {
        Route::post(
            '/existing-people',
            [GospelContactController::class, 'addExistingPeople']
        )->name('existing-people.store');

        Route::post(
            '/',
            [GospelContactController::class, 'store']
        )->name('store');

        Route::patch(
            '/{contact}',
            [GospelContactController::class, 'update']
        )->name('update');

        Route::delete(
            '/{contact}',
            [GospelContactController::class, 'destroy']
        )->name('destroy');

        Route::post(
            '/{contact}/add-to-people',
            [GospelContactController::class, 'addToPeople']
        )->name('add-to-people');

        Route::post(
            '/{contact}/link-existing-person',
            [GospelContactController::class, 'linkExistingPerson']
        )->name('link-existing-person');

        Route::post(
            '/{contact}/create-new-person-anyway',
            [GospelContactController::class, 'createNewPersonAnyway']
        )->name('create-new-person-anyway');
    });


Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/campus-work/student-center')
    ->name('quezonprovinceactivities.campus-work.student-center.')
    ->group(function (): void {
        Route::post('/', [CampusWorkStudentCenterController::class, 'store'])
            ->name('store');

        Route::patch('/{studentCenter}', [CampusWorkStudentCenterController::class, 'update'])
            ->name('update');

        Route::delete('/{studentCenter}', [CampusWorkStudentCenterController::class, 'destroy'])
            ->name('destroy');

        Route::post('/{studentCenter}/members', [CampusWorkStudentCenterController::class, 'storeMembers'])
            ->name('members.store');

        Route::delete('/{studentCenter}/members/{member}', [CampusWorkStudentCenterController::class, 'destroyMember'])
            ->name('members.destroy');
    });

// Children's Work public dashboard and protected lesson actions.
Route::middleware(['web'])
    ->get('/children-work', function () {
        $dashboardQuery = \App\Models\ChildrenWorkLesson::query()
            ->whereNotIn('status', ['draft', 'cancelled']);

$selectedLessonId = request()->integer('lesson');

$nextLesson = null;

if ($selectedLessonId > 0) {
    $nextLesson = (clone $dashboardQuery)
        ->whereKey($selectedLessonId)
        ->whereNotNull('scheduled_on')
        ->first();
}

if (! $nextLesson) {
    $nextLesson = (clone $dashboardQuery)
        ->whereNotNull('scheduled_on')
        ->whereDate('scheduled_on', '>=', today())
        ->orderBy('scheduled_on')
        ->orderBy('id')
        ->first();
}

        if (! $nextLesson) {
            $nextLesson = (clone $dashboardQuery)
                ->whereNotNull('scheduled_on')
                ->whereDate('scheduled_on', '<', today())
                ->orderByDesc('scheduled_on')
                ->orderByDesc('id')
                ->first();
        }

        $upcomingLessons = (clone $dashboardQuery)
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '>=', today())
            ->whereDate(
                'scheduled_on',
                '<=',
                now()->endOfMonth()->toDateString()
            )
->when(
    $nextLesson,
    fn ($query) => $query->where(
        'id',
        '!=',
        $nextLesson->id
    )
)
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->limit(8)
            ->get();

        $recentLessons = (clone $dashboardQuery)
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '<', today())
            ->when(
    $nextLesson,
    fn ($query) => $query->where(
        'id',
        '!=',
        $nextLesson->id
    )
)
            ->orderByDesc('scheduled_on')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

$pastLessons = (clone $dashboardQuery)
    ->whereNotNull('scheduled_on')
    ->whereDate('scheduled_on', '<', today())
    ->when(
        $nextLesson,
        fn ($query) => $query->where(
            'id',
            '!=',
            $nextLesson->id
        )
    )
    ->whereNotIn(
        'id',
        $recentLessons->pluck('id')
    )
    ->orderByDesc('scheduled_on')
    ->orderByDesc('id')
    ->get();

        $futureLessons = (clone $dashboardQuery)
            ->whereNotNull('scheduled_on')
            ->whereDate(
                'scheduled_on',
                '>',
                now()->endOfMonth()->toDateString()
            )
            ->when(
    $nextLesson,
    fn ($query) => $query->where(
        'id',
        '!=',
        $nextLesson->id
    )
)
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->limit(12)
            ->get();

        return view('children-work.dashboard', [
            'nextLesson' => $nextLesson,
            'upcomingLessons' => $upcomingLessons,
            'recentLessons' => $recentLessons,
            'futureLessons' => $futureLessons,
            'pastLessons' => $pastLessons,
        ]);
    })
    ->name('children-work.dashboard.public');

Route::middleware(['web', 'auth'])
    ->prefix('quezonprovinceactivities/children-work')
    ->name('quezonprovinceactivities.children-work.')
    ->group(function (): void {
        Route::post('/google-sheet/sync', [\App\Http\Controllers\ChildrenWorkLessonController::class, 'syncGoogleSheet'])
            ->name('google-sheet.sync');

        Route::post('/google-sheet/push', [\App\Http\Controllers\ChildrenWorkLessonController::class, 'pushGoogleSheet'])
            ->name('google-sheet.push');

        Route::post('/lessons', [\App\Http\Controllers\ChildrenWorkLessonController::class, 'store'])
            ->name('lessons.store');

        Route::patch('/lessons/{lesson}', [\App\Http\Controllers\ChildrenWorkLessonController::class, 'update'])
            ->name('lessons.update');

        Route::delete('/lessons/{lesson}', [\App\Http\Controllers\ChildrenWorkLessonController::class, 'destroy'])
            ->name('lessons.destroy');
    });



    /*
 * ============================================================
 * PHASE 26C — PUBLIC MEETING RESPONSE FORM
 * ============================================================
 *
 * No login required.
 */
    Route::middleware(['web'])
    ->group(function (): void {
        Route::get(
            '/meeting/{slug}/name-search',
            [
                PublicMeetingFormController::class,
                'search',
            ]
        )
            ->middleware('throttle:30,1')
            ->name('meeting.search');

        Route::post(
            '/meeting/{slug}/database-autofill',
            [
                PublicMeetingFormController::class,
                'autofill',
            ]
        )
            ->middleware('throttle:60,1')
            ->name('meeting.autofill');

        Route::get(
            '/meeting/{slug}',
            [
                PublicMeetingFormController::class,
                'show',
            ]
        )->name('meeting.show');

        Route::post(
            '/meeting/{slug}',
            [
                PublicMeetingFormController::class,
                'store',
            ]
        )
            ->middleware('throttle:60,1')
            ->name('meeting.store');
    });


    Route::middleware(['web', 'auth'])
    ->get(
        '/quezonprovinceactivities/attendance-meeting-responses/person-search',
        [
            AttendanceMeetingResponsePromotionController::class,
            'searchPeople',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.person-search'
    );


    Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/{response}/link-person',
        [
            AttendanceMeetingResponsePromotionController::class,
            'linkGuestToPerson',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.link-person'
    );


    Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/{response}/create-person',
        [
            AttendanceMeetingResponsePromotionController::class,
            'createGuestPerson',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.create-person'
    );

    Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/{response}/link-campus-person',
        [
            AttendanceMeetingResponsePromotionController::class,
            'linkCampusToPerson',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.link-campus-person'
    );

    Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/{response}/create-campus-person',
        [
            AttendanceMeetingResponsePromotionController::class,
            'createPersonFromCampus',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.create-campus-person'
    );

    Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/{response}/use-campus-linked-person',
        [
            AttendanceMeetingResponsePromotionController::class,
            'useCampusLinkedPerson',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.use-campus-linked-person'
    );

    Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/attendance-participants/bulk',
        [
            AttendanceMeetingResponseParticipantController::class,
            'bulkStore',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.attendance-participants.bulk'
    );

    Route::middleware(['web', 'auth'])
    ->post(
        '/quezonprovinceactivities/attendance-meeting-responses/{response}/attendance-participant',
        [
            AttendanceMeetingResponseParticipantController::class,
            'store',
        ]
    )
    ->name(
        'quezonprovinceactivities.attendance-meeting-responses.attendance-participant'
    );

    Route::middleware(['web', 'auth'])
->delete(
    '/quezonprovinceactivities/attendance-meeting-responses/{response}',
    [
        AttendanceMeetingResponseController::class,
        'destroy',
    ]
)
->name(
    'quezonprovinceactivities.attendance-meeting-responses.destroy'
);


require __DIR__.'/problem-reports.php';

// Admin-only credential upload for Service Meeting Minutes.
\Illuminate\Support\Facades\Route::post(
    '/internal/service-meeting-google-account',
    [\App\Http\Controllers\ServiceMeetingGoogleAccountController::class, 'store']
)->middleware(['auth', 'throttle:6,1'])
    ->name('service-meeting-google-account.store');

// Google Integrations Setup: admin authorization is enforced in the controller.
\Illuminate\Support\Facades\Route::post(
    '/internal/google-integrations',
    [\App\Http\Controllers\GoogleIntegrationSettingsController::class, 'store']
)->middleware(['auth', 'throttle:6,1'])->name('google-integrations.store');

\Illuminate\Support\Facades\Route::post(
    '/internal/rclone-backup-settings',
    [\App\Http\Controllers\RcloneBackupSettingsController::class, 'store']
)->middleware(['auth', 'throttle:6,1'])->name('rclone-backup-settings.store');
