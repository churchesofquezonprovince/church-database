<?php

namespace App\Support;

use InvalidArgumentException;

class RecoveryVersionBible
{
    /**
     * Canonical Bible book metadata.
     *
     * code = filename used by the local Recovery Version site.
     */
    private const BOOKS = [
        ['name' => 'Genesis', 'code' => 'Gen', 'aliases' => ['gen', 'ge', 'gn']],
        ['name' => 'Exodus', 'code' => 'Exo', 'aliases' => ['exo', 'ex']],
        ['name' => 'Leviticus', 'code' => 'Lev', 'aliases' => ['lev', 'le', 'lv']],
        ['name' => 'Numbers', 'code' => 'Num', 'aliases' => ['num', 'nu', 'nm']],
        ['name' => 'Deuteronomy', 'code' => 'Deu', 'aliases' => ['deu', 'deut', 'dt']],
        ['name' => 'Joshua', 'code' => 'Jos', 'aliases' => ['jos', 'josh']],
        ['name' => 'Judges', 'code' => 'Jdg', 'aliases' => ['jdg', 'judg', 'judges']],
        ['name' => 'Ruth', 'code' => 'Rut', 'aliases' => ['rut', 'ruth']],
        ['name' => '1 Samuel', 'code' => '1Sa', 'aliases' => ['1sa', '1 sam', '1 samuel']],
        ['name' => '2 Samuel', 'code' => '2Sa', 'aliases' => ['2sa', '2 sam', '2 samuel']],
        ['name' => '1 Kings', 'code' => '1Ki', 'aliases' => ['1ki', '1 kg', '1 kings']],
        ['name' => '2 Kings', 'code' => '2Ki', 'aliases' => ['2ki', '2 kg', '2 kings']],
        ['name' => '1 Chronicles', 'code' => '1Ch', 'aliases' => ['1ch', '1 chr', '1 chronicles']],
        ['name' => '2 Chronicles', 'code' => '2Ch', 'aliases' => ['2ch', '2 chr', '2 chronicles']],
        ['name' => 'Ezra', 'code' => 'Ezr', 'aliases' => ['ezr', 'ezra']],
        ['name' => 'Nehemiah', 'code' => 'Neh', 'aliases' => ['neh', 'nehemiah']],
        ['name' => 'Esther', 'code' => 'Est', 'aliases' => ['est', 'esther']],
        ['name' => 'Job', 'code' => 'Job', 'aliases' => ['job']],
        ['name' => 'Psalms', 'code' => 'Psa', 'aliases' => ['ps', 'psa', 'psalm', 'psalms']],
        ['name' => 'Proverbs', 'code' => 'Prv', 'aliases' => ['pr', 'pro', 'prov', 'proverbs']],
        ['name' => 'Ecclesiastes', 'code' => 'Ecc', 'aliases' => ['ecc', 'eccl', 'ecclesiastes']],
        ['name' => 'Song of Songs', 'code' => 'SoS', 'aliases' => ['sos', 'song', 'song of songs']],
        ['name' => 'Isaiah', 'code' => 'Isa', 'aliases' => ['isa', 'isaiah']],
        ['name' => 'Jeremiah', 'code' => 'Jer', 'aliases' => ['jer', 'jeremiah']],
        ['name' => 'Lamentations', 'code' => 'Lam', 'aliases' => ['lam', 'lamentations']],
        ['name' => 'Ezekiel', 'code' => 'Ezk', 'aliases' => ['eze', 'ezek', 'ezekiel']],
        ['name' => 'Daniel', 'code' => 'Dan', 'aliases' => ['dan', 'daniel']],
        ['name' => 'Hosea', 'code' => 'Hos', 'aliases' => ['hos', 'hosea']],
        ['name' => 'Joel', 'code' => 'Joe', 'aliases' => ['joe', 'joel']],
        ['name' => 'Amos', 'code' => 'Amo', 'aliases' => ['amo', 'amos']],
        ['name' => 'Obadiah', 'code' => 'Oba', 'aliases' => ['oba', 'obad', 'obadiah']],
        ['name' => 'Jonah', 'code' => 'Jon', 'aliases' => ['jon', 'jonah']],
        ['name' => 'Micah', 'code' => 'Mic', 'aliases' => ['mic', 'micah']],
        ['name' => 'Nahum', 'code' => 'Nah', 'aliases' => ['nah', 'nahum']],
        ['name' => 'Habakkuk', 'code' => 'Hab', 'aliases' => ['hab', 'habakkuk']],
        ['name' => 'Zephaniah', 'code' => 'Zep', 'aliases' => ['zep', 'zeph', 'zephaniah']],
        ['name' => 'Haggai', 'code' => 'Hag', 'aliases' => ['hag', 'haggai']],
        ['name' => 'Zechariah', 'code' => 'Zec', 'aliases' => ['zec', 'zech', 'zechariah']],
        ['name' => 'Malachi', 'code' => 'Mal', 'aliases' => ['mal', 'malachi']],

        ['name' => 'Matthew', 'code' => 'Mat', 'aliases' => ['mat', 'matt', 'matthew']],
        ['name' => 'Mark', 'code' => 'Mrk', 'aliases' => ['mar', 'mk', 'mark']],
        ['name' => 'Luke', 'code' => 'Luk', 'aliases' => ['luk', 'lk', 'luke']],
        ['name' => 'John', 'code' => 'Joh', 'aliases' => ['joh', 'jn', 'john']],
        ['name' => 'Acts', 'code' => 'Act', 'aliases' => ['act', 'acts']],
        ['name' => 'Romans', 'code' => 'Rom', 'aliases' => ['rom', 'ro', 'romans']],
        ['name' => '1 Corinthians', 'code' => '1Co', 'aliases' => ['1co', '1 cor', '1 corinthians']],
        ['name' => '2 Corinthians', 'code' => '2Co', 'aliases' => ['2co', '2 cor', '2 corinthians']],
        ['name' => 'Galatians', 'code' => 'Gal', 'aliases' => ['gal', 'galatians']],
        ['name' => 'Ephesians', 'code' => 'Eph', 'aliases' => ['eph', 'ephesians']],
        ['name' => 'Philippians', 'code' => 'Phi', 'aliases' => ['phi', 'phil', 'philippians']],
        ['name' => 'Colossians', 'code' => 'Col', 'aliases' => ['col', 'colossians']],
        ['name' => '1 Thessalonians', 'code' => '1Th', 'aliases' => ['1th', '1 thes', '1 thessalonians']],
        ['name' => '2 Thessalonians', 'code' => '2Th', 'aliases' => ['2th', '2 thes', '2 thessalonians']],
        ['name' => '1 Timothy', 'code' => '1Ti', 'aliases' => ['1ti', '1 tim', '1 timothy']],
        ['name' => '2 Timothy', 'code' => '2Ti', 'aliases' => ['2ti', '2 tim', '2 timothy']],
        ['name' => 'Titus', 'code' => 'Tit', 'aliases' => ['tit', 'titus']],
        ['name' => 'Philemon', 'code' => 'Phm', 'aliases' => ['phm', 'philemon']],
        ['name' => 'Hebrews', 'code' => 'Heb', 'aliases' => ['heb', 'hebrews']],
        ['name' => 'James', 'code' => 'Jam', 'aliases' => ['jam', 'jas', 'james']],
        ['name' => '1 Peter', 'code' => '1Pe', 'aliases' => ['1pe', '1 pet', '1 peter']],
        ['name' => '2 Peter', 'code' => '2Pe', 'aliases' => ['2pe', '2 pet', '2 peter']],
        ['name' => '1 John', 'code' => '1Jo', 'aliases' => ['1jo', '1 jn', '1 john']],
        ['name' => '2 John', 'code' => '2Jo', 'aliases' => ['2jo', '2 jn', '2 john']],
        ['name' => '3 John', 'code' => '3Jo', 'aliases' => ['3jo', '3 jn', '3 john']],
        ['name' => 'Jude', 'code' => 'Jud', 'aliases' => ['jud', 'jude']],
        ['name' => 'Revelation', 'code' => 'Rev', 'aliases' => ['rev', 'revelation']],
    ];

