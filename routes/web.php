<?php

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

