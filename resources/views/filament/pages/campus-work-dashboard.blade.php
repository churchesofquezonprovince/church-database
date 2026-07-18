<x-filament-panels::page>
    @php
        $studentBook = $this->studentBook();
        $servingBook = $this->servingBook();
        $additionalReadings = $this->additionalReadings();
    @endphp

    <div class="space-y-6">

        @if (session('campus_work_dashboard_book_updated'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Book section updated.</p>
            </div>
        @endif

        @if (session('campus_work_dashboard_reading_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Additional reading material added.</p>
            </div>
        @endif

        @if (session('campus_work_dashboard_reading_updated'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Additional reading material updated.</p>
            </div>
        @endif

        @if (session('campus_work_dashboard_reading_deleted'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Additional reading material removed.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Please check the following:</p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Future Immich Gallery --}}
        <section class="rounded-2xl border border-sky-200 bg-sky-50 p-5 shadow-sm dark:border-sky-900 dark:bg-sky-950/40">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">
                        Future Roadmap
                    </p>

                    <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                        Student Gallery from Immich Albums
                    </h2>

                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                        Placeholder only. In a future phase, this area can show
                        student photos from selected Immich albums.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                    @foreach (range(1, 5) as $index)
                        <div class="flex aspect-square items-center justify-center rounded-xl border border-dashed border-sky-300 bg-white text-xs font-bold text-sky-700 dark:border-sky-800 dark:bg-gray-950 dark:text-sky-300">
                            Album {{ $index }}
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Main Books --}}
        <section class="grid gap-5 xl:grid-cols-2">
            @foreach ([
                [
                    'heading' => 'Book to be Pursued by the Student',
                    'item' => $studentBook,
                    'section' => \App\Models\CampusWorkDashboardItem::SECTION_STUDENT_BOOK,
                ],
                [
                    'heading' => 'Book to be Pursued by the Serving Ones',
                    'item' => $servingBook,
                    'section' => \App\Models\CampusWorkDashboardItem::SECTION_SERVING_BOOK,
                ],
            ] as $bookCard)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                                Main Book
                            </p>

                            <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                {{ $bookCard['heading'] }}
                            </h2>
                        </div>

                        <button
                            type="button"
                            onclick="document.getElementById('edit-main-book-{{ $bookCard['section'] }}').showModal()"
                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-500"
                        >
                            Edit
                        </button>
                    </div>

                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                        <h3 class="break-words text-xl font-bold text-gray-900 dark:text-white">
                            {{ $bookCard['item']->title ?: 'No book title yet' }}
                        </h3>

                        @if ($bookCard['item']->description)
                            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                {{ $bookCard['item']->description }}
                            </p>
                        @else
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                No notes or description yet.
                            </p>
                        @endif

                        @if ($bookCard['item']->link)
                            <a
                                href="{{ $bookCard['item']->link }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-4 inline-flex text-sm font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                Open link
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        {{-- Additional Reading Material --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                        Sub-section
                    </p>

                    <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                        Additional Reading Material
                    </h2>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Add, edit, or remove additional readings for the campus work.
                    </p>
                </div>

                <details class="rounded-xl border border-indigo-200 bg-indigo-50 dark:border-indigo-900 dark:bg-indigo-950/40">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-indigo-800 dark:text-indigo-200">
                        Add Reading Material
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('quezonprovinceactivities.campus-work.dashboard.readings.store') }}"
                        class="space-y-3 border-t border-indigo-200 p-4 dark:border-indigo-900"
                    >
                        @csrf

                        <input
                            type="text"
                            name="title"
                            required
                            maxlength="255"
                            placeholder="Reading title"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >

                        <textarea
                            name="description"
                            rows="3"
                            placeholder="Notes or description"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        ></textarea>

                        <input
                            type="text"
                            name="link"
                            maxlength="500"
                            placeholder="Optional link"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >

                        <button
                            type="submit"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500"
                        >
                            Add Reading
                        </button>
                    </form>
                </details>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($additionalReadings as $reading)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <h3 class="break-words font-bold text-gray-900 dark:text-white">
                                    {{ $reading->title }}
                                </h3>

                                @if ($reading->description)
                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                                        {{ $reading->description }}
                                    </p>
                                @endif

                                @if ($reading->link)
                                    <a
                                        href="{{ $reading->link }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2 inline-flex text-sm font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                                    >
                                        Open link
                                    </a>
                                @endif
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <button
                                    type="button"
                                    onclick="document.getElementById('edit-reading-{{ $reading->id }}').showModal()"
                                    class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                >
                                    Edit
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('quezonprovinceactivities.campus-work.dashboard.readings.destroy', $reading) }}"
                                    onsubmit="return confirm('Remove this reading material?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white hover:bg-red-500"
                                    >
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No additional reading material yet.
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Edit main book dialogs --}}
        @foreach ([
            [
                'id' => 'student_book',
                'heading' => 'Book to be Pursued by the Student',
                'item' => $studentBook,
                'section' => \App\Models\CampusWorkDashboardItem::SECTION_STUDENT_BOOK,
            ],
            [
                'id' => 'serving_book',
                'heading' => 'Book to be Pursued by the Serving Ones',
                'item' => $servingBook,
                'section' => \App\Models\CampusWorkDashboardItem::SECTION_SERVING_BOOK,
            ],
        ] as $bookDialog)
            <dialog
                id="edit-main-book-{{ $bookDialog['section'] }}"
                class="m-auto w-[calc(100%-2rem)] max-w-xl rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                style="z-index: 9999; position: fixed; inset: 0; margin: auto;"
            >
                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.campus-work.dashboard.main-book.update') }}"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="section"
                        value="{{ $bookDialog['section'] }}"
                    >

                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-bold">
                            Edit {{ $bookDialog['heading'] }}
                        </h3>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <label class="text-sm font-bold">Book Title</label>
                            <input
                                type="text"
                                name="title"
                                required
                                maxlength="255"
                                value="{{ $bookDialog['item']->title }}"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        <div>
                            <label class="text-sm font-bold">Notes / Description</label>
                            <textarea
                                name="description"
                                rows="4"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ $bookDialog['item']->description }}</textarea>
                        </div>

                        <div>
                            <label class="text-sm font-bold">Optional Link</label>
                            <input
                                type="text"
                                name="link"
                                maxlength="500"
                                value="{{ $bookDialog['item']->link }}"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </dialog>
        @endforeach

        {{-- Edit reading dialogs --}}
        @foreach ($additionalReadings as $reading)
            <dialog
                id="edit-reading-{{ $reading->id }}"
                class="m-auto w-[calc(100%-2rem)] max-w-xl rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                style="z-index: 9999; position: fixed; inset: 0; margin: auto;"
            >
                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.campus-work.dashboard.readings.update', $reading) }}"
                >
                    @csrf
                    @method('PATCH')

                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-bold">
                            Edit Reading Material
                        </h3>
                    </div>

                    <div class="space-y-4 p-5">
                        <div>
                            <label class="text-sm font-bold">Title</label>
                            <input
                                type="text"
                                name="title"
                                required
                                maxlength="255"
                                value="{{ $reading->title }}"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>

                        <div>
                            <label class="text-sm font-bold">Notes / Description</label>
                            <textarea
                                name="description"
                                rows="4"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >{{ $reading->description }}</textarea>
                        </div>

                        <div>
                            <label class="text-sm font-bold">Optional Link</label>
                            <input
                                type="text"
                                name="link"
                                maxlength="500"
                                value="{{ $reading->link }}"
                                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                            >
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </dialog>
        @endforeach
    </div>
</x-filament-panels::page>
