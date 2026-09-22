<details
    data-ministry-dropdown
    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
>
    <summary class="ministry-dropdown-summary">
        <span>Paste Ministry Book Outline</span>
        <span class="ministry-dropdown-chevron" aria-hidden="true">›</span>
    </summary>

    <div class="mt-5 space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Put the book title on the first nonblank line, then one lesson
            title per line. Blank lines are ignored. For multiple books in one
            paste, use headings such as “LIFE LESSONS — Volume 1”.
            Parse Outline fills the review fields without saving.
        </p>

        <label for="ministry-outline-paste" class="block text-sm font-semibold">
            Book outline
        </label>
        <textarea
            id="ministry-outline-paste"
            wire:model="ministryOutlinePaste"
            rows="10"
            maxlength="100000"
            placeholder="LIFE LESSONS&#10;&#10;Knowing That You Are Saved&#10;The Need of Your Whole Family to Be Saved"
            class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-950 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
        ></textarea>

        @error('ministryOutlinePaste')
            <p data-coqp-field-error role="alert" class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
        @enderror

        <div class="flex flex-wrap gap-3">
            <x-filament::button
                wire:click="parseMinistryOutline"
                wire:loading.attr="disabled"
            >
                Parse Outline
            </x-filament::button>
            <x-filament::button
                color="gray"
                wire:click="clearMinistryOutlinePaste"
                wire:loading.attr="disabled"
            >
                Clear Paste
            </x-filament::button>
        </div>

        @if (count($ministryOutlineBooks))
            <form wire:submit="saveMinistryOutline" class="space-y-5">
                <div class="border-t border-gray-200 pt-5 dark:border-gray-700">
                    <h3 class="text-lg font-bold">Review Books &amp; Lessons</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        Enter a unique code for each volume. Lesson codes use that
                        code followed by the lesson number, such as LLV1-1.
                        Parsing again replaces these review fields.
                        Clear Paste only clears the pasted text.
                    </p>
                </div>

                @if ($errors->has('ministryOutlineBooks*'))
                    <div data-coqp-field-error role="alert" class="space-y-1 text-sm text-red-600 dark:text-red-300">
                        @foreach ($errors->getMessages() as $field => $messages)
                            @if (str_starts_with($field, 'ministryOutlineBooks'))
                                @foreach ($messages as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                @endif

                @foreach ($ministryOutlineBooks as $bookIndex => $draft)
                    <details
                        open
                        data-ministry-dropdown
                        wire:key="outline-review-book-{{ $bookIndex }}"
                        class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                    >
                        <summary class="ministry-dropdown-summary">
                            <span>
                                {{ $draft['title'] }}
                                <span class="block text-sm font-normal text-gray-500 dark:text-gray-400">
                                    {{ count($draft['lessons']) }} lessons
                                </span>
                            </span>
                            <span class="ministry-dropdown-chevron" aria-hidden="true">›</span>
                        </summary>

                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <label class="block text-sm font-semibold">
                                Book Code *
                                <input
                                    required maxlength="20"
                                    wire:model="ministryOutlineBooks.{{ $bookIndex }}.code"
                                    placeholder="e.g. LLV{{ $bookIndex + 1 }}"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"
                                >
                            </label>
                            <label class="block text-sm font-semibold">
                                English Title *
                                <input
                                    required maxlength="255"
                                    wire:model="ministryOutlineBooks.{{ $bookIndex }}.title"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"
                                >
                            </label>
                            <label class="block text-sm font-semibold">
                                Tagalog Title
                                <input
                                    maxlength="255"
                                    wire:model="ministryOutlineBooks.{{ $bookIndex }}.title_tagalog"
                                    class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"
                                >
                            </label>
                        </div>

                        <div class="mt-5 space-y-3">
                            @foreach ($draft['lessons'] as $lessonIndex => $draftLesson)
                                <div
                                    wire:key="outline-review-lesson-{{ $bookIndex }}-{{ $lessonIndex }}"
                                    class="grid gap-3 border-t border-gray-200 pt-3 md:grid-cols-2 dark:border-gray-700"
                                >
                                    <label class="block text-sm font-semibold">
                                        Lesson {{ $lessonIndex + 1 }} — English *
                                        <input
                                            required maxlength="255"
                                            wire:model="ministryOutlineBooks.{{ $bookIndex }}.lessons.{{ $lessonIndex }}.title"
                                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"
                                        >
                                    </label>
                                    <label class="block text-sm font-semibold">
                                        Lesson {{ $lessonIndex + 1 }} — Tagalog
                                        <input
                                            maxlength="255"
                                            wire:model="ministryOutlineBooks.{{ $bookIndex }}.lessons.{{ $lessonIndex }}.title_tagalog"
                                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950"
                                        >
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach

                <div class="flex flex-wrap gap-3">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        Save Books &amp; Lessons
                    </x-filament::button>
                    <x-filament::button
                        color="gray"
                        wire:click="discardMinistryOutlineReview"
                        wire:confirm="Discard the unsaved outline review?"
                        wire:loading.attr="disabled"
                    >
                        Discard Review
                    </x-filament::button>
                </div>
            </form>
        @endif
    </div>
</details>
