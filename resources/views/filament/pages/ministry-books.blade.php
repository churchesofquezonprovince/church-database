<x-filament-panels::page>

<!-- coqp-ministry-dropdowns-v1 -->
<style>
    [data-ministry-dropdown] > .ministry-dropdown-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        cursor: pointer;
        list-style: none;
        font-size: 1.125rem;
        font-weight: 700;
    }
    [data-ministry-dropdown] > summary::-webkit-details-marker {
        display: none;
    }
    .ministry-dropdown-chevron {
        flex-shrink: 0;
        font-size: 1.5rem;
        transition: transform 160ms ease;
    }
    [data-ministry-dropdown][open] > summary > .ministry-dropdown-chevron {
        transform: rotate(90deg);
    }
    [data-ministry-dropdown] > summary:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 5px;
        border-radius: .25rem;
    }
</style>

    @php
        $books = $this->books();
    @endphp

    <div class="space-y-6">
        @include('filament.components.ministry-outline-import')

        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Administration
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Ministry Books
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Manage ministry books and lesson series used in shepherding,
                visitation, gospel work, and ministry progress.
            </p>
        </div>

        <details data-ministry-dropdown data-collapse-target="Add-Ministry-Book" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
<summary class="ministry-dropdown-summary"><span>Add Ministry Book</span><span class="ministry-dropdown-chevron" aria-hidden="true">›</span></summary>
<div class="mt-5">



            <form
                wire:submit="addBook"
                class="mt-5 grid gap-4 md:grid-cols-2"
            >
<!-- ministry-book-fields-v2 -->

<div>
    <label for="ministry-newBookCode"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Code *
    </label>

    <input id="ministry-newBookCode" type="text" wire:model="newBookCode"
        maxlength="20" placeholder="AB"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" required>

    @error('newBookCode')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div>
    <label for="ministry-newBookTitle"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        English Title *
    </label>

    <input id="ministry-newBookTitle" type="text" wire:model="newBookTitle"
        maxlength="255" placeholder="English title"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" required>

    @error('newBookTitle')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div>
    <label for="ministry-newBookShortTitle"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Short Title
    </label>

    <input id="ministry-newBookShortTitle" type="text" wire:model="newBookShortTitle"
        maxlength="100" placeholder="Short title (optional)"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">

    @error('newBookShortTitle')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div>
    <label for="ministry-newBookTagalogTitle"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Tagalog Title
    </label>

    <input id="ministry-newBookTagalogTitle" type="text" wire:model="newBookTagalogTitle"
        maxlength="255" placeholder="Tagalog title (optional)"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">

    @error('newBookTagalogTitle')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div class="md:col-span-2">
    <label for="ministry-newBookDescription"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Description
    </label>

    <textarea id="ministry-newBookDescription" wire:model="newBookDescription"
        rows="3" maxlength="5000" placeholder="Description (optional)"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>

    @error('newBookDescription')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>

<div class="md:col-span-2 flex justify-end">
                    <button
                        type="submit"
                        class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-500"
                    >
                        Add Ministry Book
                    </button>
                </div>
            </form>

</div></details>

        <details open data-ministry-dropdown data-collapse-target="Configured-Ministry-Books" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
<summary class="ministry-dropdown-summary"><span>Configured Ministry Books</span><span class="ministry-dropdown-chevron" aria-hidden="true">›</span></summary>
<div class="mt-5">

            <div class="flex items-center justify-between gap-4">
                <div>


                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Lessons will be managed inside each book.
                    </p>
                </div>

                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ $books->count() }} configured
                </span>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="bookSearch"
                    placeholder="Search code or title..."
                    class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                >

                <select
                    wire:model.live="bookStatusFilter"
                    class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                >
                    <option value="all">All Books</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="lessonSearch"
                    placeholder="Search lessons by code, English, or Tagalog title..."
                    class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                >

                <select
                    wire:model.live="lessonStatusFilter"
                    class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                >
                    <option value="all">All Lessons</option>
                    <option value="active">Active Lessons</option>
                    <option value="archived">Archived Lessons</option>
                </select>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($books as $book)
                    <details data-ministry-dropdown
                        wire:key="ministry-book-{{ $book->id }}"
                        class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                    >
<summary class="ministry-dropdown-summary"><span>{{ $book->code }} · {{ $book->title }}
    @if (filled($book->title_tagalog))
        <span lang="tl" class="mt-1 block text-sm font-medium text-primary-700 dark:text-primary-300">
            {{ $book->title_tagalog }}
        </span>
    @endif
    <span class="mt-1 block text-sm font-normal text-gray-500 dark:text-gray-400">
        {{ $book->lessons_count }} lessons · {{ $book->is_active ? 'Active' : 'Archived' }}
    </span></span><span class="ministry-dropdown-chevron" aria-hidden="true">›</span></summary>
