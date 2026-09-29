<section data-attendee-layout class="min-w-0 space-y-6" aria-label="Attendee enrollment">
<!-- attendance-native-styling -->

<!-- attendee-spacing-polish-v1 -->
<style>
[data-attendee-layout]{font-size:14px;line-height:1.6}
[data-attendee-layout] > details > summary{font-size:17px;padding:16px 20px}
[data-attendee-layout] > details > div,
[data-attendee-layout] > div{padding:22px !important}
[data-attendee-layout] h2{font-size:18px;line-height:1.4;margin-bottom:10px}
[data-attendee-layout] p{font-size:14px;line-height:1.6;margin:10px 0 16px}
[data-attendee-layout] label{display:block;font-size:13px;line-height:1.5;font-weight:600}
[data-attendee-layout] .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px;margin:20px 0}
[data-attendee-layout] input:not([type="hidden"]),
[data-attendee-layout] select{box-sizing:border-box;width:100%;min-height:44px;margin-top:7px;padding:10px 12px !important;font-size:14px !important;line-height:1.4}
[data-attendee-layout] select{padding-right:32px !important}
[data-attendee-layout] button{min-height:42px;padding:10px 14px !important;font-size:13px !important;line-height:1.4;white-space:normal}
[data-attendee-layout] form[wire\:submit="addAttendee"] > button{min-height:46px;margin-top:4px;padding:12px 22px !important;font-size:14px !important}
[data-attendee-layout] details details,
[data-attendee-layout] > div > details{margin:18px 0}
[data-attendee-layout] > div > details > summary{padding:12px 0;font-size:14px}
[data-attendee-layout] > div > details > form{padding:4px 0 12px}
[data-attendee-layout] .overflow-x-auto{margin-top:22px}
[data-attendee-layout] table{width:100%;min-width:760px;table-layout:fixed;font-size:14px}
[data-attendee-layout] th{padding:14px 16px !important;font-size:13px;line-height:1.5}
[data-attendee-layout] td{padding:18px 16px !important;line-height:1.65;vertical-align:top}
[data-attendee-layout] th:nth-child(1){width:29%}
[data-attendee-layout] th:nth-child(2){width:19%}
[data-attendee-layout] th:nth-child(3){width:32%}
[data-attendee-layout] th:nth-child(4){width:20%}
[data-attendee-layout] td strong{font-size:14px;line-height:1.5}
[data-attendee-layout] td small{display:block;margin-top:7px;font-size:12px;line-height:1.4}
[data-attendee-layout] td:nth-child(3) > div{margin-bottom:14px;white-space:nowrap}
[data-attendee-layout] td:nth-child(3) form{margin-top:10px;white-space:normal}
[data-attendee-layout] td:nth-child(3) > div > button{display:block;margin-top:10px}
[data-attendee-layout] td button{max-width:100%;text-align:left}
[data-attendee-layout] form[wire\:submit="link"]{margin-top:24px;padding:20px !important}
[data-attendee-layout] form[wire\:submit="link"] > button{margin:6px 8px 0 0}
@media(max-width:640px){
 [data-attendee-layout] > details > div,[data-attendee-layout] > div{padding:16px !important}
 [data-attendee-layout] .grid{grid-template-columns:minmax(0,1fr);gap:16px;margin:18px 0}
 [data-attendee-layout] form[wire\:submit="addAttendee"] > button{width:100%}
}
</style>

