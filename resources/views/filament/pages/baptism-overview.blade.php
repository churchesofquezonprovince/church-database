<x-filament-panels::page>
    <div class="coqp-baptism">
        <style>
            .coqp-baptism{--bp-bg:#fff;--bp-soft:#f8fafc;--bp-border:#d1d5db;--bp-text:#111827;--bp-muted:#4b5563;color:var(--bp-text);font-size:14px;line-height:1.5}
            .dark .coqp-baptism{--bp-bg:#18181b;--bp-soft:#202025;--bp-border:#3f3f46;--bp-text:#fafafa;--bp-muted:#a1a1aa}
            .coqp-baptism .bp-card{background:var(--bp-bg);border:1px solid var(--bp-border);border-radius:16px;padding:20px;margin-bottom:20px}
            .coqp-baptism h2{font-size:18px;font-weight:700;margin:0 0 8px}.coqp-baptism h3{font-size:16px;font-weight:700;margin:0}
            .coqp-baptism .bp-muted{color:var(--bp-muted)}.coqp-baptism .bp-small{font-size:12px}
            .coqp-baptism .bp-filters{display:flex;flex-wrap:wrap;gap:14px;align-items:end;margin-top:18px}
            .coqp-baptism label{display:grid;gap:6px;font-weight:600;min-width:140px;flex:1}
            .coqp-baptism select,.coqp-baptism input{width:100%;min-height:44px;border:1px solid var(--bp-border);border-radius:9px;background:var(--bp-soft);color:var(--bp-text);padding:8px 12px;font:inherit}
            .coqp-baptism select{padding-right:32px}.coqp-baptism option{background:var(--bp-bg);color:var(--bp-text)}
            .coqp-baptism .bp-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:20px 0}
            .coqp-baptism .bp-stat{border:1px solid var(--bp-border);border-radius:12px;padding:16px;background:var(--bp-bg)}
            .coqp-baptism .bp-number{display:block;font-size:28px;font-weight:700;line-height:1.3}
            .coqp-baptism .bp-months{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
            .coqp-baptism .bp-month{border:1px solid var(--bp-border);border-radius:12px;padding:14px}
            .coqp-baptism .bp-month-head{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:12px}
            .coqp-baptism .bp-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:5px}
            .coqp-baptism .bp-weekday{text-align:center;font-size:12px;color:var(--bp-muted);padding-bottom:5px}
            .coqp-baptism .bp-day{min-width:0;min-height:34px;aspect-ratio:1;border:1px solid var(--bp-border);border-radius:6px;font-size:13px;cursor:pointer;position:relative;display:flex;align-items:center;justify-content:center;flex-direction:column;line-height:1.15}
            .coqp-baptism .bp-day:hover{outline:2px solid #f59e0b;outline-offset:1px}.coqp-baptism .bp-day[aria-pressed=true]{outline:3px solid #f59e0b;outline-offset:1px}
            .coqp-baptism .bp-level-0{background:var(--bp-soft);color:var(--bp-muted)}
            .coqp-baptism .bp-level-1{background:#d1fae5;color:#064e3b}.coqp-baptism .bp-level-2{background:#6ee7b7;color:#064e3b}
            .coqp-baptism .bp-level-3{background:#047857;color:#fff}.coqp-baptism .bp-level-4{background:#064e3b;color:#fff}
            .coqp-baptism .bp-day-count{font-size:10px;font-weight:700;margin-top:2px}
            .coqp-baptism .bp-month-view{grid-template-columns:1fr;max-width:780px;margin:0 auto}.coqp-baptism .bp-month-view .bp-day{min-height:65px;aspect-ratio:auto;font-size:18px}.coqp-baptism .bp-month-view .bp-day-count{font-size:12px}
            .coqp-baptism .bp-legend,.coqp-baptism .bp-actions{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin:14px 0}
            .coqp-baptism .bp-swatch{display:inline-block;width:16px;height:16px;border:1px solid var(--bp-border);border-radius:4px;vertical-align:middle;margin-right:4px}
            .coqp-baptism .bp-button{display:inline-flex;align-items:center;justify-content:center;min-height:40px;border:1px solid var(--bp-border);border-radius:8px;padding:8px 12px;background:var(--bp-soft);color:var(--bp-text);font-size:14px;font-weight:600;cursor:pointer;text-decoration:none}
            .coqp-baptism .bp-primary{background:#f59e0b;color:#111827;border-color:#f59e0b}.coqp-baptism button:disabled{opacity:.45;cursor:default}
            .coqp-baptism .bp-table-wrap{overflow-x:auto}.coqp-baptism table{width:100%;min-width:700px;border-collapse:collapse;text-align:left}
            .coqp-baptism th,.coqp-baptism td{padding:14px 12px;border-bottom:1px solid var(--bp-border);vertical-align:top}.coqp-baptism th{font-weight:700;background:var(--bp-soft)}
            .coqp-baptism .bp-person{font-weight:700}.coqp-baptism .bp-success{color:#047857;font-weight:600}.dark .coqp-baptism .bp-success{color:#6ee7b7}
            .coqp-baptism .bp-warning{color:#92400e;font-weight:600}.dark .coqp-baptism .bp-warning{color:#fcd34d}
            .coqp-baptism .bp-event{padding:12px 0;border-bottom:1px solid var(--bp-border)}.coqp-baptism .bp-event:last-child{border:0}
            .coqp-baptism .bp-empty{padding:22px 0;color:var(--bp-muted)}.coqp-baptism .bp-link-editor{margin-top:10px}.coqp-baptism summary{cursor:pointer;font-weight:600}
            .coqp-baptism :is(button,a,select,input,summary):focus-visible{outline:3px solid #f59e0b;outline-offset:3px}
            @media(max-width:1100px){.coqp-baptism .bp-months:not(.bp-month-view){grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media(max-width:680px){.coqp-baptism .bp-months:not(.bp-month-view){grid-template-columns:1fr}.coqp-baptism .bp-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.coqp-baptism .bp-card{padding:14px}.coqp-baptism .bp-day{min-height:38px}.coqp-baptism .bp-month-view .bp-day{min-height:50px}}
        </style>

        <div class="bp-card">
            <h2>Baptisms and the activities around them</h2>
            <p class="bp-muted">Choose a year or month, then select a day to see baptisms and attendance activities. Confirm an activity only when the baptism took place there.</p>
            <div class="bp-filters">
                <label>View<select wire:model.live="display"><option value="year">Whole Year</option><option value="month">Monthly</option></select></label>
                <label>Year<select wire:model.live="year">@foreach ($years as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
                @if ($display === 'month')
                    <label>Month<select wire:model.live="month">@foreach (range(1, 12) as $m)<option value="{{ $m }}">{{ \Carbon\CarbonImmutable::create(2000, $m, 1)->format('F') }}</option>@endforeach</select></label>
                @endif
                <label>Person's locality<select wire:model.live="locality"><option value="">All localities</option>@foreach ($localities as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
                <label>Confirmed activity<select wire:model.live="activity"><option value="">All activities</option><option value="unlinked">No current confirmed link</option>@foreach ($activityOptions as $id => $title)<option value="{{ $id }}">{{ $title }}</option>@endforeach</select></label>
                <label>Find a person<input type="search" wire:model.live.debounce.350ms="search" maxlength="200" placeholder="Name"></label>
            </div>
        </div>
        <div class="bp-stats" aria-live="polite">
            <div class="bp-stat"><span class="bp-number">{{ number_format($total) }}</span><span class="bp-muted">Baptisms in this period</span></div>
            <div class="bp-stat"><span class="bp-number">{{ number_format($completeCount) }}</span><span class="bp-muted">With complete dates</span></div>
            <div class="bp-stat"><span class="bp-number">{{ number_format($partialCount) }}</span><span class="bp-muted">With incomplete dates</span></div>
            <div class="bp-stat"><span class="bp-number">{{ number_format($linkedCount) }}</span><span class="bp-muted">With confirmed activities</span></div>
        </div>

        <section class="bp-card" aria-label="Baptism calendar">
            <h2>{{ $display === 'month' ? \Carbon\CarbonImmutable::create($year, $month, 1)->format('F Y') : $year }} Baptism Calendar</h2>
            <p class="bp-muted">Darker days mean more baptisms. The small number below a date is its baptism count. An unshaded day has no recorded baptism with a complete date.</p>
            <div class="bp-legend" aria-label="Baptism count legend">
                @foreach ([0 => '0', 1 => '1', 2 => '2–4', 3 => '5–9', 4 => '10+'] as $level => $label)
                    <span><span class="bp-swatch bp-level-{{ $level }}"></span>{{ $label }}</span>
                @endforeach
            </div>
            <div class="bp-months {{ $display === 'month' ? 'bp-month-view' : '' }}">
                @foreach ($calendars as $calendar)
                    <div class="bp-month" wire:key="baptism-month-{{ $year }}-{{ $calendar['number'] }}">
                        <div class="bp-month-head"><h3>{{ $calendar['name'] }}</h3><span class="bp-muted bp-small">{{ $calendar['count'] }} recorded</span></div>
                        <div class="bp-grid">
                            @foreach (['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $weekday)<div class="bp-weekday">{{ $weekday }}</div>@endforeach
                            @for ($blank = 0; $blank < $calendar['offset']; $blank++)<span aria-hidden="true"></span>@endfor
                            @foreach ($calendar['days'] as $day)
                                <button type="button" class="bp-day bp-level-{{ $day['level'] }}"
                                    wire:key="baptism-day-{{ $day['date'] }}" wire:click="selectDay('{{ $day['date'] }}')"
                                    aria-pressed="{{ $selectedDate === $day['date'] ? 'true' : 'false' }}"
                                    aria-label="{{ $day['date'] }}: {{ $day['count'] }} {{ $day['count'] === 1 ? 'baptism' : 'baptisms' }}"
                                    title="{{ $day['date'] }} · {{ $day['count'] }} {{ $day['count'] === 1 ? 'baptism' : 'baptisms' }}">
                                    <span>{{ $day['day'] }}</span>@if ($day['count'])<span class="bp-day-count">{{ $day['count'] }}</span>@endif
                                </button>
                            @endforeach
                        </div>
                        @if ($calendar['partial'])<p class="bp-muted bp-small" style="margin-top:10px">{{ $calendar['partial'] }} incomplete {{ $calendar['partial'] === 1 ? 'date' : 'dates' }} included in this month's total, outside the daily heatmap.</p>@endif
                    </div>
                @endforeach
            </div>
            @if ($unknownMonthCount)<p class="bp-muted" style="margin-top:16px">{{ $unknownMonthCount }} {{ $unknownMonthCount === 1 ? 'baptism has' : 'baptisms have' }} a known year ({{ $year }}) but no month. These appear in Whole Year totals only.</p>@endif
            <div class="bp-actions">
                <button type="button" class="bp-button" wire:click="showList('period')">Show All in Period</button>
                <button type="button" class="bp-button" wire:click="showList('partial')">Incomplete Dates ({{ $partialCount }})</button>
                <button type="button" class="bp-button" wire:click="showList('unknown-year')">Unknown Year ({{ $unknownYearCount }})</button>
            </div>
            <p class="bp-muted bp-small">Unknown-year records are shown separately and are not included in a year's totals. All lists follow the locality, name, and confirmed-activity filters above.</p>
        </section>

        @if ($selectedDate !== '')
            <section class="bp-card">
                <h2>Attendance activities on {{ $selectedDate }}</h2>
                <p class="bp-muted">These activities took place on the selected date. Their presence here does not confirm a baptism. Activities from all localities are shown so visits to another locality can be linked.</p>
                @forelse ($daySessions as $session)
                    <div class="bp-event"><strong>{{ $session['title'] }}</strong><p class="bp-muted">{{ $session['sheet_title'] }}@if ($session['time']) · {{ $session['time'] }}@endif @if ($session['location']) · {{ $session['location'] }}@endif</p></div>
                @empty
                    <p class="bp-empty">No attendance activity is recorded for this day.</p>
                @endforelse
            </section>
        @endif

        <section class="bp-card">
            <h2>{{ $listTitle }}</h2>
            <p class="bp-muted">{{ $rowCount }} {{ $rowCount === 1 ? 'person' : 'people' }}. Baptism dates come from People → Church Profile. Linking an activity does not change those dates.</p>
            <div class="bp-table-wrap" style="margin-top:14px">
                <table>
                    <thead><tr><th scope="col">Person</th><th scope="col">Baptism Date</th><th scope="col">Baptism Activity</th></tr></thead>
                    <tbody>
                        @forelse ($people as $person)
                            <tr wire:key="baptism-person-{{ $person['person_id'] }}-{{ $person['date'] ?? 'partial' }}">
                                <td><a
    class="bp-person"
    href="{{ url('/quezonprovinceactivities/people/' . $person['person_id']) }}"
    style="color:inherit;text-decoration:underline;text-underline-offset:3px"
>{{ $person['name'] }}</a><div class="bp-muted">{{ $person['locality'] ?: 'Locality not recorded' }}</div></td>
                                <td>{{ $person['date_label'] }}@if (! $person['date'])<p class="bp-muted bp-small">Not placed on a calendar day.</p>@endif</td>
                                <td>
                                    @if ($person['linked'])
                                        <p class="bp-success">Confirmed: {{ $person['link']->sheet_title }}</p>
                                        @if ($person['link']->session_title)<p class="bp-muted">{{ $person['link']->session_title }}</p>@endif
                                    @elseif ($person['link'])
                                        <p class="bp-warning">Previous link needs review</p><p class="bp-muted">{{ $person['link']->sheet_title ?: 'Activity unavailable' }} — the date changed or the activity is unavailable. Excluded from confirmed totals.</p>
                                    @else
                                        <p class="bp-muted">No confirmed activity</p>
                                    @endif
                                    @if ($person['date'] && $person['suggestions']->isNotEmpty())
                                        <details class="bp-link-editor">
                                            <summary>{{ $person['linked'] ? 'Change activity' : 'Choose baptism activity' }}</summary>
                                            <div style="margin-top:10px">
                                                <label for="bp-session-{{ $person['person_id'] }}">Activity on {{ $person['date'] }}</label>
                                                <select id="bp-session-{{ $person['person_id'] }}" wire:model="sessionChoices.{{ $person['person_id'] }}">
                                                    <option value="">Select an activity</option>
                                                    @foreach ($person['suggestions'] as $suggestion)
                                                        <option value="{{ $suggestion['id'] }}">{{ $suggestion['attended'] ? 'Attended · ' : '' }}{{ $suggestion['title'] }} · {{ $suggestion['sheet_title'] }}{{ $suggestion['time'] ? ' · '.$suggestion['time'] : '' }}{{ $suggestion['location'] ? ' · '.$suggestion['location'] : '' }}</option>
                                                    @endforeach
                                                </select>
                                                <p class="bp-muted bp-small" style="margin-top:6px">“Attended” comes from the attendance record. Confirm only if the baptism happened during this activity.</p>
                                                <button type="button" class="bp-button bp-primary" style="margin-top:8px" wire:click="confirmActivity({{ $person['person_id'] }}, '{{ $person['date'] }}')" wire:confirm="Confirm that this person was baptized during the selected activity?" wire:loading.attr="disabled">Confirm Activity</button>
                                            </div>
                                        </details>
                                    @elseif ($person['date'])
                                        <p class="bp-muted bp-small">No attendance activity recorded on this date.</p>
                                    @else
                                        <p class="bp-muted bp-small">Record a complete baptism date in People before linking an activity.</p>
                                    @endif
                                    @if ($person['link'])
                                        <button type="button" class="bp-button" style="margin-top:10px" wire:click="removeActivity({{ $person['person_id'] }})" wire:confirm="Remove the activity link? The baptism date will be kept." wire:loading.attr="disabled">Remove Link</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="bp-empty">No recorded baptisms match this selection.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bp-actions">
                <button type="button" class="bp-button" wire:click="changePeoplePage({{ $peoplePage - 1 }})" @disabled($peoplePage <= 1)>Previous</button>
                <span class="bp-muted">Page {{ $peoplePage }} of {{ $lastPage }}</span>
                <button type="button" class="bp-button" wire:click="changePeoplePage({{ $peoplePage + 1 }})" @disabled($peoplePage >= $lastPage)>Next</button>
            </div>
        </section>

        <section class="bp-card">
            <h2>Baptisms by Confirmed Activity</h2>
            <p class="bp-muted">Counts for the selected period and filters. Each person is counted once, under their confirmed Attendance Sheet.</p>
            @forelse ($summary as $sheetId => $item)
                <div class="bp-event">
                    <div class="bp-actions" style="justify-content:space-between;margin:0"><div><strong>{{ $item['title'] }}</strong><p class="bp-muted">{{ $item['dates']->implode(', ') }}</p></div><button type="button" class="bp-button" wire:click="$set('activity', '{{ $sheetId }}')">{{ $item['count'] }} {{ $item['count'] === 1 ? 'baptism' : 'baptisms' }} · View</button></div>
                </div>
            @empty
                <p class="bp-empty">No confirmed baptism activities in this period yet. Select a day and confirm an activity beside a person's name.</p>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
