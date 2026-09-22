@php
    $googleAccounts = \App\Services\GoogleIntegrationSettings::accounts();
    $googleCalendarRows = array_values((array) \App\Services\GoogleIntegrationSettings::get('services.google_calendar.calendars', []));
    $legacyCalendar = (string) \App\Services\GoogleIntegrationSettings::get('services.google_calendar.calendar_id');
    if ($legacyCalendar !== '' && ! in_array($legacyCalendar, array_column($googleCalendarRows, 'id'), true)) {
        $googleCalendarRows[] = ['name' => 'Default Calendar', 'id' => $legacyCalendar, 'color' => '#3b82f6'];
    }
    if ($googleCalendarRows === []) {
        $googleCalendarRows[] = ['name' => '', 'id' => '', 'color' => '#3b82f6'];
    }
@endphp
<details id="google-integrations" class="group rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900" open>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5">
        <div>
            <h2 class="text-lg font-bold text-gray-950 dark:text-white">Google Integrations Setup</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage accounts, calendars, and the Children’s Work spreadsheet.</p>
        </div>
        <x-filament::icon icon="heroicon-m-chevron-down" class="h-5 w-5 shrink-0 text-gray-400" />
    </summary>
    <div class="coqp-google-setup border-t border-gray-200 px-6 pb-6 pt-5 dark:border-gray-700">
        <style>
            .coqp-google-setup { padding-top:1.25rem; }
            .coqp-google-setup details { border:1px solid #64748b55;border-radius:.8rem;margin-top:1rem; }
            .coqp-google-setup details>summary { cursor:pointer;padding:1rem;font-weight:700; }
            .coqp-google-setup .google-body { padding:1rem;border-top:1px solid #64748b55; }
            .coqp-google-setup label { display:block;font-weight:600;margin-top:1rem; }
            .coqp-google-setup input:not([type=hidden]),.coqp-google-setup select { width:100%;padding:.65rem;border:1px solid #64748b88;border-radius:.5rem;background:transparent;color:inherit;margin-top:.4rem; }
            .coqp-google-setup option { background:#fff;color:#172033; }
            .dark .coqp-google-setup option { background:#18181b;color:#fff; }
            .coqp-google-setup button { border:1px solid #64748b88;border-radius:.5rem;padding:.6rem 1rem;margin-top:1rem;font-weight:600; }
            .coqp-google-setup button[type=submit] { background:#2563eb;color:white;border-color:#2563eb; }
            .coqp-google-setup :is(input,select,button,summary,a):focus-visible { outline:3px solid #3b82f6;outline-offset:2px; }
            .coqp-google-setup table { width:100%;border-collapse:collapse;min-width:700px; }
            .coqp-google-setup th,.coqp-google-setup td { padding:.5rem;text-align:left;vertical-align:top; }
            .coqp-google-setup p { margin-top:.65rem;overflow-wrap:anywhere; }
            .coqp-google-setup .google-help { color:#64748b; }
            .dark .coqp-google-setup .google-help { color:#cbd5e1; }
        </style>
        @if(session('google_setup_success'))
            <p data-coqp-flash="success" data-coqp-flash-id="7149ff56956c3d28" data-coqp-keep="false" role="status">{{ session('google_setup_success') }}</p>
        @endif
        @if(session('google_setup_error'))
            <p data-coqp-flash="danger" data-coqp-flash-id="2fe238aac3e3e4f4" data-coqp-keep="false" role="alert" class="font-semibold">{{ session('google_setup_error') }}</p>
        @endif
        <p class="google-help">Current server settings remain in use until you save a section. Adding an account does not switch any integration.</p>
        <details>
            <summary>Google Service Accounts</summary>
            <div class="google-body">
                <ul style="padding-left:1.5rem;list-style:disc;overflow-wrap:anywhere;">
                    @forelse($googleAccounts as $accountLabel)
                        <li>{{ $accountLabel }}</li>
                    @empty
                        <li>No readable accounts found. Add an account below.</li>
                    @endforelse
                </ul>
                <p class="google-help">Existing entries refer to the current server credential files. Entries with the same email may use the same Google account.</p>
                <form method="POST" action="{{ url('/internal/google-integrations') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="feature" value="account">
                    <label for="google-account-label">Account name</label>
                    <input id="google-account-label" name="label" maxlength="120" required placeholder="For example: Calendar and Sheets">
                    <label for="google-account-upload">JSON key file</label>
                    <input id="google-account-upload" name="credentials" type="file" accept=".json,application/json" required>
                    <p class="google-help">The key is stored privately. The account email is read from the file. Resource access is checked when you save an enabled integration.</p>
                    <button type="submit">Add Account</button>
                </form>
                <details>
                    <summary>How do I add a different Google account?</summary>
                    <div class="google-body">
                        <ol style="list-style:decimal;padding-left:1.5rem;">
                            <li>Open <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener noreferrer" style="text-decoration:underline;">Google Cloud → Service Accounts</a> and choose your project.</li>
                            <li>Select the service account you want to use. Use its existing JSON key if you already have it.</li>
                            <li>If you need a new key, open Keys → Add Key → Create new key → JSON → Create. Keep the downloaded file private.</li>
                            <li>Enter an account name above, choose that JSON file, then click Add Account.</li>
                            <li>Share the Drive folder, calendars, or spreadsheet with the email displayed in the account list.</li>
                            <li>Choose the account in the relevant section below, then save that section.</li>
                        </ol>
                        <p>Minutes needs folder Viewer access. Calendar editing needs permission to change events. Children’s Work needs spreadsheet Editor access. Enable the corresponding Drive, Calendar, or Sheets API in the account’s Google Cloud project.</p>
                    </div>
                </details>
            </div>
        </details>
        @foreach(['minutes' => 'Service Meeting Minutes Account', 'calendar' => 'Calendars / Schedules', 'children' => 'Children’s Work / Google Sheets'] as $feature => $heading)
            @php $savedGoogleSettings = \App\Services\GoogleIntegrationSettings::saved($feature); @endphp
            <details>
                <summary>{{ $heading }}</summary>
                <div class="google-body">
                    <p class="google-help">{{ $savedGoogleSettings === null ? 'Using current server defaults.' : 'Using settings saved in Developer Options.' }}</p>
                    <form method="POST" action="{{ url('/internal/google-integrations') }}">
                        @csrf
                        <input type="hidden" name="feature" value="{{ $feature }}">
                        <label for="google-account-{{ $feature }}">Service account</label>
                        <select id="google-account-{{ $feature }}" name="account">
                            <option value="">Use this integration’s existing account</option>
                            @foreach($googleAccounts as $id => $accountLabel)
                                <option value="{{ $id }}" @selected(($savedGoogleSettings['account'] ?? '') === $id)>{{ $accountLabel }}</option>
                            @endforeach
                        </select>
                        @if($feature === 'minutes')
                            <p>Uses the folder saved under Service Meeting Minutes Setup below.</p>
                        @else
                            @php $prefix = \App\Services\GoogleIntegrationSettings::PREFIXES[$feature]; @endphp
                            <label for="google-enabled-{{ $feature }}">Sync</label>
                            <select id="google-enabled-{{ $feature }}" name="enabled">
                                <option value="1" @selected(\App\Services\GoogleIntegrationSettings::get($prefix.'.enabled'))>Enabled</option>
                                <option value="0" @selected(! \App\Services\GoogleIntegrationSettings::get($prefix.'.enabled'))>Disabled</option>
                            </select>
                            @if($feature === 'calendar')
                                <p class="google-help">Find each Calendar ID in Google Calendar → Settings → the calendar → Integrate calendar. Use the ID, not its public URL. Removing a row stops syncing that calendar; existing website events are kept.</p>
                                <div style="overflow-x:auto;">
                                    <table>
                                        <thead><tr><th>Name</th><th>Calendar ID</th><th>Color</th><th>Action</th></tr></thead>
                                        <tbody id="google-calendar-rows" data-next="{{ count($googleCalendarRows) }}">
                                            @foreach($googleCalendarRows as $index => $calendar)
                                                <tr>
                                                    <td><input aria-label="Calendar name" name="calendars[{{ $index }}][name]" value="{{ $calendar['name'] ?? '' }}" maxlength="120"></td>
                                                    <td><input aria-label="Calendar ID" name="calendars[{{ $index }}][id]" value="{{ $calendar['id'] ?? '' }}" maxlength="300"></td>
                                                    <td><input aria-label="Calendar color" name="calendars[{{ $index }}][color]" type="color" value="{{ $calendar['color'] ?? '#3b82f6' }}"></td>
                                                    <td><button type="button" onclick="this.closest('tr').remove()">Remove</button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <template id="google-calendar-row-template">
                                    <tr>
                                        <td><input aria-label="Calendar name" data-field="name" maxlength="120"></td>
                                        <td><input aria-label="Calendar ID" data-field="id" maxlength="300"></td>
                                        <td><input aria-label="Calendar color" data-field="color" type="color" value="#3b82f6"></td>
                                        <td><button type="button" onclick="this.closest('tr').remove()">Remove</button></td>
                                    </tr>
                                </template>
                                <button type="button" onclick="const body = document.getElementById('google-calendar-rows'); if(body.rows.length >= 20) { window.coqpToast('Up to 20 calendars may be configured.', 'warning'); return; } const row = document.getElementById('google-calendar-row-template').content.cloneNode(true); const index = Number(body.dataset.next); row.querySelectorAll('[data-field]').forEach(input => { input.name = 'calendars[' + index + '][' + input.dataset.field + ']'; }); body.dataset.next = index + 1; body.appendChild(row);">Add Calendar</button>
                            @else
                                <label for="google-spreadsheet">Spreadsheet ID</label>
                                <input id="google-spreadsheet" name="spreadsheet_id" value="{{ \App\Services\GoogleIntegrationSettings::get($prefix.'.spreadsheet_id') }}" maxlength="200">
                                <p class="google-help">Copy the part between /d/ and /edit in the Google Sheets URL.</p>
                                <label for="google-sheet-tab">Sheet tab name</label>
                                <input id="google-sheet-tab" name="sheet_name" value="{{ \App\Services\GoogleIntegrationSettings::get($prefix.'.sheet_name') }}" maxlength="100">
                                <p class="google-help">Leave blank to keep the current automatic tab selection.</p>
                                <label for="google-header-row">Header row</label>
                                <input id="google-header-row" name="header_row" type="number" min="1" max="10000" value="{{ \App\Services\GoogleIntegrationSettings::get($prefix.'.header_row', 1) }}" required>
                                <p class="google-help">Changing the spreadsheet changes where scheduled sync reads and writes lesson data. Existing website lessons are kept.</p>
                            @endif
                        @endif
                        <div><button type="submit">Verify &amp; Save Settings</button></div>
                        <p class="google-help">Enabled connections are checked for read access before saving. Disabled sync settings are saved without a connection test.</p>
                    </form>
                </div>
            </details>
        @endforeach
        @include('filament.components.rclone-backup-settings')
    </div>
</details>
