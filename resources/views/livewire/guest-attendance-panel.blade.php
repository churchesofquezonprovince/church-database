<section class="ga-panel" aria-label="Guests and contacts">
<style>
.ga-panel{margin:24px 0;padding:20px;border:1px solid #64748b66;border-radius:16px;background:#fff;color:#172033}.dark .ga-panel{background:#18181b;color:#f3f4f6}.ga-panel h2{font-size:1.2rem;font-weight:700}.ga-panel p{margin:8px 0}.ga-panel .ga-fields{display:flex;flex-wrap:wrap;gap:12px;margin:12px 0}.ga-panel label{display:grid;gap:5px;flex:1;min-width:180px}.ga-panel input,.ga-panel select,.ga-panel button{border:1px solid #64748b88;border-radius:8px;padding:8px 12px;min-height:44px;background:transparent;color:inherit}.ga-panel option{background:#fff;color:#172033}.ga-panel button{cursor:pointer}.ga-panel button:disabled{opacity:.45;cursor:default}.ga-panel table{width:100%;border-collapse:collapse;min-width:640px}.ga-panel th,.ga-panel td{padding:12px;text-align:left;border-bottom:1px solid #64748b55;vertical-align:top}.ga-panel .ga-scroll{overflow:auto}.ga-panel .ga-primary{background:#b45309;color:white}.ga-panel summary{cursor:pointer;font-weight:600}.ga-panel :is(input,select,button,summary):focus-visible{outline:3px solid #3b82f6;outline-offset:2px}.ga-panel .ga-status{font-weight:700}.ga-panel .ga-error{color:#ef4444}
</style>
<h2>Guests & Contacts</h2>
<p>For attendees who are not yet in the People Database. Registering or enrolling someone does not mark them present.</p>
@if ($errors->any())<p class="ga-error" role="alert">{{ $errors->first() }}</p>@endif
@if ($mode === 'enroll' && $sheet->is_active && !$session->is_no_meeting)
<details><summary>Add from a response, or add a walk-in</summary>
<form wire:submit="enroll">
<div class="ga-fields">
<label>Registration response<select wire:model.live="responseId"><option value="">Manual walk-in / existing attendee</option>@foreach ($responses as $r)<option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->respondent_name ?: $r->guest_name ?: $r->sourceLabel() }} · {{ $r->response ?: 'No Yes/No answer' }}</option>@endforeach</select></label>
<label>Use an existing attendee<select wire:model="existingId"><option value="">Create / use the response's attendee</option>@foreach ($allGuests as $g)<option value="{{ $g->id }}">{{ $g->name }} · {{ $g->locality ?: 'No locality' }}</option>@endforeach</select></label>
<label>Enrollment<select wire:model="scope"><option value="this_meeting">This Meeting</option>@if ($sheet->schedule_type !== 'one_time')<option value="onward">From This Meeting Onward</option>@endif</select></label>
</div>
@if (!$responseId)<div class="ga-fields"><label>Name (new walk-in)<input wire:model="name" maxlength="255"></label><label>Locality (optional)<input wire:model="locality" maxlength="150"></label></div>@endif
<p>Select an existing attendee only when you have confirmed they are the same person. Matching names are never combined automatically.</p>
<button type="submit" class="ga-primary" wire:loading.attr="disabled">Enroll Attendee</button>
</form></details>
@endif
<div class="ga-fields"><label>Search attendees<input type="search" wire:model.live.debounce.350ms="search"></label>
@if ($mode==='check')<button type="button" wire:click="$toggle('showGrid')">{{ $showGrid ? 'Show Selected Date' : 'Show Recent 14 Dates' }}</button>@endif</div>
<p>{{ $active->count() }} guest/contact attendees enrolled for {{ $session->session_date->format('M j, Y') }}. People-linked attendees are shown in the existing People list.</p>
<div class="ga-scroll"><table><thead><tr><th>Attendee</th>@if ($mode==='check')@foreach ($sessions as $s)<th>{{ $s->session_date->format('M j, Y') }}</th>@endforeach @else<th>Enrollment periods</th><th>Identity</th>@endif</tr></thead><tbody>
@forelse ($guests as $g)<tr wire:key="ga-{{ $g->id }}"><td><strong>{{ $g->name }}</strong><br>{{ ucfirst($g->source_type) }} · {{ $g->locality ?: 'No locality' }}</td>
@if ($mode==='check')
@foreach ($sessions as $s)
@php $record=$records->get($s->id.':'.$g->id); $canMark=$sheet->is_active && !$s->is_no_meeting && $rosters->get($s->id)->contains((int)$g->id); @endphp
<td><div class="ga-status">{{ $s->is_no_meeting ? 'No Meeting' : ucfirst($record?->status ?? 'Not recorded') }}</div>
@if ($canMark)<select aria-label="Attendance for {{ $g->name }} on {{ $s->session_date->format('M j') }}" wire:change="mark({{ $g->id }}, $event.target.value, {{ $s->id }})" wire:loading.attr="disabled"><option value="" disabled @selected(!$record)>Choose status</option>@foreach (['present','late','absent','excused'] as $status)<option value="{{ $status }}" @selected($record?->status===$status)>{{ ucfirst($status) }}</option>@endforeach</select>@else<p>Not editable for this date.</p>@endif</td>
@endforeach
@else
<td>@forelse ($periods->where('attendance_guest_id',$g->id) as $period)<p>{{ $period->starts_on }} → {{ $period->ends_on ?: 'Onward' }} @if ($sheet->is_active)<button type="button" wire:click="deactivate({{ $period->id }})" wire:confirm="Deactivate this enrollment period? Historical attendance will be kept.">Deactivate</button>@endif</p>@empty No active enrollment periods. @endforelse</td>
<td>@if ($sheet->is_active)<button type="button" wire:click="$set('linkGuestId', {{ $g->id }})">Link to People</button>@endif</td>
@endif</tr>@empty<tr><td colspan="{{ $mode==='check' ? $sessions->count()+1 : 3 }}">No guest/contact attendees here yet.</td></tr>@endforelse
</tbody></table></div>
@if ($mode==='enroll' && $linkGuestId && $allGuests->has($linkGuestId))
<form wire:submit="link" style="margin-top:20px"><h2>Link {{ $allGuests->get($linkGuestId)->name }} to People</h2>
<p>Select an existing Person after checking their identity. Attendance and assignments will be preserved. Conflicting records will stop the link for review.</p>
<div class="ga-fields"><label>Find Person<input wire:model.live.debounce.350ms="personSearch" placeholder="First or last name"></label><label>Confirmed Person<select wire:model="linkPersonId" required><option value="">Choose a matching Person</option>@foreach ($people as $p)<option value="{{ $p->id }}">#{{ $p->id }} · {{ $p->display_name }} · {{ $p->locality ?: 'No locality' }}</option>@endforeach</select></label></div>
<button type="submit" class="ga-primary" wire:confirm="Confirm these records belong to the same person?" wire:loading.attr="disabled">Confirm Person Link</button><button type="button" wire:click="$set('linkGuestId', null)">Cancel</button>
</form>
@endif
</section>
