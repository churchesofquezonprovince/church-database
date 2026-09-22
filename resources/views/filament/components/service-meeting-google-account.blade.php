@php
    $serviceAccountStatus = \App\Services\ServiceMeetingGoogleAccount::status();
@endphp

<div class="mb-6 border-b border-gray-200 pb-6 dark:border-gray-700" style="margin-top: 1rem;">
    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
        Google Service Account
    </h3>

    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
        {{ $serviceAccountStatus['label'] }}.
        Use Test Folder Access to verify the connection.
    </p>

    @if (session('service_account_success'))
        <p role="status" class="mt-3 text-sm text-green-700 dark:text-green-300">
            {{ session('service_account_success') }}
        </p>
    @endif

    @if (session('service_account_error'))
        <p role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300">
            {{ session('service_account_error') }}
        </p>
    @endif

    @if ($serviceAccountStatus['email'])
        <div class="mt-4">
            <label for="service-account-email" class="block text-sm font-semibold">
                Service-account email
            </label>
            <input
                id="service-account-email"
                readonly
                value="{{ $serviceAccountStatus['email'] }}"
                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
            <button
                type="button"
                class="mt-2 text-sm font-semibold underline"
                onclick="navigator.clipboard.writeText(document.getElementById('service-account-email').value).then(() => { this.textContent = 'Copied'; }).catch(() => { document.getElementById('service-account-email').select(); this.textContent = 'Select and copy the email above'; })"
            >
                Copy Email
            </button>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Share the Google Drive folder with this email as Viewer.
            </p>
        </div>
    @endif

    <form
        method="POST"
        action="{{ url('/internal/service-meeting-google-account') }}"
        enctype="multipart/form-data"
        class="mt-5 space-y-3"
    >
        @csrf

        <label for="service-account-json" class="block text-sm font-semibold">
            Upload / Replace Credentials
        </label>
        <input
            id="service-account-json"
            name="service_account_json"
            type="file"
            accept=".json,application/json"
            required
            class="block w-full text-sm"
        >
        <details
            class="rounded-xl border border-gray-200 dark:border-gray-700"
        >
            <summary
                class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white"
            >
                How do I set up the Google service account?
            </summary>

            <div class="border-t border-gray-200 p-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200">
                <p>
                    Open
                    <a
                        href="https://console.cloud.google.com/iam-admin/serviceaccounts?project=coqp-database-calendar-503816"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold underline"
                    >Google Cloud → Service Accounts</a>.
                    Sign in with the Google account that manages your project.
                </p>

                <ol style="list-style: decimal; padding-left: 1.5rem; margin-top: 1rem;">
                    <li style="margin-bottom: .75rem;">
                        To use your existing calendar account, find and click
                        <strong style="overflow-wrap: anywhere;">coqp-calendar-sync@coqp-database-calendar-503816.iam.gserviceaccount.com</strong>.
                    </li>
                    <li style="margin-bottom: .75rem;">
                        Open its <strong>Keys</strong> tab.
                    </li>
                    <li style="margin-bottom: .75rem;">
                        If you already have its original JSON key file, use that
                        file and skip to step 5. Otherwise, click
                        <strong>Add Key → Create new key</strong>.
                    </li>
                    <li style="margin-bottom: .75rem;">
                        Select <strong>JSON</strong>, then click <strong>Create</strong>.
                        Your browser downloads a <strong>.json</strong> file,
                        usually into <strong>Downloads</strong>. Keep it private.
                        Google cannot download the same private key again later.
                    </li>
                    <li style="margin-bottom: .75rem;">
                        Open your <strong>Service Meeting Minutes folder</strong>
                        in Google Drive. Click <strong>Share</strong> and give
                        the same service-account email <strong>Viewer</strong>
                        access. If it already has access, leave it there.
                    </li>
                    <li style="margin-bottom: .75rem;">
                        Return to <strong>Developer Options → Service Meeting
                        Minutes Setup → Google Service Account</strong>.
                    </li>
                    <li style="margin-bottom: .75rem;">
                        Under <strong>Upload / Replace Credentials</strong>,
                        click <strong>Choose File</strong> and select the
                        <strong>.json</strong> file.
                    </li>
                    <li style="margin-bottom: .75rem;">
                        Click <strong>Verify &amp; Save Credentials</strong>.
                        The website checks access to the saved folder before
                        switching accounts.
                    </li>
                    <li>
                        After the success message, check that the displayed email
                        is <strong>coqp-calendar-sync@…</strong>, then reopen
                        <strong>Posts → Service Meeting Minutes</strong>.
                    </li>
                </ol>

                <p style="margin-top: 1rem;">
                    <strong>Keep existing keys for now:</strong> calendar sync
                    may still use one of them. Upload the JSON only through this
                    setup form. The private key is never displayed.
                </p>
            </div>
        </details>

        <x-filament::button type="submit">
            Verify &amp; Save Credentials
        </x-filament::button>
    </form>

    <div class="mt-4">
        <x-filament::button
            type="button"
            color="gray"
            wire:click="testServiceMeetingGoogleAccount"
            wire:loading.attr="disabled"
        >
            Test Folder Access
        </x-filament::button>
    </div>
</div>
