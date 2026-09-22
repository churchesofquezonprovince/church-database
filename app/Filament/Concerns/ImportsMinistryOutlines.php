<?php

namespace App\Filament\Concerns;

use App\Models\MinistryBook;
use App\Models\MinistryLesson;
use App\Support\ActivityLogger;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait ImportsMinistryOutlines
{
    public string $ministryOutlinePaste = '';

    public array $ministryOutlineBooks = [];

    public function parseMinistryOutline(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->validate([
            'ministryOutlinePaste' => ['required', 'string', 'max:100000'],
        ]);

        $books = [];
        $current = null;

        foreach (preg_split('/\R/u', trim($this->ministryOutlinePaste)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match(
                '/^(.+?)\s*[-–—]\s*Volume\s+(\d+)\s*$/iu',
                $line,
                $match
            )) {
                $books[] = [
                    'code' => '',
                    'title' => mb_convert_case(trim($match[1]), MB_CASE_TITLE, 'UTF-8') . ' — Volume ' . (int) $match[2],
                    'title_tagalog' => '',
                    'lessons' => [],
                ];
                $current = array_key_last($books);
                continue;
            }

            if ($current === null) {
                // A plain first heading is also a valid book title.
                $books[] = [
                    'code' => '',
                    'title' => mb_convert_case($line, MB_CASE_TITLE, 'UTF-8'),
                    'title_tagalog' => '',
                    'lessons' => [],
                ];
                $current = array_key_last($books);
                continue;
            }

            $books[$current]['lessons'][] = [
                'title' => $line,
                'title_tagalog' => '',
            ];

            if (count($books) > 20 || count($books[$current]['lessons']) > 200) {
                throw ValidationException::withMessages([
                    'ministryOutlinePaste' =>
                        'Paste up to 20 volumes, with up to 200 lessons each.',
                ]);
            }
        }

        if ($books === []) {
            throw ValidationException::withMessages([
                'ministryOutlinePaste' => 'No book title was found.',
            ]);
        }

        $seen = [];
        foreach ($books as $book) {
            $key = mb_strtolower($book['title']);

            if (isset($seen[$key]) || $book['lessons'] === []) {
                throw ValidationException::withMessages([
                    'ministryOutlinePaste' =>
                        'Each book must appear once and contain at least one lesson.',
                ]);
            }

            if (mb_strlen($book['title']) > 255) {
                throw ValidationException::withMessages([
                    'ministryOutlinePaste' => 'A book title exceeds 255 characters.',
                ]);
            }

            foreach ($book['lessons'] as $lesson) {
                if (mb_strlen($lesson['title']) > 255) {
                    throw ValidationException::withMessages([
                        'ministryOutlinePaste' =>
                            'A lesson title exceeds 255 characters. Use one title per line.',
                    ]);
                }
            }

            $seen[$key] = true;
        }

        // Replace the review only after the entire paste passes validation.
        $this->ministryOutlineBooks = $books;
        $this->resetValidation();

        Notification::make()
            ->title('Outline ready for review')
            ->body(count($books) . ' books loaded. Enter their codes before saving.')
            ->success()
            ->send();
    }

    public function clearMinistryOutlinePaste(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->ministryOutlinePaste = '';
        $this->resetValidation('ministryOutlinePaste');
    }

    public function discardMinistryOutlineReview(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->ministryOutlineBooks = [];
        $this->resetValidation();
    }

    public function saveMinistryOutline(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'ministryOutlineBooks' => ['required', 'array', 'min:1', 'max:20'],
            'ministryOutlineBooks.*.code' => [
                'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'ministryOutlineBooks.*.title' => ['required', 'string', 'max:255'],
            'ministryOutlineBooks.*.title_tagalog' => ['nullable', 'string', 'max:255'],
            'ministryOutlineBooks.*.lessons' => ['required', 'array', 'min:1', 'max:200'],
            'ministryOutlineBooks.*.lessons.*.title' => ['required', 'string', 'max:255'],
            'ministryOutlineBooks.*.lessons.*.title_tagalog' => [
                'nullable', 'string', 'max:255',
            ],
        ]);

        $books = $data['ministryOutlineBooks'];
        $codes = [];
        $titles = [];

        foreach ($books as $index => &$book) {
            $book['code'] = strtoupper(trim($book['code']));
            $book['title'] = trim($book['title']);
            $book['title_tagalog'] = trim($book['title_tagalog'] ?? '');
            $titleKey = mb_strtolower($book['title']);

            if (isset($codes[$book['code']])) {
                throw ValidationException::withMessages([
                    "ministryOutlineBooks.$index.code" => 'Use a different code for each book.',
                ]);
            }

            if ($book['title'] === '' || isset($titles[$titleKey])) {
                throw ValidationException::withMessages([
                    "ministryOutlineBooks.$index.title" =>
                        'Each book needs a nonblank, distinct title.',
                ]);
            }

            foreach ($book['lessons'] as $lessonIndex => &$lesson) {
                $lesson['title'] = trim($lesson['title']);
                $lesson['title_tagalog'] = trim($lesson['title_tagalog'] ?? '');

                if ($lesson['title'] === '') {
                    throw ValidationException::withMessages([
                        "ministryOutlineBooks.$index.lessons.$lessonIndex.title" =>
                            'Enter a lesson title.',
                    ]);
                }
            }
            unset($lesson);

            $codes[$book['code']] = true;
            $titles[$titleKey] = true;
        }
        unset($book);

        DB::transaction(function () use ($books): void {
            // Check every reviewed book before creating any records.
            foreach ($books as $index => $item) {
                if (MinistryBook::query()->where('code', $item['code'])->exists()) {
                    throw ValidationException::withMessages([
                        "ministryOutlineBooks.$index.code" =>
                            'This book code already exists.',
                    ]);
                }

                if (MinistryBook::query()->where('title', $item['title'])->exists()) {
                    throw ValidationException::withMessages([
                        "ministryOutlineBooks.$index.title" =>
                            'This book title already exists. Manage its lessons in Configured Ministry Books.',
                    ]);
                }
            }

            $sort = (int) MinistryBook::query()->max('sort_order');

            foreach ($books as $item) {
                $sort += 10;
                $book = MinistryBook::query()->create([
                    'code' => $item['code'],
                    'title' => $item['title'],
                    'title_tagalog' => $item['title_tagalog'] ?: null,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]);

                ActivityLogger::log(
                    action: 'ministry_book.created',
                    subject: $book,
                    description: 'Added a Ministry Book from a reviewed outline.',
                    newValues: [
                        'code' => $book->code,
                        'title' => $book->title,
                        'title_tagalog' => $book->title_tagalog,
                    ],
                );

                foreach (array_values($item['lessons']) as $index => $itemLesson) {
                    $lesson = MinistryLesson::query()->create([
                        'ministry_book_id' => $book->id,
                        'code' => $book->code . '-' . ($index + 1),
                        'title' => $itemLesson['title'],
                        'title_tagalog' => $itemLesson['title_tagalog'] ?: null,
                        'sort_order' => ($index + 1) * 10,
                        'is_active' => true,
                    ]);

                    ActivityLogger::log(
                        action: 'ministry_lesson.created',
                        subject: $lesson,
                        description: 'Added a Ministry Lesson from a reviewed outline.',
                        newValues: [
                            'book' => $book->title,
                            'code' => $lesson->code,
                            'title' => $lesson->title,
                            'title_tagalog' => $lesson->title_tagalog,
                        ],
                    );
                }
            }
        });

        $count = count($books);
        $this->ministryOutlineBooks = [];
        $this->ministryOutlinePaste = '';
        $this->resetValidation();

        Notification::make()
            ->title($count . ' books and their lessons saved')
            ->success()
            ->send();
    }
}
