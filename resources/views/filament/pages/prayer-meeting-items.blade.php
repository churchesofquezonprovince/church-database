<x-filament-panels::page>
    @php
        $localities = $this->localities();
        $selectedLocality = $this->selectedLocality();
        $selectedSheet = $this->selectedSheet();
        $item = $this->prayerItem();
        $lines = $this->lines();
        $lineTypeOptions = $this->lineTypeOptions();
        $snapshots = $this->snapshots();
    @endphp

    <div class="space-y-6">
        @if (session('prayer_meeting_item_line_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Prayer item line added.</p>
            </div>
        @endif

        @if (session('prayer_meeting_item_line_updated'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Prayer item line updated.</p>
            </div>
        @endif

        @if (session('prayer_meeting_item_line_deleted'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Prayer item line removed.</p>
            </div>
        @endif

        @if (session('prayer_meeting_item_snapshot_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Prayer item snapshot saved.</p>
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

        {{-- Header --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                        Posts
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        Prayer Meeting Items
                    </h2>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Load prayer items by locality. Meeting day and time are automatically read from Attendance → Prayer Meeting.
                    </p>

                    <div class="mt-4 rounded-xl border border-gray-300 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-950">
                        <p class="font-bold text-gray-900 dark:text-white">
                            Meeting Day and Time
                        </p>

                        <p class="mt-1 text-gray-700 dark:text-gray-200">
                            {{ $this->meetingScheduleLabel() }}
                        </p>
                    </div>
                </div>

                
            </div>

            @if ($localities->isNotEmpty())
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($localities as $row)
                        <a
                            href="{{ $this->loadUrl($row['value']) }}"
                            @class([
                                'rounded-full px-3 py-1.5 text-xs font-bold transition',
                                'bg-indigo-600 text-white' => $selectedLocality === $row['value'],
                                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' => $selectedLocality !== $row['value'],
                            ])
                        >
                            {{ $row['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        @if (! $item)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">No Prayer Meeting attendance sheet found.</p>

                <p class="mt-2 text-sm">
                    Create or load a Prayer Meeting locality first under Attendance → Prayer Meeting.
                </p>
            </section>
        @else
            @if (! $selectedSheet)
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    <p class="font-bold">No Attendance → Prayer Meeting sheet found for this locality.</p>
                    <p class="mt-1 text-sm">
                        The prayer items can still be viewed and edited, but the meeting day/time is not auto-linked yet.
                    </p>
                </section>
            @endif

            {{-- Sub Header --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $this->localityLabel() }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $this->meetingScheduleLabel() }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a
                            href="#edit-prayer-content"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-center text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Edit Content
                        </a>

                        <form
                            method="POST"
                            action="{{ route('quezonprovinceactivities.posts.prayer-meeting-items.snapshots.store', $item) }}"
                            onsubmit="return confirm('Save a snapshot of the current prayer items?');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="w-full rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-center text-sm font-bold text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100 dark:hover:bg-amber-900 sm:w-auto"
                            >
                                Save Snapshot
                            </button>
                        </form>

                        <a
                            href="{{ $this->printUrl() }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-center text-sm font-bold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                        >
                            Print PDF
                        </a>
                    </div>
                </div>
            </section>

            {{-- Main PDF Preview --}}
            <section class="rounded-2xl border border-gray-200 bg-gray-100 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-950">
                <div class="mx-auto max-w-4xl bg-white p-8 text-black shadow-xl" style="color: #000;">
                    <div class="text-center">
                        <p class="text-sm font-bold uppercase">
                            {{ $this->localityLabel() }}
                        </p>

                        <h1 class="mt-1 text-xl font-bold uppercase">
                            Prayer Meeting Items
                        </h1>

                    </div>

                    <div class="mt-8 text-sm leading-snug">
                        @forelse ($lines as $line)
                            <div
                                class="{{ $this->lineClass($line->line_type) }}"
                                style="display: grid; grid-template-columns: 42px minmax(0, 1fr); column-gap: 4px; align-items: start; margin-bottom: 3px;"
                            ><span style="font-weight: 700; white-space: nowrap;">{{ $line->marker }}</span><span style="white-space: pre-line;">{{ $line->content }}</span></div>
                        @empty
                            <p class="text-center text-gray-500">
                                No prayer items encoded yet. Click Edit Content to add lines.
                            </p>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- Snapshot History --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">
                            History
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                            Prayer Item Snapshots
                        </h2>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Saved copies of previous prayer item versions.
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($snapshots as $snapshot)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        Snapshot #{{ $snapshot->id }}
                                    </p>

                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        Saved {{ $snapshot->created_at?->timezone('Asia/Manila')->format('M d, Y g:i A') }} PHT
                                        @if ($snapshot->created_by_name)
                                            by {{ $snapshot->created_by_name }}
                                        @endif
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ count($snapshot->content_json ?? []) }} line(s)
                                    </p>
                                </div>

                                <a
                                    href="{{ $this->snapshotPrintUrl($snapshot) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="rounded-xl border border-gray-300 px-4 py-2 text-center text-sm font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                >
                                    View Snapshot
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No snapshots saved yet.
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Edit Content --}}
            <section
                id="edit-prayer-content"
                class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                            Edit Content
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                            Prayer Item Lines
                        </h2>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            Add, edit, or remove lines. Use the marker field for I., A., 1., i., etc.
                        </p>
                    </div>

                    <details class="rounded-xl border border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-950">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-gray-900 hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-800">
                            Add New Line
                        </summary>

                        <form
                            method="POST"
                            action="{{ route('quezonprovinceactivities.posts.prayer-meeting-items.lines.store') }}"
                            class="grid gap-3 border-t border-gray-300 p-4 dark:border-gray-700 md:grid-cols-2"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="prayer_meeting_item_id"
                                value="{{ $item->id }}"
                            >

                            <div>
                                <label class="text-sm font-bold text-gray-900 dark:text-white">
                                    Type
                                </label>

                                <select
                                    name="line_type"
                                    required
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                                    @foreach ($lineTypeOptions as $value => $label)
                                        <option value="{{ $value }}">
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-bold text-gray-900 dark:text-white">
                                    Marker
                                </label>

                                <input
                                    type="text"
                                    name="marker"
                                    maxlength="20"
                                    placeholder="I. / A. / 1. / i."
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400"
                                >
                            </div>

                            <div>
                                <label class="text-sm font-bold text-gray-900 dark:text-white">
                                    Sort Order
                                </label>

                                <input
                                    type="number"
                                    name="sort_order"
                                    min="1"
                                    max="999"
                                    value="{{ ($lines->max('sort_order') ?? 0) + 1 }}"
                                    required
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-sm font-bold text-gray-900 dark:text-white">
                                    Content
                                </label>

                                <textarea
                                    name="content"
                                    rows="4"
                                    required
                                    placeholder="Type the prayer item content..."
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400"
                                ></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <button
                                    type="submit"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                                >
                                    Add Line
                                </button>
                            </div>
                        </form>
                    </details>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($lines as $line)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        #{{ $line->sort_order }} · {{ $lineTypeOptions[$line->line_type] ?? $line->line_type }}
                                    </p>

                                    <p class="mt-2 break-words text-sm text-gray-900 dark:text-gray-100">
                                        @if ($line->marker)
                                            <span class="font-bold">{{ $line->marker }}</span>
                                        @endif

                                        <span class="whitespace-pre-line">{{ $line->content }}</span>
                                    </p>
                                </div>

                                <div class="flex shrink-0 gap-2">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('edit-prayer-line-{{ $line->id }}').showModal()"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                    >
                                        Edit
                                    </button>

                                    <form
                                        method="POST"
                                        action="{{ route('quezonprovinceactivities.posts.prayer-meeting-items.lines.destroy', $line) }}"
                                        onsubmit="return confirm('Remove this prayer item line?');"
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
                            No lines yet.
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Edit dialogs --}}
            @foreach ($lines as $line)
                <dialog
                    id="edit-prayer-line-{{ $line->id }}"
                    class="m-auto w-[calc(100%-2rem)] max-w-2xl rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    style="z-index: 9999; position: fixed; inset: 0; margin: auto;"
                >
                    <form
                        method="POST"
                        action="{{ route('quezonprovinceactivities.posts.prayer-meeting-items.lines.update', $line) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-bold">Edit Prayer Item Line</h3>
                        </div>

                        <div class="grid gap-4 p-5 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-bold">Type</label>

                                <select
                                    name="line_type"
                                    required
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >
                                    @foreach ($lineTypeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($line->line_type === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-sm font-bold">Marker</label>

                                <input
                                    type="text"
                                    name="marker"
                                    maxlength="20"
                                    value="{{ $line->marker }}"
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >
                            </div>

                            <div>
                                <label class="text-sm font-bold">Sort Order</label>

                                <input
                                    type="number"
                                    name="sort_order"
                                    min="1"
                                    max="999"
                                    value="{{ $line->sort_order }}"
                                    required
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-sm font-bold">Content</label>

                                <textarea
                                    name="content"
                                    rows="6"
                                    required
                                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >{{ $line->content }}</textarea>
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
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                            >
                                Save
                            </button>
                        </div>
                    </form>
                </dialog>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
