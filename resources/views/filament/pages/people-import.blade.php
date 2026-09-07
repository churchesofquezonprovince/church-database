<x-filament-panels::page>
    <div class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm dark:border-primary-900 dark:from-gray-900 dark:to-gray-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                Churches of Quezon Database
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                People CSV Import
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Upload the People Import CSV template. Validate first before importing real records.
            </p>
        </div>

        @if (session('import_status') === 'validated')
            <div class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800 shadow-sm dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">CSV validation passed.</p>
                <p class="mt-1 text-sm">
                    Rows found: {{ session('import_summary.rows_found') }}. No records were saved because you selected validate only.
                </p>
            </div>
        @endif

        @if (session('import_status') === 'imported')
            <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 text-primary-800 shadow-sm dark:border-primary-900 dark:bg-primary-950 dark:text-primary-100">
                <p class="font-bold">Import completed.</p>
                <p class="mt-1 text-sm">
                    Imported {{ session('import_summary.rows_imported') }} record(s).
                </p>
            </div>
        @endif

        @if (session('import_status') === 'failed')
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">CSV validation failed.</p>
                <p class="mt-1 text-sm">
                    No records were imported. Fix the errors below, then upload again.
                </p>

                <ul class="mt-4 list-disc space-y-1 pl-5 text-sm">
                    @foreach (session('import_errors', []) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Upload error.</p>

                <ul class="mt-4 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 xl:col-span-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Upload CSV
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Use CSV UTF-8 exported from Excel. Maximum file size is 5 MB.
                </p>

                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.imports.people') }}"
                    enctype="multipart/form-data"
                    class="mt-6 space-y-5"
                >
                    @csrf

                    <div>
                        <label for="csv_file" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                            CSV File
                        </label>

                        <input
                            id="csv_file"
                            name="csv_file"
                            type="file"
                            accept=".csv,text/csv"
                            required
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm file:mr-4 file:rounded-lg file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            name="action"
                            value="validate"
                            class="inline-flex items-center justify-center rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50 dark:border-primary-900 dark:bg-gray-900 dark:text-primary-200 dark:hover:bg-primary-950"
                        >
                            Validate Only
                        </button>

                        <button
                            type="submit"
                            name="action"
                            value="import"
                            onclick="return confirm('Import this CSV into the database? Make sure you already made a backup.');"
                            class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                        >
                            Validate and Import
                        </button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 text-indigo-800 shadow-sm dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-100">
                    <p class="font-bold">Step 1</p>
                    <p class="mt-1 text-sm">Download the template, open it in Excel, then save as CSV UTF-8 or CSV (Delimited).</p>

                    <a
                        href="{{ route('quezonprovinceactivities.exports.people-import-template') }}"
                        class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                    >
                        Download Template
                    </a>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <p class="font-bold">Important</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        <li>Required: First Name, Last Name, Sex, Locality, and Status.</li>
                        <li>Locality must already be configured in Province Setup, including outside-province Localities.</li>
                        <li>Birthdate must be YYYY-MM-DD. Baptism date may be YYYY, YYYY-MM, or YYYY-MM-DD.</li>
                        <li>Use Validate Only first.</li>
                        <li>Backup before real import.</li>
                        <li>Shepherd and emergency contact names must already exist in People records.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
