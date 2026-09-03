<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChildrenWorkLesson extends Model
{
    protected $fillable = [
        'scheduled_on',
        'lesson_code',
        'lesson_title',
        'lesson_url',
        'suggested_hymn',
        'suggested_hymn_url',
        'memory_verse',
        'story',
        'story_url',
        'presentation_slides',
        'presentation_slides_url',
        'activity',
        'activity_url',
        'assigned_to',
        'notes',
        'status',
        'source',
        'google_sheet_row_number',
        'google_sheet_row_hash',
        'google_sheet_synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'scheduled_on' => 'date',
        'google_sheet_synced_at' => 'datetime',
    ];

public static function resourceDisplayTitle(
    ?string $value,
    ?string $url,
    string $fallback
): string {
    $value = trim((string) $value);
    $url = trim((string) $url);

    /*
     * If we already have a proper human-readable title,
     * use it as-is.
     */
    if (
        $value !== ''
        && ! filter_var($value, FILTER_VALIDATE_URL)
    ) {
        return $value;
    }

    /*
     * If the content itself is a URL, use it as the
     * resource URL for title detection.
     */
    if ($url === '' && filter_var($value, FILTER_VALIDATE_URL)) {
        $url = $value;
    }

    /*
     * YouTube resources can provide their title through
     * the public oEmbed endpoint without an API key.
     */
    if (filled(self::youtubeEmbedUrl($url))) {
        $youtubeTitle = self::youtubeTitle($url);

        if (filled($youtubeTitle)) {
            return $youtubeTitle;
        }
    }

    return $fallback;
}

public static function youtubeTitle(?string $url): ?string
{
    $url = trim((string) $url);

    if ($url === '' || ! filled(self::youtubeEmbedUrl($url))) {
        return null;
    }

    return Cache::remember(
        'children-work:youtube-title:' . sha1($url),
        now()->addDays(7),
        function () use ($url): ?string {
            try {
                $response = Http::timeout(5)
                    ->retry(1, 250)
                    ->get(
                        'https://www.youtube.com/oembed',
                        [
                            'url' => $url,
                            'format' => 'json',
                        ]
                    );

                if (! $response->successful()) {
                    return null;
                }

                $title = trim(
                    (string) $response->json('title')
                );

                if ($title === '') {
                    return null;
                }

                return self::cleanYoutubeTitle($title);
            } catch (\Throwable) {
                return null;
            }
        }
    );
}

private static function cleanYoutubeTitle(string $title): string
{
    $title = trim($title);

    /*
     * Example:
     *
     * Learn About Acts of Kindness and Sharing 💛 😊
     * | ABCmouse Classroom Adventure for Kids
     *
     * becomes:
     *
     * Learn About Acts of Kindness and Sharing
     */
    if (str_contains($title, '|')) {
        $title = trim(
            Str::before($title, '|')
        );
    }

    /*
     * Remove common YouTube suffixes.
     */
    $title = preg_replace(
        '/\s*[-–—]\s*YouTube\s*$/iu',
        '',
        $title
    ) ?? $title;

    /*
     * Remove trailing emoji/symbol decoration while
     * preserving normal letters, numbers and punctuation.
     */
    $title = preg_replace(
        '/[\p{So}\p{Sk}\x{FE0F}\x{200D}\s]+$/u',
        '',
        $title
    ) ?? $title;

    $title = trim($title);

    return $title !== ''
        ? $title
        : 'YouTube Video';
}

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '>=', today())
            ->orderBy('scheduled_on');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query
            ->whereNotNull('scheduled_on')
            ->whereDate('scheduled_on', '<', today())
            ->orderByDesc('scheduled_on');
    }

    public function displayTitle(): string
    {
        return filled($this->lesson_title)
            ? (string) $this->lesson_title
            : 'Untitled Children\'s Work Lesson';
    }

    public function displayDate(): string
    {
        return $this->scheduled_on
            ? $this->scheduled_on->format('M d, Y')
            : 'No date';
    }

public static function youtubeEmbedUrl(?string $url): ?string
{
    $url = trim((string) $url);

    if ($url === '') {
        return null;
    }

    $parts = parse_url($url);

    if (! is_array($parts)) {
        return null;
    }

    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = (string) ($parts['path'] ?? '');

    $videoId = null;

    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $videoId = trim($path, '/');
    }

    if (
        $videoId === null
        && (
            $host === 'youtube.com'
            || $host === 'www.youtube.com'
            || $host === 'm.youtube.com'
        )
    ) {
        parse_str(
            (string) ($parts['query'] ?? ''),
            $query
        );

        if (filled($query['v'] ?? null)) {
            $videoId = (string) $query['v'];
        } elseif (
            preg_match(
                '#/(?:embed|shorts)/([^/?]+)#',
                $path,
                $matches
            )
        ) {
            $videoId = $matches[1];
        }
    }

    if (! filled($videoId)) {
        return null;
    }

    $videoId = preg_replace(
        '/[^A-Za-z0-9_-]/',
        '',
        $videoId
    );

    if (! filled($videoId)) {
        return null;
    }

    return 'https://www.youtube-nocookie.com/embed/'
        . $videoId;
}

}
