@php
    $lesson ??= null;

    $smartChipFields =
        $lesson?->google_sheet_smart_chip_fields ?? [];

    $isSmartChipLink = fn (string $field): bool =>
        in_array($field, $smartChipFields, true);
@endphp

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Date</label>
    <input
        type="date"
        name="scheduled_on"
        value="{{ old('scheduled_on', $lesson?->scheduled_on?->format('Y-m-d')) }}"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Status</label>
    <select
        name="status"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >
        @foreach ($statusOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $lesson?->status ?? 'scheduled') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Lesson Code</label>
    <input
        type="text"
        name="lesson_code"
        value="{{ old('lesson_code', $lesson?->lesson_code) }}"
        placeholder="Example: Lesson 122"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Assigned To / c/o</label>
    <input
        type="text"
        name="assigned_to"
        value="{{ old('assigned_to', $lesson?->assigned_to) }}"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >
</div>

<div class="lg:col-span-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Lesson</label>
    <textarea
        name="lesson_title"
        rows="2"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('lesson_title', $lesson?->lesson_title) }}</textarea>
</div>

<div class="lg:col-span-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Lesson Link
    </label>

    <textarea
        name="lesson_url"
        rows="2"
        @disabled($isSmartChipLink('lesson_url'))
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('lesson_url', $lesson?->lesson_url) }}</textarea>

    @if ($isSmartChipLink('lesson_url'))
        <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
            🔒 Google Sheets Smart Chip — edit this link in Google Sheets.
        </p>
    @endif
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Suggested Hymn</label>
    <textarea
        name="suggested_hymn"
        rows="3"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('suggested_hymn', $lesson?->suggested_hymn) }}</textarea>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Suggested Hymn Link
    </label>

    <textarea
        name="suggested_hymn_url"
        rows="3"
        @disabled($isSmartChipLink('suggested_hymn_url'))
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('suggested_hymn_url', $lesson?->suggested_hymn_url) }}</textarea>

    @if ($isSmartChipLink('suggested_hymn_url'))
        <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
            🔒 Google Sheets Smart Chip — edit this link in Google Sheets.
        </p>
    @endif
</div>

<div class="lg:col-span-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Memory Verse</label>
    <textarea
        name="memory_verse"
        rows="3"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('memory_verse', $lesson?->memory_verse) }}</textarea>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Story</label>
    <textarea
        name="story"
        rows="4"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('story', $lesson?->story) }}</textarea>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Story Link
    </label>

    <textarea
        name="story_url"
        rows="3"
        @disabled($isSmartChipLink('story_url'))
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('story_url', $lesson?->story_url) }}</textarea>

    @if ($isSmartChipLink('story_url'))
        <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
            🔒 Google Sheets Smart Chip — edit this link in Google Sheets.
        </p>
    @endif
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Presentation Slides</label>
    <textarea
        name="presentation_slides"
        rows="4"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('presentation_slides', $lesson?->presentation_slides) }}</textarea>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Presentation Slides Link
    </label>

    <textarea
        name="presentation_slides_url"
        rows="3"
        @disabled($isSmartChipLink('presentation_slides_url'))
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('presentation_slides_url', $lesson?->presentation_slides_url) }}</textarea>

    @if ($isSmartChipLink('presentation_slides_url'))
        <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
            🔒 Google Sheets Smart Chip — edit this link in Google Sheets.
        </p>
    @endif
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Activity</label>
    <textarea
        name="activity"
        rows="3"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('activity', $lesson?->activity) }}</textarea>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Activity Link
    </label>

    <textarea
        name="activity_url"
        rows="3"
        @disabled($isSmartChipLink('activity_url'))
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('activity_url', $lesson?->activity_url) }}</textarea>

    @if ($isSmartChipLink('activity_url'))
        <p class="mt-2 text-xs font-semibold text-amber-600 dark:text-amber-400">
            🔒 Google Sheets Smart Chip — edit this link in Google Sheets.
        </p>
    @endif
</div>

<div class="lg:col-span-2">
    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Notes</label>
    <textarea
        name="notes"
        rows="3"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
    >{{ old('notes', $lesson?->notes) }}</textarea>
</div>