@php $sourceLabels = ['person'=>'People Database', 'campus'=>'Campus Contact', 'gospel'=>'Gospel Contact', 'guest'=>'Guest', 'manual'=>'Guest / Walk-in']; @endphp
<details open class="min-w-0 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
<summary class="cursor-pointer px-4 py-4 text-lg font-bold text-emerald-900 hover:bg-emerald-100 dark:text-emerald-100 dark:hover:bg-emerald-900 sm:px-6">Add Attendee</summary>
<div class="border-t border-emerald-200 p-4 dark:border-emerald-900 sm:p-6">
<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">Choose an existing Person or contact, or add a guest without creating a People record. Enrollment does not mark attendance.</p>
@if ($errors->any())<p class="my-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200" role="alert">{{ $errors->first() }}</p>@endif
@if ($sheet->is_active && !$session->is_no_meeting)
<form wire:submit="addAttendee">
<div class="my-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
<label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Attendee source<select class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model.live="addSource"><option value="person">People Database</option><option value="campus">Campus Contacts</option><option value="gospel">Gospel Contacts</option><option value="guest">Guest / Walk-in</option></select></label>
<label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Enrollment<select class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model="scope"><option value="this_meeting">This Meeting Only</option>@if ($sheet->schedule_type !== 'one_time')<option value="onward">From This Meeting Onward</option>@endif</select></label>
</div>
@if ($addSource !== 'guest')
<div class="my-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
<label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Find by first or last name<input class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" type="search" wire:model.live.debounce.350ms="identitySearch" placeholder="Type a name"></label>
<label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Choose attendee<select class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model="identityId" required><option value="">Choose a result</option>@foreach ($identityChoices as $identity)<option value="{{ $identity->id }}">#{{ $identity->id }} · {{ $identity->display_name }} · {{ $identity->locality ?: 'No locality' }}{{ $addSource !== 'person' && $identity->person_id ? ' · Linked to People' : '' }}</option>@endforeach</select></label>
</div>
@if ($identitySearch !== '' && $identityChoices->isEmpty())<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">No matching records. Search another name, or choose Guest / Walk-in.</p>@endif
@if (in_array($addSource, ['campus', 'gospel'], true))<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">A contact linked to People uses that Person's attendance identity. An unlinked contact can attend without being added to People.</p>@endif
@else
<div class="my-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"><label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Existing guest or contact<select class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model.live="existingId"><option value="">New guest / walk-in</option>@foreach ($allGuests as $g)<option value="{{ $g->id }}">{{ $g->name }} · {{ $sourceLabels[$g->source_type] ?? 'Guest' }} · {{ $g->locality ?: 'No locality' }}</option>@endforeach</select></label></div>
@if (!$existingId)<div class="my-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"><label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Name<input class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model="name" maxlength="255" required></label><label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Locality (optional)<input class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model="locality" maxlength="150"></label></div>@endif
<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">For someone already enrolled on this sheet, choose their existing entry. Matching names are never combined automatically.</p>
@endif
<button class="rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-500 disabled:opacity-50" type="submit" wire:loading.attr="disabled">Enroll Attendee</button>
</form>
@endif
</div></details>
<div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-6">
<h2 class="break-words text-lg font-bold text-gray-900 dark:text-white">Enrolled Attendees</h2>
<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">People, Campus Contacts, Gospel Contacts, and Guests in one list. Check-in remains on Check Attendance.</p>
<div class="my-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
<label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Search name or locality<input class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" type="search" wire:model.live.debounce.350ms="search"></label>
<label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Source<select class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model.live="sourceFilter"><option value="">All sources</option><option value="person">People Database</option><option value="campus">Campus Contacts</option><option value="gospel">Gospel Contacts</option><option value="guest">Guests / Walk-ins</option></select></label>
<label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Show<select class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model.live="showHistory"><option value="0">Enrolled for selected date</option><option value="1">All enrollment history</option></select></label>
</div>
<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">{{ $attendeeRows->count() }} shown out of {{ $totalAttendees }} {{ $showHistory ? 'attendees with enrollment history' : 'attendees enrolled for '.$session->session_date->format('M j, Y') }}. Each attendance identity is listed once.</p>
@if ($sheet->is_active && !$session->is_no_meeting)
<details class="my-3"><summary class="cursor-pointer py-3 text-sm font-semibold text-gray-700 dark:text-gray-200">People enrollment bulk actions</summary>
<form method="POST" action="{{ route('quezonprovinceactivities.attendance-sheets.participants.destroy-all', ['sheet'=>$sheet]) }}" onsubmit="return confirm('Remove all People enrollments shown for this meeting using the existing attendance safeguards? This does not remove Campus Contact, Gospel Contact, or Guest enrollments.');">
@csrf @method('DELETE')<input type="hidden" name="attendance_session_id" value="{{ $session->id }}">
<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">This action applies to all People enrolled for the selected date, regardless of the search and source filters above. Existing attendance protections still apply.</p>
<button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900" type="submit">Remove All People for This Date</button>
</form></details>
@endif
<div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700"><table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700"><thead class="bg-gray-50 dark:bg-gray-950"><tr><th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name / Locality</th><th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Source</th><th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Enrollment periods</th><th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Identity</th></tr></thead><tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
@forelse ($attendeeRows as $row)
<tr wire:key="attendee-{{ $row['key'] }}"><td class="break-words px-4 py-3 align-top text-gray-600 dark:text-gray-300"><strong class="font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</strong><br>{{ $row['locality'] ?: 'No locality' }}<br><small>{{ $row['active'] ? 'Enrolled for selected date' : 'Not enrolled for selected date' }}</small></td>
<td class="break-words px-4 py-3 align-top text-gray-600 dark:text-gray-300">{{ $sourceLabels[$row['source']] ?? $row['source'] }}</td>
<td class="break-words px-4 py-3 align-top text-gray-600 dark:text-gray-300">@foreach ($row['periods'] as $period)
@php $start = $row['person_id'] ? $period->starts_on?->format('Y-m-d') : $period->starts_on; $end = $row['person_id'] ? $period->ends_on?->format('Y-m-d') : $period->ends_on; $coversDate = (!$start || $start <= $session->session_date->toDateString()) && (!$end || $end >= $session->session_date->toDateString()); @endphp
<div class="mb-3 space-y-2">{{ $start ?: 'Sheet start' }} → {{ $end ?: 'Onward' }}{{ !$period->is_active ? ' (Inactive)' : '' }}
@if ($sheet->is_active && !$session->is_no_meeting && $period->is_active && (!$row['person_id'] || $coversDate))
@if ($row['person_id'])
<form method="POST" action="{{ route('quezonprovinceactivities.attendance-sheets.participants.destroy', ['sheet'=>$sheet, 'participant'=>$period]) }}" onsubmit="return confirm('Remove this People enrollment using the existing attendance safeguards?');">
@csrf @method('DELETE')<input type="hidden" name="attendance_session_id" value="{{ $session->id }}"><button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900" type="submit">Remove People enrollment</button></form>
@else
<button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900" type="button" wire:click="deactivate({{ $period->id }})" wire:confirm="Deactivate this enrollment period? Historical attendance will be kept.">Deactivate</button>
@endif
@endif
</div>@endforeach</td>
<td class="break-words px-4 py-3 align-top text-gray-600 dark:text-gray-300">@if ($row['person_id'])<span>Person #{{ $row['person_id'] }}</span>@elseif ($sheet->is_active)<button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900" type="button" wire:click="$set('linkGuestId', {{ $row['guest_id'] }})">Link to People</button>@endif</td></tr>
@empty<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No attendees match these filters.</td></tr>@endforelse
</tbody></table></div>
@if ($mode==='enroll' && $linkGuestId && $allGuests->has($linkGuestId))
<form wire:submit="link" class="mt-5 space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700"><h2 class="break-words text-lg font-bold text-gray-900 dark:text-white">Link {{ $allGuests->get($linkGuestId)->name }} to People</h2>
<p class="my-3 break-words text-sm text-gray-600 dark:text-gray-300">Select an existing Person after checking their identity. Attendance and assignments will be preserved. Conflicting records will stop the link for review.</p>
<div class="my-4 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"><label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Find Person<input class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model.live.debounce.350ms="personSearch" placeholder="First or last name"></label><label class="block min-w-0 text-sm font-semibold text-gray-700 dark:text-gray-200">Confirmed Person<select class="mt-1 block w-full min-w-0 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" wire:model="linkPersonId" required><option value="">Choose a matching Person</option>@foreach ($people as $p)<option value="{{ $p->id }}">#{{ $p->id }} · {{ $p->display_name }} · {{ $p->locality ?: 'No locality' }}</option>@endforeach</select></label></div>
<button type="submit" class="rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-500 disabled:opacity-50" wire:confirm="Confirm these records belong to the same person?" wire:loading.attr="disabled">Confirm Person Link</button><button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900" type="button" wire:click="$set('linkGuestId', null)">Cancel</button>
</form>
@endif
</div>
</section>
