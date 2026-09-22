<?php

namespace App\Filament\Pages;

use App\Models\MinistryBook;
use App\Models\MinistryLesson;
use App\Support\ActivityLogger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MinistryBooks extends Page
{
    use \App\Filament\Concerns\ImportsMinistryOutlines;

    protected string $view = 'filament.pages.ministry-books';

    public string $bookSearch = '';

    public string $bookStatusFilter = 'all';

    public string $lessonSearch = '';

    public string $lessonStatusFilter = 'all';

    public string $newBookCode = '';

    public string $newBookTitle = '';

    public string $newBookTagalogTitle = '';

    public string $newBookShortTitle = '';

    public string $newBookDescription = '';

    public ?int $editingBookId = null;

    public string $editBookCode = '';

    public string $editBookTitle = '';

    public string $editBookTagalogTitle = '';

    public string $editBookShortTitle = '';

    public string $editBookDescription = '';

    public array $newLessonCode = [];

    public array $newLessonTitle = [];

    public array $newLessonTagalogTitle = [];

    public array $newLessonDescription = [];

    public ?int $editingLessonId = null;

    public string $editLessonCode = '';

    public string $editLessonTitle = '';

    public string $editLessonTagalogTitle = '';

    public string $editLessonDescription = '';

    public int $editLessonSortOrder = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function getTitle(): string
    {
        return 'Ministry Books Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'Ministry Books Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function books(): Collection
    {
        return MinistryBook::query()
            ->withCount('lessons')
            ->with([
                'lessons' => function ($query): void {
                    $query
                        ->when(
                            $this->lessonStatusFilter === 'active',
                            fn ($query) =>
                                $query->where('is_active', true)
                        )
                        ->when(
                            $this->lessonStatusFilter === 'archived',
                            fn ($query) =>
                                $query->where('is_active', false)
                        )
                        ->when(
                            filled($this->lessonSearch),
                            function ($query): void {
                                $search = trim($this->lessonSearch);

                                $query->where(
                                    function ($query) use ($search): void {
                                        $query
                                            ->where(
                                                'code',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'title',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'title_tagalog',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                            }
                        )
                        ->orderByDesc('is_active')
                        ->orderBy('sort_order')
                        ->orderBy('code');
                },
            ])
            ->when(
                $this->bookStatusFilter === 'active',
                fn ($query) => $query->where('is_active', true)
            )
            ->when(
                $this->bookStatusFilter === 'archived',
                fn ($query) => $query->where('is_active', false)
            )
            ->when(
                filled($this->bookSearch),
                function ($query): void {
                    $search = trim($this->bookSearch);

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%")
                            ->orWhere('short_title', 'like', "%{$search}%")
                            ->orWhere('title_tagalog', 'like', "%{$search}%");
                    });
                }
            )
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function addBook(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'newBookCode' => [
                'required',
                'string',
                'max:20',
            ],
            'newBookTitle' => [
                'required',
                'string',
                'max:255',
            ],
            'newBookTagalogTitle' => [
                'nullable',
                'string',
                'max:255',
            ],
            'newBookShortTitle' => [
                'nullable',
                'string',
                'max:100',
            ],
            'newBookDescription' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $code = strtoupper(
            trim($data['newBookCode'])
        );

        if (
            MinistryBook::query()
                ->where('code', $code)
                ->exists()
        ) {
            Notification::make()
                ->title('Ministry Book code already exists')
                ->body(
                    "The code {$code} is already configured."
                )
                ->warning()
                ->send();

            return;
        }

        $nextSortOrder =
            ((int) MinistryBook::query()->max('sort_order')) + 10;

        $book = MinistryBook::query()->create([
            'code' => $code,
            'title' => trim($data['newBookTitle']),
            'title_tagalog' => filled($data['newBookTagalogTitle'] ?? null)
                ? trim($data['newBookTagalogTitle'])
                : null,
            'short_title' =>
                filled($data['newBookShortTitle'] ?? null)
                    ? trim($data['newBookShortTitle'])
                    : null,
            'description' =>
                filled($data['newBookDescription'] ?? null)
                    ? trim($data['newBookDescription'])
                    : null,
            'sort_order' => $nextSortOrder,
            'is_active' => true,
        ]);

        ActivityLogger::log(
            action: 'ministry_book.created',
            subject: $book,
            description: 'Added a Ministry Book.',
            newValues: [
                'code' => $book->code,
                'title' => $book->title,
            ],
        );

        $this->resetNewBookForm();

        Notification::make()
            ->title('Ministry Book added')
            ->success()
            ->send();
    }

    public function editBook(int $bookId): void
    {
        $book = MinistryBook::query()
            ->findOrFail($bookId);

        $this->editingBookId = $book->id;
        $this->editBookCode = $book->code;
        $this->editBookTitle = $book->title;
        $this->editBookTagalogTitle = (string) ($book->title_tagalog ?? '');
        $this->editBookShortTitle =
            (string) ($book->short_title ?? '');
        $this->editBookDescription =
            (string) ($book->description ?? '');
    }

    public function saveBook(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (! $this->editingBookId) {
            return;
        }

        $data = $this->validate([
            'editBookCode' => [
                'required',
                'string',
                'max:20',
            ],
            'editBookTitle' => [
                'required',
                'string',
                'max:255',
            ],
            'editBookTagalogTitle' => [
                'nullable',
                'string',
                'max:255',
            ],
            'editBookShortTitle' => [
                'nullable',
                'string',
                'max:100',
            ],
            'editBookDescription' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $book = MinistryBook::query()
            ->withCount('lessons')
            ->findOrFail($this->editingBookId);

        $code = strtoupper(
            trim($data['editBookCode'])
        );

        if (
            $book->lessons_count > 0
            && $code !== $book->code
        ) {
            Notification::make()
                ->title('Book code cannot be changed')
                ->body(
                    'A Ministry Book code cannot be changed after lessons have been added.'
                )
                ->warning()
                ->send();

            return;
        }

        if (
            MinistryBook::query()
                ->where('code', $code)
                ->whereKeyNot($book->id)
                ->exists()
        ) {
            Notification::make()
                ->title('Ministry Book code already exists')
                ->body(
                    "The code {$code} is already configured."
                )
                ->warning()
                ->send();

            return;
        }

        $book->update([
            'code' => $code,
            'title' => trim($data['editBookTitle']),
            'title_tagalog' => filled($data['editBookTagalogTitle'] ?? null)
                ? trim($data['editBookTagalogTitle'])
                : null,
            'short_title' =>
                filled($data['editBookShortTitle'] ?? null)
                    ? trim($data['editBookShortTitle'])
                    : null,
            'description' =>
                filled($data['editBookDescription'] ?? null)
                    ? trim($data['editBookDescription'])
                    : null,
        ]);

        Notification::make()
            ->title('Ministry Book updated')
            ->success()
            ->send();

        $this->cancelBookEditing();
    }

    public function toggleBookActive(int $bookId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $book = MinistryBook::query()
            ->findOrFail($bookId);

        $book->update([
            'is_active' => ! $book->is_active,
        ]);

        Notification::make()
            ->title(
                $book->is_active
                    ? 'Ministry Book restored'
                    : 'Ministry Book archived'
            )
            ->success()
            ->send();
    }

    public function deleteBook(int $bookId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $book = MinistryBook::query()
            ->withCount('lessons')
            ->findOrFail($bookId);

        if ($book->lessons_count > 0) {
            Notification::make()
                ->title('Ministry Book cannot be deleted')
                ->body(
                    'Archive this book instead because it already has lessons.'
                )
                ->warning()
                ->send();

            return;
        }

        $book->delete();

        Notification::make()
            ->title('Ministry Book deleted')
            ->success()
            ->send();
    }

    public function cancelBookEditing(): void
    {
        $this->editingBookId = null;
        $this->editBookCode = '';
        $this->editBookTitle = '';
        $this->editBookTagalogTitle = '';
        $this->editBookShortTitle = '';
        $this->editBookDescription = '';
    }

    public function suggestedLessonCode(int $bookId): string
    {
        $book = MinistryBook::query()->find($bookId);

        if (! $book) {
            return '';
        }

        $prefix = strtoupper($book->code);

        $largest = MinistryLesson::query()
            ->where('ministry_book_id', $book->id)
            ->pluck('code')
            ->map(function (string $code) use ($prefix): ?int {
                if (! preg_match(
                    '/^' . preg_quote($prefix, '/') . '(\d+)$/i',
                    trim($code),
                    $matches
                )) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter(fn ($number) => $number !== null)
            ->max();

        return $prefix . (($largest ?? 0) + 1);
    }

    public function prepareLesson(int $bookId): void
    {
        if (blank($this->newLessonCode[$bookId] ?? null)) {
            $this->newLessonCode[$bookId] =
                $this->suggestedLessonCode($bookId);
        }
    }

    public function addLesson(int $bookId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $book = MinistryBook::query()->findOrFail($bookId);

        $this->prepareLesson($book->id);

        $data = $this->validate([
            "newLessonCode.{$book->id}" => [
                'required',
                'string',
                'max:50',
            ],
            "newLessonTitle.{$book->id}" => [
                'nullable',
                'string',
                'max:255',
            ],
            "newLessonTagalogTitle.{$book->id}" => [
                'nullable',
                'string',
                'max:255',
            ],
            "newLessonDescription.{$book->id}" => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $code = strtoupper(
            trim($data['newLessonCode'][$book->id])
        );

        if (
            MinistryLesson::query()
                ->where('ministry_book_id', $book->id)
                ->where('code', $code)
                ->exists()
        ) {
            Notification::make()
                ->title('Lesson code already exists')
                ->warning()
                ->send();

            return;
        }

        $sortOrder = (
            (int) MinistryLesson::query()
                ->where('ministry_book_id', $book->id)
                ->max('sort_order')
        ) + 10;

        $lesson = MinistryLesson::query()->create([
            'ministry_book_id' => $book->id,
            'code' => $code,
            'title' => filled(
                $data['newLessonTitle'][$book->id] ?? null
            )
                ? trim($data['newLessonTitle'][$book->id])
                : null,
            'title_tagalog' => filled(
                $data['newLessonTagalogTitle'][$book->id]
                    ?? null
            )
                ? trim(
                    $data['newLessonTagalogTitle'][$book->id]
                )
                : null,
            'description' => filled(
                $data['newLessonDescription'][$book->id] ?? null
            )
                ? trim($data['newLessonDescription'][$book->id])
                : null,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);

        ActivityLogger::log(
            action: 'ministry_lesson.created',
            subject: $lesson,
            description: 'Added a Ministry Lesson.',
            newValues: [
                'book' => $book->title,
                'code' => $lesson->code,
                'title' => $lesson->title,
                'title_tagalog' => $lesson->title_tagalog,
            ],
        );

        unset(
            $this->newLessonCode[$book->id],
            $this->newLessonTitle[$book->id],
            $this->newLessonTagalogTitle[$book->id],
            $this->newLessonDescription[$book->id],
        );

        Notification::make()
            ->title('Ministry Lesson added')
            ->success()
            ->send();
    }

    public function editLesson(int $lessonId): void
    {
        $lesson = MinistryLesson::query()
            ->findOrFail($lessonId);

        $this->editingLessonId = $lesson->id;
        $this->editLessonCode = $lesson->code;
        $this->editLessonTitle =
            (string) ($lesson->title ?? '');
        $this->editLessonTagalogTitle =
            (string) ($lesson->title_tagalog ?? '');
        $this->editLessonDescription =
            (string) ($lesson->description ?? '');
        $this->editLessonSortOrder =
            (int) $lesson->sort_order;
    }

    public function saveLesson(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if (! $this->editingLessonId) {
            return;
        }

        $data = $this->validate([
            'editLessonCode' => [
                'required',
                'string',
                'max:50',
            ],
            'editLessonTitle' => [
                'nullable',
                'string',
                'max:255',
            ],
            'editLessonTagalogTitle' => [
                'nullable',
                'string',
                'max:255',
            ],
            'editLessonDescription' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'editLessonSortOrder' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $lesson = MinistryLesson::query()
            ->findOrFail($this->editingLessonId);

        $code = strtoupper(
            trim($data['editLessonCode'])
        );

        if (
            MinistryLesson::query()
                ->where(
                    'ministry_book_id',
                    $lesson->ministry_book_id
                )
                ->where('code', $code)
                ->whereKeyNot($lesson->id)
                ->exists()
        ) {
            Notification::make()
                ->title('Lesson code already exists')
                ->warning()
                ->send();

            return;
        }

        $lesson->update([
            'code' => $code,
            'title' => filled($data['editLessonTitle'])
                ? trim($data['editLessonTitle'])
                : null,
            'title_tagalog' => filled(
                $data['editLessonTagalogTitle']
            )
                ? trim(
                    $data['editLessonTagalogTitle']
                )
                : null,
            'description' => filled(
                $data['editLessonDescription']
            )
                ? trim($data['editLessonDescription'])
                : null,
            'sort_order' =>
                (int) $data['editLessonSortOrder'],
        ]);

        $this->cancelLessonEditing();

        Notification::make()
            ->title('Ministry Lesson updated')
            ->success()
            ->send();
    }

    public function toggleLessonActive(int $lessonId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $lesson = MinistryLesson::query()
            ->findOrFail($lessonId);

        $lesson->update([
            'is_active' => ! $lesson->is_active,
        ]);

        Notification::make()
            ->title(
                $lesson->is_active
                    ? 'Ministry Lesson restored'
                    : 'Ministry Lesson archived'
            )
            ->success()
            ->send();
    }

    public function removeAllBookLessons(int $bookId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($bookId): array {
            $book = MinistryBook::query()
                ->lockForUpdate()
                ->findOrFail($bookId);

            $lessons = MinistryLesson::query()
                ->where('ministry_book_id', $book->id)
                ->lockForUpdate()
                ->get();

            foreach ($lessons as $lesson) {
                if ($lesson->contacts()->exists()) {
                    return ['blocked' => true, 'count' => 0, 'editing' => false];
                }
            }

            $editing = $lessons->contains('id', $this->editingLessonId);

            foreach ($lessons as $lesson) {
                if (! $lesson->delete()) {
                    throw new \RuntimeException('A lesson could not be removed.');
                }
            }

            if ($lessons->isNotEmpty()) {
                ActivityLogger::log(
                    action: 'ministry_book.lessons_removed',
                    subject: $book,
                    description: 'Removed all unlinked lessons from a Ministry Book.',
                    newValues: [
                        'lessons' => $lessons->map(fn ($lesson): array => [
                            'id' => $lesson->id,
                            'code' => $lesson->code,
                            'title' => $lesson->title,
                        ])->all(),
                    ],
                );
            }

            return [
                'blocked' => false,
                'count' => $lessons->count(),
                'editing' => $editing,
            ];
        });

        if ($result['blocked']) {
            Notification::make()
                ->title('Lessons could not be removed')
                ->body('At least one lesson is linked to a shepherding contact. No lessons were removed.')
                ->warning()
                ->send();

            return;
        }

        if ($result['editing']) {
            $this->cancelLessonEditing();
        }

        unset($this->newLessonCode[$bookId]);

        Notification::make()
            ->title($result['count'] . ' lessons removed')
            ->body('The Ministry Book has been kept.')
            ->success()
            ->send();
    }

    public function deleteLesson(int $lessonId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $lesson = MinistryLesson::query()
            ->findOrFail($lessonId);

        $lesson->delete();

        Notification::make()
            ->title('Ministry Lesson deleted')
            ->success()
            ->send();
    }

    public function cancelLessonEditing(): void
    {
        $this->editingLessonId = null;
        $this->editLessonCode = '';
        $this->editLessonTitle = '';
        $this->editLessonTagalogTitle = '';
        $this->editLessonDescription = '';
        $this->editLessonSortOrder = 0;
    }

    private function resetNewBookForm(): void
    {
        $this->newBookCode = '';
        $this->newBookTitle = '';
        $this->newBookTagalogTitle = '';
        $this->newBookShortTitle = '';
        $this->newBookDescription = '';
    }
}
