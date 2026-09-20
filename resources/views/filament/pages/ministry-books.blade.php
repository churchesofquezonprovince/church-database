<x-filament-panels::page>
    @php
        $books = $this->books();
    @endphp

    <div class="space-y-6">
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

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                Add Ministry Book
            </h3>

            <form
                wire:submit="addBook"
                class="mt-5 grid gap-4 md:grid-cols-2"
            >
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Code *
                    </label>

                    <input
                        type="text"
                        wire:model="newBookCode"
                        placeholder="AB"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('newBookCode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Title *
                    </label>

                    <input
                        type="text"
                        wire:model="newBookTitle"
                        placeholder="After Being Saved"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >

                    @error('newBookTitle')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Short Title
                    </label>

                    <input
                        type="text"
                        wire:model="newBookShortTitle"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                </div>


                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Description
                    </label>

                    <textarea
                        wire:model="newBookDescription"
                        rows="3"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    ></textarea>
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
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                        Configured Ministry Books
                    </h3>

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
                    <div
                        wire:key="ministry-book-{{ $book->id }}"
                        class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                    >
                        @if ($editingBookId === $book->id)
                            <form
                                wire:submit="saveBook"
                                class="grid gap-4 md:grid-cols-2"
                            >
                                <input
                                    type="text"
                                    wire:model="editBookCode"
                                    placeholder="Code"
                                    class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                >

                                <input
                                    type="text"
                                    wire:model="editBookTitle"
                                    placeholder="Title"
                                    class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                >

                                <input
                                    type="text"
                                    wire:model="editBookShortTitle"
                                    placeholder="Short Title"
                                    class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                >


                                <textarea
                                    wire:model="editBookDescription"
                                    rows="3"
                                    placeholder="Description"
                                    class="md:col-span-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                ></textarea>

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

                        <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-3">
                                <h5 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Lessons / Messages
                                </h5>

                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $book->lessons_count }} total
                                </span>
                            </div>

                            @if ($book->is_active)
                                <form
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
                                </form>
                            @endif

                            <div class="mt-4 space-y-2">
                                @forelse ($book->lessons as $lesson)
                                    <div
                                        wire:key="ministry-lesson-{{ $lesson->id }}"
                                        class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                                    >
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
                                    </div>
                                @empty
                                    <p class="py-3 text-sm text-gray-500 dark:text-gray-400">
                                        No matching lessons.
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500">
                        No Ministry Books configured.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
