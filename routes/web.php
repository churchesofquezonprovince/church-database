<?php

use App\Http\Controllers\StudentNucleusController;
use App\Http\Controllers\AttendanceSheetRecordController;
use App\Http\Controllers\AttendanceSheetParticipantController;
use App\Http\Controllers\AttendanceSheetStatusController;
use App\Http\Controllers\AttendanceReportExportController;
use App\Http\Controllers\AttendanceReportSessionController;
use App\Http\Controllers\PermanentMeetingOtherAttendeeController;
use App\Http\Controllers\AttendanceSheetController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\PeopleImportController;
use App\Http\Controllers\PrayerMeetingAttendanceController;

use App\Http\Controllers\LordsTableAttendanceController;

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/quezonprovinceactivities');


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
        Route::post('/{sheet}/participants', [AttendanceSheetParticipantController::class, 'store'])
            ->name('participants.store');

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

