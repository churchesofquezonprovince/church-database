<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProblemReportController;
Route::post('/problem-reports/submit', [ProblemReportController::class, 'store'])
    ->middleware('throttle:5,10')->name('problem-reports.store');
Route::get('/problem-reports/{id}/screenshot', [ProblemReportController::class, 'screenshot'])
    ->whereNumber('id')->name('problem-reports.screenshot');