<div class="mt-5">

                        @if ($editingBookId === $book->id)
                            <form
                                wire:submit="saveBook"
                                class="grid gap-4 md:grid-cols-2"
                            >
<!-- ministry-book-fields-v2 -->

<div>
    <label for="ministry-editBookCode"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Code *
    </label>

    <input id="ministry-editBookCode" type="text" wire:model="editBookCode"
        maxlength="20" placeholder="AB"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" required>

    @error('editBookCode')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div>
    <label for="ministry-editBookTitle"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        English Title *
    </label>

    <input id="ministry-editBookTitle" type="text" wire:model="editBookTitle"
        maxlength="255" placeholder="English title"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100" required>

    @error('editBookTitle')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div>
    <label for="ministry-editBookShortTitle"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Short Title
    </label>

    <input id="ministry-editBookShortTitle" type="text" wire:model="editBookShortTitle"
        maxlength="100" placeholder="Short title (optional)"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">

    @error('editBookShortTitle')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div>
    <label for="ministry-editBookTagalogTitle"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Tagalog Title
    </label>

    <input id="ministry-editBookTagalogTitle" type="text" wire:model="editBookTagalogTitle"
        maxlength="255" placeholder="Tagalog title (optional)"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">

    @error('editBookTagalogTitle')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>


<div class="md:col-span-2">
    <label for="ministry-editBookDescription"
        class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
        Description
    </label>

    <textarea id="ministry-editBookDescription" wire:model="editBookDescription"
        rows="3" maxlength="5000" placeholder="Description (optional)"
        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>

    @error('editBookDescription')
        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</div>

<div class="md:col-span-2 flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="cancelBookEditing"
                                        class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold dark:border-gray-700"
                                    >
                                        Cancel
                                    </button>

                                    <button
                                        type="submit"
                                        class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white"
                                    >
                                        Save Book
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="rounded-lg bg-gray-100 px-3 py-1 font-mono text-sm font-bold dark:bg-gray-800">
                                            {{ $book->code }}
                                        </span>

                                        <h4 class="font-bold text-gray-950 dark:text-white">
                                            {{ $book->title }}
                                        </h4>

                                        @if ($book->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                                                Active
                                            </span>
                                        @else
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                                Archived
                                            </span>
                                        @endif
                                    </div>

                                    @if (filled($book->title_tagalog))
                                        <p lang="tl" class="mt-2 text-sm font-medium text-primary-700 dark:text-primary-300">
                                            <span>Tagalog:</span> {{ $book->title_tagalog }}
                                        </p>
                                    @endif

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $book->lessons_count }}
                                        {{ $book->lessons_count === 1 ? 'lesson' : 'lessons' }}
                                    </p>

                                    @if ($book->description)
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $book->description }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        wire:click="editBook({{ $book->id }})"
                                        class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold dark:border-gray-700"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="toggleBookActive({{ $book->id }})"
                                        class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold dark:border-gray-700"
                                    >
                                        {{ $book->is_active ? 'Archive' : 'Restore' }}
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="deleteBook({{ $book->id }})"
                                        wire:confirm="Delete this unused Ministry Book?"
                                        class="rounded-xl border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 dark:border-red-900 dark:text-red-300"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        @endif

                        <details data-ministry-dropdown class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
<summary class="ministry-dropdown-summary"><span>Lessons / Messages</span><span class="ministry-dropdown-chevron" aria-hidden="true">›</span></summary>
<div class="mt-5">

                            <div class="flex items-center justify-between gap-3">
                                <h5 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Lessons / Messages
                                </h5>

                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $book->lessons_count }} total
                                </span>
                            </div>

                            @if ($book->is_active)
                                <details data-ministry-dropdown class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><summary class="ministry-dropdown-summary"><span>Add Lesson</span><span class="ministry-dropdown-chevron" aria-hidden="true">›</span></summary><div class="mt-4"><form
                                    wire:submit="addLesson({{ $book->id }})"
                                    class="mt-3 grid gap-3 md:grid-cols-2"
                                >
                                    <input
                                        type="text"
                                        wire:model="newLessonCode.{{ $book->id }}"
                                        placeholder="{{ $this->suggestedLessonCode($book->id) }}"
                                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    >

                                    <input
                                        type="text"
                                        wire:model="newLessonTitle.{{ $book->id }}"
                                        placeholder="English title (optional)"
                                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    >

                                    <input
                                        type="text"
                                        wire:model="newLessonTagalogTitle.{{ $book->id }}"
                                        placeholder="Tagalog title (optional)"
                                        class="md:col-span-2 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    >

                                    <textarea
                                        wire:model="newLessonDescription.{{ $book->id }}"
                                        rows="2"
                                        placeholder="Description or notes (optional)"
                                        class="md:col-span-2 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                    ></textarea>

                                    <div class="md:col-span-2 flex justify-end">
                                        <button
                                            type="submit"
                                            class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white"
                                        >
                                            Add Lesson
                                        </button>
                                    </div>
                                </form></div></details>
                            @endif

                            @if ($book->lessons_count > 0)
    <div class="mt-5 flex justify-end">
        <button
            type="button"
            wire:click="removeAllBookLessons({{ $book->id }})"
            wire:confirm="Remove ALL {{ $book->lessons_count }} lessons from {{ $book->title }}? This includes lessons hidden by filters. The book will be kept. Removal is blocked if any lesson is linked to a shepherding contact. This cannot be undone."
            wire:loading.attr="disabled"
            class="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 disabled:opacity-50 dark:border-red-900 dark:text-red-300"
        >
            Remove All Lessons
        </button>
    </div>
