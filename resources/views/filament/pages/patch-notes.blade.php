<x-filament-panels::page>
    @php
        $patchNotesMajorVersion = 1;
        $patchNotesCurrentPhase = 21;

        $patchNotes = $this->patchNotes();
        $groupedPatchNotes = $this->groupedPatchNotes();
    @endphp

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Administration
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Patch Notes
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                A simple history of system improvements based on Git commits. This page is written for non-coders so users can understand what changed.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <span class="rounded-full bg-white px-3 py-1 font-semibold text-gray-700 ring-1 ring-primary-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-primary-900">
                    Made with Laravel + Filament
                </span>

                <a
                    href="https://filamentphp.com/docs/5.x/getting-started"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="rounded-full bg-primary-600 px-3 py-1 font-semibold text-white transition hover:bg-primary-500"
                >
                    Filament Documentation
                </a>
            </div>
        </div>

        @if ($patchNotes->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900 shadow-sm dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <h3 class="text-lg font-bold">
                    No patch notes found.
                </h3>

                <p class="mt-2 text-sm">
                    The app could not read the Git history. Make sure the Docker container has access to the .git folder and the git command.
                </p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Total Updates and Version</p>
                    <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $patchNotesMajorVersion . '.' . $patchNotesCurrentPhase . '.' . ($patchNotes->count()) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">First Update</p>
                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ \Carbon\CarbonImmutable::parse($patchNotes->last()['date_time'])->format('M d, Y h:i A') }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Latest Update</p>
                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ \Carbon\CarbonImmutable::parse($patchNotes->first()['date_time'])->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Full Change History
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Listed from the latest commit down to the beginning of the project.
                </p>

                <div class="mt-6 space-y-8">
                    @foreach ($groupedPatchNotes as $date => $notes)
                        <div class="relative border-l-2 border-gray-200 pl-5 dark:border-gray-700">
                            <div class="absolute -left-2 top-0 h-4 w-4 rounded-full bg-primary-600 ring-4 ring-white dark:ring-gray-900"></div>

                            <h4 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ \Carbon\CarbonImmutable::parse($date)->format('F d, Y') }}
                            </h4>

                            <div class="mt-4 space-y-3">
                                @foreach ($notes as $note)
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="rounded-full bg-primary-100 px-2.5 py-1 text-xs font-bold text-primary-700 dark:bg-primary-900 dark:text-primary-200">
                                                        {{ $note['tag'] }}
                                                    </span>

                                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                        {{ \Carbon\CarbonImmutable::parse($note['date_time'])->format('h:i A') }}
                                                    </span>

                                                    <span @class([
                                                        'rounded-full px-2.5 py-1 text-xs font-bold',
                                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200' => $note['type'] === 'Added',
                                                        'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200' => $note['type'] === 'Removed',
                                                        'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200' => in_array($note['type'], ['Fixed', 'Restored'], true),
                                                        'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200' => ! in_array($note['type'], ['Added', 'Removed', 'Fixed', 'Restored'], true),
                                                    ])>
                                                        {{ $note['type'] }}
                                                    </span>
                                                </div>

                                                <h5 class="mt-3 text-base font-bold text-gray-900 dark:text-white">
                                                    {{ $note['title'] }}
                                                </h5>

                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $note['description'] }}
                                                </p>
                                            </div>

                                            <div class="shrink-0 rounded-lg bg-white px-2.5 py-1 text-xs font-mono text-gray-500 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:ring-gray-700">
                                                {{ $note['hash'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
