<?php

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
        Route::get('/households', [ReportExportController::class, 'households'])->name('households');
        Route::get('/locality-summary', [ReportExportController::class, 'localitySummary'])->name('locality-summary');
        Route::get('/shepherding', [ReportExportController::class, 'shepherding'])->name('shepherding');
        Route::get('/missing-people', [ReportExportController::class, 'missingPeople'])->name('missing-people');
        Route::get('/missing-households', [ReportExportController::class, 'missingHouseholds'])->name('missing-households');
    });