    public static function books(): array
    {
        return self::BOOKS;
    }

    public static function parse(
        string $reference
    ): array {
        $reference = trim($reference);

        if ($reference === '') {
            throw new InvalidArgumentException(
                'Enter a Bible reference.'
            );
        }

        $normalized = preg_replace(
            '/\s+/u',
            ' ',
            str_replace(
                ['–', '—'],
                '-',
                $reference
            )
        );

        if (
            ! preg_match(
                '/^(.+?)\s+(\d+)'
                . '(?::(\d+))?'
                . '(?:\s*-\s*'
                . '(?:(\d+):)?(\d+)'
                . ')?$/u',
                $normalized,
                $matches
            )
        ) {
            throw new InvalidArgumentException(
                'Use a reference like John 3, '
                . 'John 3:16, John 3:16-21, '
                . 'or John 3:36-4:3.'
            );
        }

        $book = self::findBook(
            $matches[1]
        );

        if (! $book) {
            throw new InvalidArgumentException(
                'Bible book not recognized.'
            );
        }

        $chapterStart = (int) $matches[2];

        $verseStart =
            isset($matches[3])
            && $matches[3] !== ''
                ? (int) $matches[3]
                : null;

        $chapterEnd =
            isset($matches[4])
            && $matches[4] !== ''
                ? (int) $matches[4]
                : null;

        $verseEnd =
            isset($matches[5])
            && $matches[5] !== ''
                ? (int) $matches[5]
                : null;

        if ($chapterStart < 1) {
            throw new InvalidArgumentException(
                'Chapter must be at least 1.'
            );
        }

        if (
            $verseStart !== null
            && $verseStart < 1
        ) {
            throw new InvalidArgumentException(
                'Verse must be at least 1.'
            );
        }

        if (
            $verseStart === null
            && (
                $chapterEnd !== null
                || $verseEnd !== null
            )
        ) {
            throw new InvalidArgumentException(
                'A verse range requires a starting verse.'
            );
        }

        if (
            $verseEnd !== null
            && $verseEnd < 1
        ) {
            throw new InvalidArgumentException(
                'Ending verse must be at least 1.'
            );
        }

        if (
            $chapterEnd !== null
            && $chapterEnd < $chapterStart
        ) {
            throw new InvalidArgumentException(
                'Ending chapter cannot be before the starting chapter.'
            );
        }

        if (
            $chapterEnd === null
            && $verseStart !== null
            && $verseEnd !== null
            && $verseEnd < $verseStart
        ) {
            throw new InvalidArgumentException(
                'Ending verse cannot be before the starting verse.'
            );
        }

        return [
            'book_code' => $book['code'],
            'book_name' => $book['name'],
            'chapter_start' => $chapterStart,
            'verse_start' => $verseStart,
            'chapter_end' => $chapterEnd,
            'verse_end' => $verseEnd,
        ];
    }

