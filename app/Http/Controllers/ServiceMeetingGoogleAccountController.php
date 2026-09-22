<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class ServiceMeetingGoogleAccountController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        return redirect(\App\Filament\Pages\DeveloperOptions::getUrl().'#google-integrations')
            ->with('google_setup_error', 'Use Add Account and select the account under Google Integrations Setup. Existing accounts were kept.');
    }
}
