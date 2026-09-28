<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Children's Work</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: #f8fafc;
            color: #111827;
        }

        .wrap {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 56px;
        }

        .header {
            margin-bottom: 24px;
        }

        .eyebrow {
            margin: 0;
            color: #be185d;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        h1 {
            margin: 8px 0 0;
            font-size: clamp(30px, 5vw, 44px);
            line-height: 1.1;
        }

        .subtitle {
            margin: 10px 0 0;
            color: #6b7280;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 20px;
        }

        .card {
            overflow: hidden;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .05);
        }

        .pad {
            padding: 24px;
        }

        .lesson-card,
        .verse-card {
            grid-column: span 6;
        }

        .story-card {
            grid-column: 1 / -1;
        }

        .hymn-card {
            grid-column: span 6;
        }

        .slides-card,
        .activity-card {
            grid-column: span 3;
        }

        .label {
            margin: 0;
            color: #be185d;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .lesson-title {
            margin: 14px 0 0;
            font-size: 25px;
            line-height: 1.3;
        }

        .date {
            margin-top: 14px;
            color: #be185d;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        .verse-card {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .verse {
            margin: 18px 0 0;
            white-space: pre-line;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.75;
        }

        .media-heading {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .media-title {
            margin: 8px 0 0;
            font-size: 20px;
        }

        .video {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: black;
        }

        .video iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .empty-media {
            display: grid;
            place-items: center;
            min-height: 220px;
            padding: 28px;
            text-align: center;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 18px;
            padding: 10px 15px;
            border-radius: 11px;
            background: #be185d;
            color: white;
            font-size: 14px;
            font-weight: 750;
            text-decoration: none;
        }

        .button:hover {
            background: #9d174d;
        }

        .button.violet {
            background: #7c3aed;
        }

        .button.green {
            background: #059669;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-top: 32px;
        }

        .schedule-card {
            padding: 22px;
        }

        .schedule-card h2 {
            margin: 0;
            font-size: 19px;
        }

.schedule-item {
    display: block;
    margin-top: 12px;
    padding: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    color: inherit;
    text-decoration: none;
    transition:
        background-color .15s ease,
        border-color .15s ease;
}

.schedule-item:hover {
    background: #fdf2f8;
    border-color: #f9a8d4;
}

.schedule-item:focus-visible {
    outline: 2px solid #db2777;
    outline-offset: 2px;
}

        .schedule-date {
            color: #be185d;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .schedule-title {
            margin-top: 5px;
            font-weight: 700;
            line-height: 1.4;
        }

.past-lessons-card {
    grid-column: 1 / -1;
}

.past-lessons-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.past-lessons-list .schedule-item {
    margin-top: 0;
}

@media (max-width: 850px) {
    .past-lessons-list {
        grid-template-columns: 1fr;
    }
}

        @media (max-width: 850px) {
            .lesson-card,
            .verse-card,
            .hymn-card,
            .slides-card,
            .activity-card {
                grid-column: 1 / -1;
            }

            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    @php
        $storyEmbed = $nextLesson
            ? \App\Models\ChildrenWorkLesson::youtubeEmbedUrl(
                $nextLesson->story_url
            )
            : null;

        $hymnEmbed = $nextLesson
            ? \App\Models\ChildrenWorkLesson::youtubeEmbedUrl(
                $nextLesson->suggested_hymn_url
            )
            : null;
            $resourceLabel = function (?string $value, string $fallback): string {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $fallback;
        }

        return $value;
    };

    $storyTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->story,
            $nextLesson->story_url,
            'Story Video'
        )
        : 'Story Video';

    $hymnTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->suggested_hymn,
            $nextLesson->suggested_hymn_url,
            'Suggested Hymn'
        )
        : 'Suggested Hymn';

    $slidesTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->presentation_slides,
            $nextLesson->presentation_slides_url,
            'Presentation Slides'
        )
        : 'Presentation Slides';

    $activityTitle = $nextLesson
        ? \App\Models\ChildrenWorkLesson::resourceDisplayTitle(
            $nextLesson->activity,
            $nextLesson->activity_url,
            'Activity Material'
        )
        : 'Activity Material';
    @endphp

    <main class="wrap">
        <header class="header">
            <p class="eyebrow">Children's Work</p>
            <h1>Children's Work Dashboard</h1>
            <p class="subtitle">
                Children's meeting schedule and preparation materials.
            </p>
        </header>

        @if ($nextLesson)
            <section class="feature-grid">

                <article class="card lesson-card">
                    <div class="pad">
                        @if ($nextLesson->lesson_code)
                            <p class="label">{{ $nextLesson->lesson_code }}</p>
                        @endif

                        <h2 class="lesson-title">
                            {{ $nextLesson->displayTitle() }}
                        </h2>

                        <div class="date">
                            {{ $nextLesson->displayDate() }}
                        </div>

                        @if ($nextLesson->assigned_to)
                            <p class="muted">
                                c/o {{ $nextLesson->assigned_to }}
                            </p>
                        @endif

                        @if ($nextLesson->lesson_url)
                            <a
                                class="button"
                                href="{{ $nextLesson->lesson_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open Lesson
                            </a>
                        @endif
                    </div>
                </article>

                <article class="card verse-card">
                    <div class="pad">
                        <p class="label">Memory Verse</p>

                        @if ($nextLesson->memory_verse)
                            <div class="verse">
                                {{ $nextLesson->memory_verse }}
                            </div>
                        @else
                            <p class="muted">
                                No memory verse has been added.
                            </p>
                        @endif
                    </div>
                </article>

                <article class="card story-card">
                    <div class="media-heading">
                        <p class="label">Story</p>

                        @if ($nextLesson->story)
                            <h2 class="media-title">
                                {{ $storyTitle }}
                            </h2>
                        @endif
                    </div>

                    @if ($storyEmbed)
                        <div class="video">
                            <iframe
                                src="{{ $storyEmbed }}"
                                title="{{ $storyTitle }}"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @elseif ($nextLesson->story_url)
                        <div class="empty-media">
                            <div>
                                <p class="muted">
                                    This story opens in an external resource.
                                </p>

                                <a
                                    class="button"
                                    href="{{ $nextLesson->story_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Open Story
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="empty-media muted">
                            No story video or link has been added.
                        </div>
                    @endif
                </article>

                <article class="card hymn-card">
                    <div class="media-heading">
                        <p class="label">Suggested Hymn</p>

                        <h2 class="media-title">
                            {{ $hymnTitle }}
                        </h2>
                    </div>

                    @if ($hymnEmbed)
                        <div class="video">
                            <iframe
                                src="{{ $hymnEmbed }}"
                                title="{{ $hymnTitle }}"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @elseif ($nextLesson->suggested_hymn_url)
                        <div class="pad">
                            <a
                                class="button"
                                href="{{ $nextLesson->suggested_hymn_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open Hymn
                            </a>
                        </div>
                    @endif
                </article>

                <article class="card slides-card">
                    <div class="pad">
                        <p class="label">Presentation Slides</p>

                        <p>
                            {{ $slidesTitle }}
                        </p>

                        @if ($nextLesson->presentation_slides_url)
                            <a
                                class="button violet"
                                href="{{ $nextLesson->presentation_slides_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open Slides
                            </a>
                        @endif
                    </div>
                </article>

                <article class="card activity-card">
                    <div class="pad">
                        <p class="label">Activity</p>

                        <p>
                            {{ $activityTitle }}
                        </p>

                        @if ($nextLesson->activity_url)
                            <a
                                class="button green"
                                href="{{ $nextLesson->activity_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Open Activity
                            </a>
                        @endif
                    </div>
                </article>

            </section>
        @else
            <div class="card pad">
                No Children's Work schedule has been added yet.
            </div>
        @endif

        <section class="schedule-grid">


