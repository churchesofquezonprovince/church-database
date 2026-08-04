<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Children's Work</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #fff7fb; color: #111827; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 28px 16px; }
        .hero, .card { background: white; border: 1px solid #f9a8d4; border-radius: 22px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
        .hero { background: #fdf2f8; }
        .eyebrow { color: #be185d; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: .08em; }
        h1 { margin: 8px 0 0; font-size: 34px; }
        h2 { margin: 0; font-size: 22px; }
        .muted { color: #6b7280; }
        .grid { display: grid; gap: 18px; }
        @media (min-width: 900px) { .grid-2 { grid-template-columns: 1.1fr .9fr; } }
        .lesson { border: 1px solid #e5e7eb; border-radius: 16px; padding: 16px; margin-top: 12px; background: #fff; }
        .date { color: #be185d; font-weight: 800; font-size: 13px; text-transform: uppercase; }
        a { color: #be185d; font-weight: 700; }
        .verse { white-space: pre-line; background: #fffbeb; border: 1px solid #fde68a; border-radius: 16px; padding: 14px; margin-top: 14px; }
    </style>
</head>
<body>
    <main class="wrap grid">
        <section class="hero">
            <p class="eyebrow">Children's Work</p>
            <h1>Children's Work Dashboard</h1>
            <p class="muted">Latest children's meeting schedule and lesson preparation materials.</p>
        </section>

        <section class="card">
            <p class="eyebrow">Latest Schedule</p>

            @if ($nextLesson)
                <h2>{{ $nextLesson->displayTitle() }}</h2>
                <p class="muted">
                    {{ $nextLesson->displayDate() }}
                    @if ($nextLesson->assigned_to)
                        · {{ $nextLesson->assigned_to }}
                    @endif
                </p>

                @if ($nextLesson->lesson_url)
                    <p><a href="{{ $nextLesson->lesson_url }}" target="_blank" rel="noopener noreferrer">Open Lesson Link</a></p>
                @endif

                @if ($nextLesson->memory_verse)
                    <div class="verse">
                        <strong>Memory Verse</strong><br>
                        {{ $nextLesson->memory_verse }}
                    </div>
                @endif
            @else
                <p class="muted">No Children's Work lesson schedule has been added yet.</p>
            @endif
        </section>

        <section class="grid grid-2">
            <div class="card">
                <h2>Upcoming Lessons</h2>

                @forelse ($upcomingLessons as $lesson)
                    <div class="lesson">
                        <div class="date">{{ $lesson->displayDate() }}</div>
                        <strong>{{ $lesson->displayTitle() }}</strong>

                        @if ($lesson->activity)
                            <p class="muted">Activity: {{ \Illuminate\Support\Str::limit($lesson->activity, 130) }}</p>
                        @endif
                    </div>
                @empty
                    <p class="muted">No upcoming lessons yet.</p>
                @endforelse
            </div>

            <div class="card">
                <h2>Recent Lessons</h2>

                @forelse ($recentLessons as $lesson)
                    <div class="lesson">
                        <div class="date">{{ $lesson->displayDate() }}</div>
                        <strong>{{ $lesson->displayTitle() }}</strong>
                    </div>
                @empty
                    <p class="muted">No recent lessons yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</body>
</html>
