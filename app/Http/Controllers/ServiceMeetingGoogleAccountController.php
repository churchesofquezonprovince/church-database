<?php

namespace App\Http\Controllers;

use App\Filament\Pages\DeveloperOptions;
use App\Models\DriveMeetingDocument;
use App\Services\ServiceMeetingGoogleAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServiceMeetingGoogleAccountController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validator = validator($request->all(), [
            'service_account_json' => ['required', 'file', 'max:64'],
        ]);

        if ($validator->fails()) {
            return redirect(DeveloperOptions::getUrl())->with(
                'service_account_error',
                'Choose a service-account JSON file no larger than 64 KB.'
            );
        }

        $file = $request->file('service_account_json');
        $temporaryPath = $file->getRealPath();
        $credentials = null;

        try {
            $json = file_get_contents($temporaryPath);
            $credentials = ServiceMeetingGoogleAccount::credentials($json);
        } catch (\Throwable) {
            return redirect(DeveloperOptions::getUrl())->with(
                'service_account_error',
                'This is not a valid Google service-account JSON key. The active credentials were kept.'
            );
        } finally {
            // Remove the PHP upload once its contents have been read.
            if ($temporaryPath && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }

        try {
            ServiceMeetingGoogleAccount::testFolder($credentials);
        } catch (\Throwable) {
            return redirect(DeveloperOptions::getUrl())->with(
                'service_account_error',
                'Folder access could not be verified. Share the saved folder with the uploaded account email as Viewer, enable the Drive API in its project, and retry. The active credentials were kept.'
            );
        }

        try {
            ServiceMeetingGoogleAccount::install($credentials);
        } catch (\Throwable) {
            return redirect(DeveloperOptions::getUrl())->with(
                'service_account_error',
                'The verified credentials could not be stored. Check application storage permissions.'
            );
        }

        Cache::forget(DriveMeetingDocument::documentCacheKey());
        Cache::forget('service_meeting_docs');

        return redirect(DeveloperOptions::getUrl())->with(
            'service_account_success',
            'Service account saved and folder access verified. Reopen Service Meeting Minutes to refresh its documents.'
        );
    }
}
