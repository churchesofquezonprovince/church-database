<?php

namespace App\Models;

use App\Support\RecoveryVersionBible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShepherdingContactBibleReading extends Model
{
    protected $fillable = [
        'shepherding_contact_id',
        'book_code',
        'book_name',
        'chapter_start',
        'verse_start',
        'chapter_end',
        'verse_end',
        'sort_order',
    ];

    protected $casts = [
        'chapter_start' => 'integer',
        'verse_start' => 'integer',
        'chapter_end' => 'integer',
        'verse_end' => 'integer',
        'sort_order' => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(
            ShepherdingContact::class,
            'shepherding_contact_id'
        );
    }

    public function referenceLabel(): string
    {
        return RecoveryVersionBible::formatReference(
            bookName: $this->book_name,
            chapterStart: $this->chapter_start,
            verseStart: $this->verse_start,
            chapterEnd: $this->chapter_end,
            verseEnd: $this->verse_end,
        );
    }

    public function recoveryVersionPath(): string
    {
        return RecoveryVersionBible::pathFor(
            bookCode: $this->book_code,
            chapter: $this->chapter_start,
            verse: $this->verse_start,
        );
    }

    public function recoveryVersionUrl(): ?string
    {
        $baseUrl =
            trim(
                (string) config(
                    'bible.recovery_version_url',
                    ''
                )
            );

        if ($baseUrl === '') {
            return null;
        }

        return rtrim(
            $baseUrl,
            '/'
        ) . $this->recoveryVersionPath();
    }
}