@endif

<div class="mt-4 space-y-2">
                                @forelse ($book->lessons as $lesson)
                                    <details data-ministry-dropdown
                                        wire:key="ministry-lesson-{{ $lesson->id }}"
                                        class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                                    >
<summary class="ministry-dropdown-summary"><span>{{ $lesson->code }} · {{ $lesson->title ?: 'Untitled lesson' }}
    @if (filled($lesson->title_tagalog))
        <span lang="tl" class="mt-1 block text-sm font-medium text-primary-700 dark:text-primary-300">
            {{ $lesson->title_tagalog }}
        </span>
    @endif</span><span class="ministry-dropdown-chevron" aria-hidden="true">›</span></summary>
<div class="mt-5">

                                        @if ($editingLessonId === $lesson->id)
                                            <form
                                                wire:submit="saveLesson"
                                                class="grid gap-3 md:grid-cols-2"
                                            >
                                                <input
                                                    type="text"
                                                    wire:model="editLessonCode"
                                                    placeholder="Code"
                                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                                >

                                                <input
                                                    type="text"
                                                    wire:model="editLessonTitle"
                                                    placeholder="English title"
                                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                                >

                                                <input
                                                    type="text"
                                                    wire:model="editLessonTagalogTitle"
                                                    placeholder="Tagalog title"
                                                    class="md:col-span-2 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                                >

                                                <textarea
                                                    wire:model="editLessonDescription"
                                                    rows="2"
                                                    placeholder="Description"
                                                    class="md:col-span-2 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                                ></textarea>

                                                <div class="md:col-span-2 flex justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        wire:click="cancelLessonEditing"
                                                        class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-semibold dark:border-gray-700"
                                                    >
                                                        Cancel
                                                    </button>

                                                    <button
                                                        type="submit"
                                                        class="rounded-xl bg-primary-600 px-3 py-2 text-sm font-bold text-white"
                                                    >
                                                        Save Lesson
                                                    </button>
                                                </div>
                                            </form>
                                        @else
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-mono text-sm font-bold">
                                                            {{ $lesson->code }}
                                                        </span>

                                                        <span class="text-sm text-gray-800 dark:text-gray-200">
                                                            {{ $lesson->title ?: 'Untitled lesson' }}
                                                        </span>

                                                        @if ($lesson->is_active)
                                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">
                                                                Active
                                                            </span>
                                                        @else
                                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                                                Archived
                                                            </span>
                                                        @endif
                                                    </div>

                                                    @if ($lesson->title_tagalog)
                                                    <p class="mt-1 text-xs font-medium text-primary-700 dark:text-primary-300">
                                                        Tagalog:
                                                        {{ $lesson->title_tagalog }}
                                                    </p>
                                                @endif

                                                @if ($lesson->description)
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $lesson->description }}
                                                        </p>
                                                    @endif
                                                </div>

                                                <div class="flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        wire:click="editLesson({{ $lesson->id }})"
                                                        class="rounded-xl border border-gray-300 px-3 py-1.5 text-xs font-semibold dark:border-gray-700"
                                                    >
                                                        Edit
                                                    </button>

                                                    <button
                                                        type="button"
                                                        wire:click="toggleLessonActive({{ $lesson->id }})"
                                                        class="rounded-xl border border-gray-300 px-3 py-1.5 text-xs font-semibold dark:border-gray-700"
                                                    >
                                                        {{ $lesson->is_active ? 'Archive' : 'Restore' }}
                                                    </button>

                                                    <button
                                                        type="button"
                                                        wire:click="deleteLesson({{ $lesson->id }})"
                                                        wire:confirm="Delete this unused Ministry Lesson?"
                                                        class="rounded-xl border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 dark:border-red-900 dark:text-red-300"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        @endif

</div></details>
                                @empty
                                    <p class="py-3 text-sm text-gray-500 dark:text-gray-400">
                                        No matching lessons.
                                    </p>
                                @endforelse
                            </div>

</div></details>

</div></details>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">
                        No Ministry Books configured.
                    </p>
                @endforelse
            </div>

</div></details>
    </div>
</x-filament-panels::page>