    public static function formatReference(
        string $bookName,
        int $chapterStart,
        ?int $verseStart = null,
        ?int $chapterEnd = null,
        ?int $verseEnd = null,
    ): string {
        $label =
            $bookName
            . ' '
            . $chapterStart;

        if ($verseStart !== null) {
            $label .= ':' . $verseStart;
        }

        if ($verseEnd !== null) {
            $label .= '–';

            if (
                $chapterEnd !== null
                && $chapterEnd !== $chapterStart
            ) {
                $label .=
                    $chapterEnd
                    . ':';
            }

            $label .= $verseEnd;
        }

        return $label;
    }

    public static function pathFor(
        string $bookCode,
        int $chapter,
        ?int $verse = null,
    ): string {
        $anchor = $verse !== null
            ? '#v' . $chapter . '_' . $verse
            : '#v' . $chapter;

        return '/'
            . $bookCode
            . '.htm'
            . $anchor;
    }

    private static function findBook(
        string $input
    ): ?array {
        $needle = self::normalizeBookName(
            $input
        );

        foreach (self::BOOKS as $book) {
            $candidates = array_merge(
                [
                    $book['name'],
                    $book['code'],
                ],
                $book['aliases']
            );

            foreach ($candidates as $candidate) {
                if (
                    self::normalizeBookName(
                        $candidate
                    ) === $needle
                ) {
                    return $book;
                }
            }
        }

        return null;
    }

    private static function normalizeBookName(
        string $value
    ): string {
        $value = mb_strtolower(
            trim($value)
        );

        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

        return trim($value);
    }
}
