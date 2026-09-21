<x-filament-widgets::widget>
    @php
        $pageLink = function (string $name, string $label): ?array {
            $class = 'App\\Filament\\Pages\\' . $name;
            if (! class_exists($class) || ! $class::canAccess()) {
                return null;
            }
            return ['label' => $label, 'url' => $class::getUrl()];
        };
        $resourceLink = function (string $class, string $label): ?array {
            return $class::canViewAny()
                ? ['label' => $label, 'url' => $class::getUrl('index')]
                : null;
        };
        $groups = [
            ['People & Households', 'Find people, their households, and the localities they belong to.', [
                $resourceLink(\App\Filament\Resources\People\PersonResource::class, 'Find a person'),
                $resourceLink(\App\Filament\Resources\Households\HouseholdResource::class, 'View households'),
                $pageLink('LocalityDashboard', 'View localities'),
                $pageLink('FamilyTree', 'View family relationships'),
            ]],
            ['Meetings & Attendance', 'Record who attended a meeting, or review attendance from previous meetings.', [
                $pageLink('AttendanceDashboard', 'View attendance overview'),
                $pageLink('CheckAttendance', 'Record meeting attendance'),
                $pageLink('AttendanceSheets', 'Find attendance sheets'),
                $pageLink('AttendanceReports', 'View attendance reports'),
                $pageLink('AttendanceHistory', 'View attendance history'),
            ]],
            ['Schedules & Activities', 'Find dates and details for meetings, home meetings, and other activities.', [
                $pageLink('Schedules', 'View schedules'),
                $pageLink('HomeMeetingSchedule', 'View home meeting schedules'),
            ]],
            ['Shepherding & Gospel Work', 'Find contacts and review shepherding visits, activities, and follow-up records.', [
                $pageLink('ShepherdingDashboard', 'View shepherding overview'),
                $pageLink('ShepherdingContacts', 'View shepherding records'),
                $pageLink('GospelContacts', 'Find gospel contacts'),
                $pageLink('ShepherdingHistory', 'View shepherding history'),
                $pageLink('ShepherdingMap', 'View shepherding map'),
                $pageLink('TraineesSection', 'View trainees section'),
            ]],
            ['Campus Work', 'Find student contacts, campus activities, student center information, and readings.', [
                $pageLink('CampusWorkDashboard', 'Visit campus work'),
                $pageLink('CampusContacts', 'Find student contacts'),
                $pageLink('CampusActivities', 'View campus activities'),
                $pageLink('StudentCenter', 'View student center'),
                $pageLink('StudentNucleus', 'View student nucleus'),
            ]],
            ["Children's Work", 'Find information and lessons for serving with the children.', [
                $pageLink('ChildrenWorkDashboard', "Visit children's work"),
                $pageLink('ChildrenWorkLessons', "Find children's lessons"),
            ]],
            ['Other Church Life Matters', 'Read prayer items, service meeting notes, young people\'s meeting content, and shared enjoyment.', [
                $pageLink('PrayerMeetingItems', 'Read prayer meeting items'),
                $pageLink('ServiceMeetingMinutes', 'Read service meeting minutes'),
                $pageLink('YpMeeting', "View young people's meeting"),
                $pageLink('EnjoymentPosts', 'Read enjoyment posts'),
                $pageLink('Newsletter', 'Read newsletter'),
            ]],
            ['Photos & Albums', 'Browse photos from meetings and activities through the Immich albums page.', [
                $pageLink('ImmichAlbums', 'Browse photos and albums'),
            ]],
        ];
        $adminLinks = array_values(array_filter([
            $resourceLink(\App\Filament\Resources\Users\UserResource::class, 'Manage user accounts'),
            $pageLink('ManageAttendanceSheets', 'Manage attendance sheets'),
            $pageLink('ImmichPeopleLinking', 'Link people with Immich'),
            $pageLink('PeopleImport', 'Import people records'),
            $pageLink('Reports', 'View people reports'),
            $pageLink('BackupDashboard', 'View backups'),
            $pageLink('ProvinceSetup', 'Set up province and localities'),
            $pageLink('SchoolSetup', 'Set up schools'),
            $pageLink('MinistryBooks', 'Manage ministry books'),
            $pageLink('MorningRevivalSetup', 'Set up morning revival'),
            $pageLink('HymnsSetup', 'Set up hymns'),
            $pageLink('ExternalHymnsSetup', 'Set up external hymns'),
            $pageLink('HymnalNetSetup', 'Set up Hymnal.net'),
            $pageLink('SongbaseSetup', 'Set up Songbase'),
            $pageLink('PatchNotes', 'Read website updates'),
            $pageLink('DeveloperOptions', 'Developer settings'),
            $resourceLink(\App\Filament\Resources\ActivityLogs\ActivityLogResource::class, 'View activity logs'),
        ]));
    @endphp

    <style>
        /* COQP children-style v3 */
        .coqp-home-v1 { --home-bg: #fff; --home-soft: var(--color-gray-50, #f9fafb); --home-text: var(--color-gray-950, #030712); --home-muted: var(--color-gray-600, #4b5563); --home-border: var(--color-gray-200, #e5e7eb); --home-link: #be185d; color: var(--home-text); font-size: 1.0625rem; line-height: 1.65; min-width: 0; container-type: inline-size; }
        .dark .coqp-home-v1 { --home-bg: var(--color-gray-900, #111827); --home-soft: var(--color-gray-800, #1f2937); --home-text: #fff; --home-muted: var(--color-gray-300, #d1d5db); --home-border: var(--color-gray-700, #374151); --home-link: #f9a8d4; }
        .coqp-home-v1 * { box-sizing: border-box; }
        .coqp-home-v1 h2, .coqp-home-v1 h3, .coqp-home-v1 p { margin: 0; }
        .coqp-home-v1 h2 { font-size: 1.5rem; font-weight: 700; line-height: 1.35; }
        .coqp-home-v1 h3 { font-size: 1.25rem; font-weight: 700; line-height: 1.4; }
        .coqp-home-v1 p { color: var(--home-muted); }
        .coqp-home-v1 .home-intro, .coqp-home-v1 .home-card, .coqp-home-v1 .home-extra { padding: 1.5rem; background: var(--home-bg); border: 1px solid var(--home-border); border-radius: 1rem; box-shadow: 0 1px 2px #0000000d; }
        .coqp-home-v1 .home-intro p { margin-top: .75rem; max-width: 70ch; }
        .coqp-home-v1 .home-eyebrow { display: inline-flex; align-items: center; padding: .25rem .75rem; margin-bottom: 1rem; border-radius: 9999px; background: #fce7f3; color: #9d174d; font-size: .875rem; font-weight: 700; letter-spacing: .035em; }
        .dark .coqp-home-v1 .home-eyebrow { background: #500724; color: #f9a8d4; }
        .coqp-home-v1 .home-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
        .coqp-home-v1 a { color: var(--home-link); overflow-wrap: anywhere; }
        .coqp-home-v1 .home-button { display: inline-flex; justify-content: center; align-items: center; min-height: 3rem; padding: .65rem 1rem; border: 1px solid var(--home-border); border-radius: .75rem; background: var(--home-bg); color: var(--home-text); font-size: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px #0000000d; }
        .coqp-home-v1 .home-button:hover { background: var(--home-soft); }
        .coqp-home-v1 .home-button:first-child { color: #fff; background: #2563eb; border-color: #2563eb; }
        .coqp-home-v1 .home-button:first-child:hover { background: #1d4ed8; border-color: #1d4ed8; }
        .coqp-home-v1 a:focus-visible, .coqp-home-v1 summary:focus-visible { outline: 3px solid var(--home-link); outline-offset: 4px; }
        .coqp-home-v1 .home-heading { margin: 2rem 0 1.5rem; }
        .coqp-home-v1 .home-heading p { margin-top: .5rem; }
        .coqp-home-v1 .home-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start; }
        .coqp-home-v1 .home-card { --tile-button: #7c3aed; --tile-hover: #6d28d9; --tile-text: #6d28d9; --tile-soft: #ede9fe; display: flex; flex-direction: column; min-width: 0; }
        .coqp-home-v1 .home-card[data-tone="1"], .coqp-home-v1 .home-card[data-tone="7"] { --tile-button: #047857; --tile-hover: #065f46; --tile-text: #047857; --tile-soft: #d1fae5; }
        .coqp-home-v1 .home-card[data-tone="2"], .coqp-home-v1 .home-card[data-tone="6"] { --tile-button: #b45309; --tile-hover: #92400e; --tile-text: #92400e; --tile-soft: #fef3c7; }
        .coqp-home-v1 .home-card[data-tone="3"], .coqp-home-v1 .home-card[data-tone="5"] { --tile-button: #db2777; --tile-hover: #be185d; --tile-text: #be185d; --tile-soft: #fce7f3; }
        .dark .coqp-home-v1 .home-card { --tile-text: #c4b5fd; --tile-soft: #2e1065; }
        .dark .coqp-home-v1 .home-card[data-tone="1"], .dark .coqp-home-v1 .home-card[data-tone="7"] { --tile-text: #6ee7b7; --tile-soft: #022c22; }
        .dark .coqp-home-v1 .home-card[data-tone="2"], .dark .coqp-home-v1 .home-card[data-tone="6"] { --tile-text: #fcd34d; --tile-soft: #451a03; }
        .dark .coqp-home-v1 .home-card[data-tone="3"], .dark .coqp-home-v1 .home-card[data-tone="5"] { --tile-text: #f9a8d4; --tile-soft: #500724; }
        .coqp-home-v1 .home-icon { display: grid; place-items: center; width: 2.75rem; height: 2.75rem; margin-bottom: 1rem; border-radius: 9999px; color: var(--tile-text); background: var(--tile-soft); }
        .coqp-home-v1 .home-icon svg { width: 1.4rem; height: 1.4rem; }
        .coqp-home-v1 .home-card h3 { color: var(--tile-text); }
        .coqp-home-v1 .home-card p { margin-top: .75rem; }
        .coqp-home-v1 .home-primary { display: inline-flex; align-self: flex-start; justify-content: center; align-items: center; gap: .75rem; min-height: 3rem; max-width: 100%; margin-top: 1.5rem; padding: .65rem 1rem; border-radius: .75rem; color: #fff; background: var(--tile-button); font-size: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px #0000000d; }
        .coqp-home-v1 .home-primary:hover { background: var(--tile-hover); }
        .coqp-home-v1 .home-primary span:last-child { font-size: 1.2rem; }
        .coqp-home-v1 .home-more { margin-top: .75rem; }
        .coqp-home-v1 summary { cursor: pointer; min-height: 3rem; padding: .65rem .15rem; color: var(--home-muted); font-size: 1rem; font-weight: 600; }
        .coqp-home-v1 summary:hover { color: var(--home-text); }
        .coqp-home-v1 .home-links { list-style: none; padding: 0; margin: .75rem 0 0; }
        .coqp-home-v1 .home-links li + li { margin-top: .75rem; }
        .coqp-home-v1 .home-links a { display: flex; align-items: center; min-height: 3rem; padding: .85rem 1rem; border: 1px solid var(--home-border); border-radius: .75rem; background: var(--home-bg); color: var(--home-text); font-weight: 600; text-decoration: none; }
        .coqp-home-v1 .home-links a:hover { border-color: var(--home-link); background: var(--home-soft); }
        .coqp-home-v1 .home-extra { margin-top: 2rem; }
        .coqp-home-v1 .home-extra > summary { font-size: 1.125rem; color: var(--home-text); font-weight: 700; }
        .coqp-home-v1 .home-stats { display: flex; flex-wrap: wrap; gap: 1rem; margin: 1.25rem 0; }
        .coqp-home-v1 .home-stat { flex: 1 1 12rem; padding: 1.25rem; border: 1px solid var(--home-border); border-radius: .75rem; background: var(--home-soft); }
        .coqp-home-v1 .home-stat strong { display: block; font-size: 2rem; font-weight: 700; }
        .coqp-home-v1 .home-person { display: block; padding: 1rem; margin-top: .75rem; border: 1px solid var(--home-border); border-radius: .75rem; color: var(--home-text); text-decoration: none; }
        .coqp-home-v1 .home-person:hover { background: var(--home-soft); border-color: var(--home-link); }
        .coqp-home-v1 .home-person span { display: block; margin-top: .25rem; color: var(--home-muted); }
        @container (min-width: 42rem) { .coqp-home-v1 .home-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @container (min-width: 72rem) { .coqp-home-v1 .home-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 30rem) { .coqp-home-v1 .home-intro, .coqp-home-v1 .home-card, .coqp-home-v1 .home-extra { padding: 1.1rem; } .coqp-home-v1 .home-button { width: 100%; } }
    </style>

    <div class="coqp-home-v1">
        <section class="home-intro" aria-labelledby="coqp-welcome">

            <h2 id="coqp-welcome">Welcome, serving ones.</h2>


            <div class="home-actions">
                @if (\App\Filament\Resources\People\PersonResource::canViewAny())
                    <a class="home-button" href="{{ \App\Filament\Resources\People\PersonResource::getUrl('index') }}">Find a person</a>
                @endif
                @if ($attendanceLink = $pageLink('CheckAttendance', 'Record attendance'))
                    <a class="home-button" href="{{ $attendanceLink['url'] }}">Record attendance</a>
                @endif
                @if ($scheduleLink = $pageLink('Schedules', 'View schedules'))
                    <a class="home-button" href="{{ $scheduleLink['url'] }}">View schedules</a>
                @endif
                @if (\App\Filament\Resources\People\PersonResource::canCreate())
                    <a class="home-button" href="{{ \App\Filament\Resources\People\PersonResource::getUrl('create') }}">Add a person</a>
                @endif
            </div>
        </section>

        <div class="home-heading">
            <h2 id="coqp-sections">What would you like to do?</h2>

        </div>
        <div class="home-grid" aria-labelledby="coqp-sections">
            @foreach ($groups as [$heading, $description, $links])
                @php
                    $links = array_values(array_filter($links));
                @endphp
                @if (count($links))
                    @php
                        $icons = [
                            'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
                            'M9 5H5v16h14V5h-4 M9 3h6v4H9z M8 14l3 3 5-6',
                            'M8 2v4 M16 2v4 M3 10h18 M3 4h18v18H3z M7 14h2 M12 14h2 M7 18h2',
                            'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8',
                            'M2 9l10-5 10 5-10 5z M6 11v6c4 3 8 3 12 0v-6 M22 9v8',
                            'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0 M8 9h.01 M16 9h.01 M8 14s1.5 3 4 3 4-3 4-3',
                            'M12 6c-3-2-6-2-10-1v15c4-1 7-1 10 1 3-2 6-2 10-1V5c-4-1-7-1-10 1z M12 6v15',
                            'M3 3h18v18H3z M3 17l6-6 4 4 3-3 5 5 M17 7h.01',
                        ];
                    @endphp
                    <section class="home-card" data-tone="{{ $loop->index }}">
                        <div class="home-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                <path d="{{ $icons[$loop->index] ?? $icons[0] }}" />
                            </svg>
                        </div>
                        <h3>{{ $heading }}</h3>
                        <p>{{ $description }}</p>
                        <a class="home-primary" href="{{ $links[0]['url'] }}">
                            <span>{{ $links[0]['label'] }}</span><span aria-hidden="true">→</span>
                        </a>
                        @if (count($links) > 1)
                            <details class="home-more">
                                <summary>More options <span aria-hidden="true">({{ count($links) - 1 }})</span><span class="sr-only"> for {{ $heading }}</span></summary>
                                <ul class="home-links">
                                    @foreach (array_slice($links, 1) as $link)
                                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </section>
                @endif
            @endforeach
        </div>

        <details class="home-extra">
            <summary>People records — totals and recently added people</summary>
            <p>These are saved database records, not meeting attendance counts.</p>
            <div class="home-stats">
                @foreach ($stats as $stat)
                    @if (($stat['key'] === 'households' && \App\Filament\Resources\Households\HouseholdResource::canViewAny()) || ($stat['key'] !== 'households' && \App\Filament\Resources\People\PersonResource::canViewAny()))
                        <div class="home-stat">
                            <strong>{{ number_format($stat['count']) }}</strong>
                            <span>{{ match ($stat['key']) { 'people' => 'people recorded', 'households' => 'households recorded', 'active' => 'people marked Active', default => $stat['label'] } }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            @if (\App\Filament\Resources\People\PersonResource::canViewAny())
                <h3>Recently added people</h3>
                @forelse ($recentPeople as $person)
                    <a class="home-person" href="{{ $person['url'] }}">
                        <strong>{{ $person['name'] }}</strong>
                        <span>{{ $person['locality'] }} · {{ $person['status'] }} · {{ $person['category'] }}</span>
                    </a>
                @empty
                    <p>No people have been added yet.</p>
                @endforelse
            @endif
        </details>
        @if (count($adminLinks))
            <details class="home-extra">
                <summary>More tools, reports &amp; website settings</summary>
                <p>Additional tools available to your account. Everyday tasks are in the sections above.</p>
                <ul class="home-links">
                    @foreach ($adminLinks as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
</x-filament-widgets::widget>