<article class="card schedule-card">
    <h2>
        {{
            $showingNextMonthLessons
                ? 'Next Month Lessons'
                : 'Upcoming Lessons'
        }}
    </h2>

    @forelse ($upcomingLessons as $lesson)
        <a
            href="{{ route(
                'children-work.dashboard.public',
                ['lesson' => $lesson->id]
            ) }}"
            class="schedule-item"
        >
            <div class="schedule-date">
                {{ $lesson->displayDate() }}
            </div>

            <div class="schedule-title">
                {{ $lesson->displayTitle() }}
            </div>
        </a>
    @empty
        <p class="muted">
            No more lessons scheduled this month.
        </p>
    @endforelse
</article>

<article class="card schedule-card">
    <h2>Recent Lessons</h2>

    @forelse ($recentLessons as $lesson)
        <a
            href="{{ route(
                'children-work.dashboard.public',
                ['lesson' => $lesson->id]
            ) }}"
            class="schedule-item"
        >
            <div class="schedule-date">
                {{ $lesson->displayDate() }}
            </div>

            <div class="schedule-title">
                {{ $lesson->displayTitle() }}
            </div>
        </a>
    @empty
        <p class="muted">
            No recent lessons.
        </p>
    @endforelse
</article>

<article class="card schedule-card">
    <h2>Future Lessons</h2>

    @forelse ($futureLessons as $lesson)
        <a
            href="{{ route(
                'children-work.dashboard.public',
                ['lesson' => $lesson->id]
            ) }}"
            class="schedule-item"
        >
            <div class="schedule-date">
                {{ $lesson->displayDate() }}
            </div>

            <div class="schedule-title">
                {{ $lesson->displayTitle() }}
            </div>
        </a>
    @empty
        <p class="muted">
            No future lessons currently scheduled.
        </p>
    @endforelse
</article>

<article class="card schedule-card past-lessons-card">
    <h2>All Past Lessons</h2>

    <p class="muted">
        Complete lesson history, newest first.
    </p>

    <div class="past-lessons-list">
        @forelse ($pastLessons as $lesson)
            <a
                href="{{ route(
                    'children-work.dashboard.public',
                    ['lesson' => $lesson->id]
                ) }}"
                class="schedule-item"
            >
                <div class="schedule-date">
                    {{ $lesson->displayDate() }}
                </div>

                <div class="schedule-title">
                    {{ $lesson->displayTitle() }}
                </div>
            </a>
        @empty
            <p class="muted">
                No past lessons.
            </p>
        @endforelse
    </div>
</article>

        </section>
    </main>
@include('filament.components.back-to-top')
</body>
</html>