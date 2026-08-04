<x-filament-panels::page>
    @php
        $lessons = \App\Models\ChildrenWorkLesson::query()
            ->orderByRaw('CASE WHEN scheduled_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->get();

        $statusOptions = [
            'scheduled' => 'Scheduled',
            'draft' => 'Draft',
            'special_activity' => 'Special Activity',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    @endphp

    <div class="space-y-6">
        @if (session('children_work_saved'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-800 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                {{ session('children_work_saved') }}
            </div>
        @endif

        <div class="rounded-2xl border border-pink-200 bg-pink-50 p-6 shadow-sm dark:border-pink-900 dark:bg-pink-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-pink-700 dark:text-pink-300">
                Children's Work
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Lessons
            </h2>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Manage lesson schedules, hymns, memory verses, stories, slides, and activities.
            </p>
        </div>

        <details class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <summary class="cursor-pointer px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                Add Lesson
            </summary>

            <form
                method="POST"
                action="{{ route('quezonprovinceactivities.children-work.lessons.store') }}"
                class="grid gap-4 border-t border-gray-200 p-6 dark:border-gray-700 lg:grid-cols-2"
            >
                @csrf

                @include('filament.pages.partials.children-work-lesson-form-fields', [
                    'lesson' => null,
                    'statusOptions' => $statusOptions,
                ])

                <div class="lg:col-span-2">
                    <button type="submit" class="rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500">
                        Save Lesson
                    </button>
                </div>
            </form>
        </details>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Lesson Schedule
            </h3>

            <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-[1200px] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Lesson</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Suggested Hymn</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Memory Verse</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Story</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Activity</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse ($lessons as $lesson)
                            <tr>
                                <td class="px-4 py-3 align-top font-semibold text-gray-900 dark:text-white">
                                    {{ $lesson->displayDate() }}
                                    <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                        {{ $statusOptions[$lesson->status] ?? $lesson->status }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $lesson->displayTitle() }}
                                    </p>

                                    @if ($lesson->lesson_url)
                                        <a href="{{ $lesson->lesson_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300">
                                            Open Lesson Link
                                        </a>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::limit($lesson->suggested_hymn ?: '—', 80) }}
                                </td>

                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::limit($lesson->memory_verse ?: '—', 120) }}
                                </td>

                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::limit($lesson->story ?: '—', 100) }}
                                </td>

                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::limit($lesson->activity ?: '—', 100) }}
                                </td>

                                <td class="px-4 py-3 align-top text-right">
                                    <details class="inline-block text-left">
                                        <summary class="cursor-pointer rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-600">
                                            Edit
                                        </summary>

                                        <div class="fixed inset-0 z-40 overflow-y-auto bg-gray-950/70 p-4">
                                            <div class="mx-auto max-w-5xl rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
                                                <div class="flex items-start justify-between gap-4">
                                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                                                        Edit Lesson
                                                    </h4>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        Click outside is disabled. Use Save or Delete.
                                                    </span>
                                                </div>

                                                <form
                                                    method="POST"
                                                    action="{{ route('quezonprovinceactivities.children-work.lessons.update', $lesson) }}"
                                                    class="mt-5 grid gap-4 lg:grid-cols-2"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    @include('filament.pages.partials.children-work-lesson-form-fields', [
                                                        'lesson' => $lesson,
                                                        'statusOptions' => $statusOptions,
                                                    ])

                                                    <div class="lg:col-span-2">
                                                        <button type="submit" class="rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500">
                                                            Save Changes
                                                        </button>
                                                    </div>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="{{ route('quezonprovinceactivities.children-work.lessons.destroy', $lesson) }}"
                                                    class="mt-4"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        onclick="return confirm('Delete this lesson?')"
                                                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500"
                                                    >
                                                        Delete Lesson
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No lessons yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
