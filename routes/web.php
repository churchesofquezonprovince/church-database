<?php

use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceReportExportController;
use App\Http\Controllers\AttendanceSheetController;
use App\Http\Controllers\LordsTableAttendanceController;
use App\Http\Controllers\PrayerMeetingAttendanceController;
use App\Http\Controllers\AttendanceSheetParticipantController;
use App\Http\Controllers\PeopleImportController;
use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth'])
    ->prefix('exports/church-database')
    ->name('church-database.exports.')
    ->group(function (): void {
        Route::get('/people', [ReportExportController::class, 'people'])->name('people');
        Route::get('/people-import-template', [ReportExportController::class, 'peopleImportTemplate'])->name('people-import-template');
        Route::get('/households', [ReportExportController::class, 'households'])->name('households');
        Route::get('/locality-summary', [ReportExportController::class, 'localitySummary'])->name('locality-summary');
        Route::get('/shepherding', [ReportExportController::class, 'shepherding'])->name('shepherding');
        Route::get('/missing-people', [ReportExportController::class, 'missingPeople'])->name('missing-people');
        Route::get('/missing-households', [ReportExportController::class, 'missingHouseholds'])->name('missing-households');
    });


Route::middleware(['auth'])
    ->prefix('imports/church-database')
    ->name('church-database.imports.')
    ->group(function (): void {
        Route::post('/people', [PeopleImportController::class, 'import'])->name('people');
    });


Route::middleware(['auth'])
    ->prefix('attendance/church-database')
    ->name('church-database.attendance-sheets.')
    ->group(function (): void {
        Route::post('/sheets', [AttendanceSheetController::class, 'store'])->name('store');
        Route::post('/sheets/{sheet}/participants', [AttendanceSheetParticipantController::class, 'store'])->name('participants.store');
        Route::delete('/sheets/{sheet}/participants/{participant}', [AttendanceSheetParticipantController::class, 'destroy'])->name('participants.destroy');
        Route::post('/sessions/{session}/records', [AttendanceRecordController::class, 'store'])->name('records.store');
        Route::get('/reports/export', [AttendanceReportExportController::class, 'export'])->name('reports.export');
        Route::get('/reports/print', [AttendanceReportExportController::class, 'print'])->name('reports.print');
        Route::post('/lords-table', [LordsTableAttendanceController::class, 'store'])->name('lords-table.store');
        Route::post('/prayer-meeting', [PrayerMeetingAttendanceController::class, 'store'])->name('prayer-meeting.store');
    });
