<x-filament-panels::page>
    @php
        $lessons = \App\Models\ChildrenWorkLesson::query()
            ->orderByRaw('CASE WHEN scheduled_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->get();

        $editingLesson = null;

        if (request()->integer('edit_lesson')) {
            $editingLesson = \App\Models\ChildrenWorkLesson::query()
                ->find(request()->integer('edit_lesson'));
        }

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

        @if (session('children_work_error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                {{ session('children_work_error') }}
            </div>
        @endif

        <div class="rounded-2xl border border-pink-200 bg-pink-50 p-6 shadow-sm dark:border-pink-900 dark:bg-pink-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-pink-700 dark:text-pink-300">
                Children's Work
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Lessons
            </h2>

            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('quezonprovinceactivities.children-work.google-sheet.sync') }}">
                    @csrf

                    <button
                        type="submit"
                        onclick="return confirm('Sync lessons from Google Sheet now? Existing synced rows may be updated.')"
                        class="rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500"
                    >
                        Sync Google Sheet Now
                    </button>
                </form>

                <form method="POST" action="{{ route('quezonprovinceactivities.children-work.google-sheet.push') }}">
                    @csrf

                    <button
                        type="submit"
                        onclick="return confirm('Push website changes to Google Sheet now?')"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                    >
                        Push Website Changes to Google Sheet
                    </button>
                </form>
            </div>

            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                Manage lesson schedules, hymns, memory verses, stories, slides, and activities.
            </p>
        </div>

        @if ($editingLesson)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900 dark:bg-amber-950">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                            Edit Lesson
                        </p>

                        <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editingLesson->displayTitle() }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $editingLesson->displayDate() }}
                        </p>
                    </div>

                    <a
                        href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() }}"
                        class="inline-flex rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100"
                    >
                        Cancel Edit
                    </a>
                </div>

                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.children-work.lessons.update', $editingLesson) }}"
                    class="mt-5 grid gap-4 lg:grid-cols-2"
                >
                    @csrf
                    @method('PATCH')

                    @include('filament.pages.partials.children-work-lesson-form-fields', [
                        'lesson' => $editingLesson,
                        'statusOptions' => $statusOptions,
                    ])

                    <div class="flex flex-wrap gap-2 lg:col-span-2">
                        <button type="submit" class="rounded-xl bg-pink-600 px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500">
                            Save Changes
                        </button>

                        <a
                            href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() }}"
                            class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        @endif

        <details class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900" @open(! $editingLesson && $lessons->isEmpty())>
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
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Presentation Slides</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Activity</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse ($lessons as $lesson)
                            <tr @class([
                                'bg-amber-50 dark:bg-amber-950/40' => $editingLesson?->id === $lesson->id,
                            ])>
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

                                    @if ($lesson->sync_status)
                                        <span class="mt-2 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $lesson->sync_status }}
                                        </span>
                                    @endif
                                </td>

                                  <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                      <div>{{ \Illuminate\Support\Str::limit($lesson->suggested_hymn ?: '—', 80) }}</div>

                                      @if ($lesson->suggested_hymn_url)
                                          <a href="{{ $lesson->suggested_hymn_url }}"
                                             target="_blank"
                                             rel="noopener noreferrer"
                                             class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300">
                                              Open Hymn Link
                                          </a>
                                      @endif
                                  </td>

                                  <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                      <div>{{ \Illuminate\Support\Str::limit($lesson->story ?: '—', 100) }}</div>

                                      @if ($lesson->story_url)
                                          <a href="{{ $lesson->story_url }}"
                                             target="_blank"
                                             rel="noopener noreferrer"
                                             class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300">
                                              Open Story Link
                                          </a>
                                      @endif
                                  </td>

                                  <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                      <div>{{ \Illuminate\Support\Str::limit($lesson->presentation_slides ?: '—', 100) }}</div>

                                      @if ($lesson->presentation_slides_url)
                                          <a href="{{ $lesson->presentation_slides_url }}"
                                             target="_blank"
                                             rel="noopener noreferrer"
                                             class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300">
                                              Open Presentation Slides Link
                                          </a>
                                      @endif
                                  </td>

                                  <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                      <div>{{ \Illuminate\Support\Str::limit($lesson->activity ?: '—', 100) }}</div>

                                      @if ($lesson->activity_url)
                                          <a href="{{ $lesson->activity_url }}"
                                             target="_blank"
                                             rel="noopener noreferrer"
                                             class="mt-1 inline-flex text-xs font-semibold text-pink-600 hover:underline dark:text-pink-300">
                                              Open Activity Link
                                          </a>
                                      @endif
                                  </td>

                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::limit($lesson->memory_verse ?: '—', 120) }}
                                </td>

                                <td class="px-4 py-3 align-top text-right">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            href="{{ \App\Filament\Pages\ChildrenWorkLessons::getUrl() . '?edit_lesson=' . $lesson->id }}"
                                            class="rounded-lg bg-gray-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-gray-600"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('quezonprovinceactivities.children-work.lessons.destroy', $lesson) }}"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                onclick="return confirm('Delete this lesson?')"
                                                class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-500"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
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
