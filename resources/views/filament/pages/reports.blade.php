<x-filament-panels::page>
    <div class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 to-white p-6 shadow-sm dark:border-primary-900 dark:from-gray-900 dark:to-gray-950">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                        Churches of Quezon Database
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        Reports
                    </h2>

                    <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                        Review church records, shepherding care, categories, groups, and missing data.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ \App\Filament\Resources\People\PersonResource::getUrl('index') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                    >
                        Open People
                    </a>

                    <a
                        href="{{ \App\Filament\Resources\Households\HouseholdResource::getUrl('index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-primary-200 bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50 dark:border-primary-900 dark:bg-gray-900 dark:text-primary-200 dark:hover:bg-primary-950"
                    >
                        Open Households
                    </a>
                </div>
            </div>
        </div>


        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    CSV Exports
                </h3>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Download records and reports for checking, encoding, printing, or sharing with authorized workers.
                </p>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <a href="{{ \App\Filament\Pages\PeopleImport::getUrl() }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                    Open People Import Page
                </a>

                <a href="{{ route('church-database.exports.people-import-template') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-200">
                    Download People Import Template
                </a>

                <a href="{{ route('church-database.exports.people') }}" class="rounded-xl border border-primary-200 bg-primary-50 p-4 font-semibold text-primary-700 transition hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">
                    Export People CSV
                </a>

                <a href="{{ route('church-database.exports.households') }}" class="rounded-xl border border-purple-200 bg-purple-50 p-4 font-semibold text-purple-700 transition hover:bg-purple-100 dark:border-purple-900 dark:bg-purple-950 dark:text-purple-200">
                    Export Households CSV
                </a>

                <a href="{{ route('church-database.exports.locality-summary') }}" class="rounded-xl border border-sky-200 bg-sky-50 p-4 font-semibold text-sky-700 transition hover:bg-sky-100 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200">
                    Export Locality Summary CSV
                </a>

                <a href="{{ route('church-database.exports.shepherding') }}" class="rounded-xl border border-green-200 bg-green-50 p-4 font-semibold text-green-700 transition hover:bg-green-100 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    Export Shepherding CSV
                </a>

                <a href="{{ route('church-database.exports.missing-people') }}" class="rounded-xl border border-red-200 bg-red-50 p-4 font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    Export Missing People CSV
                </a>

                <a href="{{ route('church-database.exports.missing-households') }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4 font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    Export Missing Households CSV
                </a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    People by Status
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Status-based church profile report.
                </p>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($statusReports as $report)
                        <a
                            href="{{ $report['url'] }}"
                            class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:border-primary-700 dark:hover:bg-gray-800"
                        >
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                {{ $report['label'] }}
                            </span>

                            <span class="rounded-full bg-primary-600 px-2.5 py-1 text-xs font-bold text-white">
                                {{ $report['count'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    People by Category
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Age-based category report.
                </p>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($categoryReports as $report)
                        <a
                            href="{{ $report['url'] }}"
                            class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:border-primary-700 dark:hover:bg-gray-800"
                        >
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                {{ $report['label'] }}
                            </span>

                            <span class="rounded-full bg-primary-600 px-2.5 py-1 text-xs font-bold text-white">
                                {{ $report['count'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Shepherding Group Report
            </h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                People grouped by shepherding group.
            </p>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($shepherdingGroupReports as $report)
                    <a
                        href="{{ $report['url'] }}"
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-950 dark:hover:border-primary-700 dark:hover:bg-gray-800"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $report['label'] }}
                            </p>

                            <span class="rounded-full bg-primary-600 px-2.5 py-1 text-xs font-bold text-white">
                                {{ $report['count'] }}
                            </span>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-primary-600 dark:text-primary-400">
                            View filtered list →
                        </p>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm dark:border-red-900 dark:bg-red-950">
                <h3 class="text-lg font-bold text-red-900 dark:text-red-100">
                    Data Quality Checks
                </h3>

                <p class="mt-1 text-sm text-red-700 dark:text-red-200">
                    Records that may need completion or review.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach ($qualityReports as $report)
                        <a
                            href="{{ $report['url'] }}"
                            class="block rounded-xl border border-red-200 bg-white p-4 transition hover:bg-red-100 dark:border-red-900 dark:bg-gray-900 dark:hover:bg-red-950"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $report['label'] }}
                                </p>

                                <span class="rounded-full bg-red-600 px-2.5 py-1 text-xs font-bold text-white">
                                    {{ $report['count'] }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $report['description'] }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950">
                <h3 class="text-lg font-bold text-amber-900 dark:text-amber-100">
                    Household Checks
                </h3>

                <p class="mt-1 text-sm text-amber-700 dark:text-amber-200">
                    Household records that may need review.
                </p>

                <div class="mt-5 space-y-3">
                    @foreach ($householdReports as $report)
                        <a
                            href="{{ $report['url'] }}"
                            class="block rounded-xl border border-amber-200 bg-white p-4 transition hover:bg-amber-100 dark:border-amber-900 dark:bg-gray-900 dark:hover:bg-amber-950"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ $report['label'] }}
                                </p>

                                <span class="rounded-full bg-amber-600 px-2.5 py-1 text-xs font-bold text-white">
                                    {{ $report['count'] }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $report['description'] }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
