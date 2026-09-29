<x-filament-panels::page>
<div class="cq-conf">
<style>
.cq-conf{--cq-border:#d1d5db;--cq-bg:#fff;--cq-muted:#4b5563;color:#111827;display:grid;gap:20px}.dark .cq-conf{--cq-border:#374151;--cq-bg:#18181b;--cq-muted:#d1d5db;color:#f3f4f6}
.cq-conf .cq-card{border:1px solid var(--cq-border);border-radius:14px;padding:20px;background:var(--cq-bg)}.cq-conf h2,.cq-conf summary{font-size:1.05rem;font-weight:700}.cq-conf summary{cursor:pointer}.cq-conf p{margin:8px 0;color:var(--cq-muted)}.cq-conf .cq-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-top:14px}.cq-conf label{display:grid;gap:6px;font-size:.9rem}.cq-conf input:not([type=checkbox]),.cq-conf select{width:100%;min-width:0;border:1px solid var(--cq-border);border-radius:8px;background:var(--cq-bg);color:inherit;padding:9px;min-height:44px}.cq-conf input[type=color]{padding:3px}.cq-conf .cq-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:14px}.cq-conf button,.cq-conf .cq-link{border:1px solid var(--cq-border);border-radius:8px;padding:8px 12px;min-height:40px;cursor:pointer}.cq-conf .cq-primary{background:#b45309;color:white;border-color:#b45309}.cq-conf button:disabled{opacity:.45;cursor:default}.cq-conf .cq-table{overflow-x:auto;margin-top:14px}.cq-conf table{width:100%;border-collapse:collapse;min-width:720px}.cq-conf th,.cq-conf td{text-align:left;padding:12px;border-bottom:1px solid var(--cq-border);vertical-align:top}.cq-conf .cq-badge{display:inline-flex;align-items:center;gap:6px}.cq-conf .cq-dot{width:14px;height:14px;border-radius:50%;display:inline-block;border:1px solid #888}.cq-conf .cq-error{color:#ef4444}.cq-conf .cq-stat{font-size:1.6rem;font-weight:700}.cq-conf .cq-check{display:flex;align-items:center;gap:10px}.cq-conf .cq-dialog{position:fixed;inset:0;margin:auto;max-width:600px;width:calc(100vw - 32px);max-height:80dvh;overflow:auto;border:1px solid var(--cq-border);border-radius:16px;padding:24px;background:var(--cq-bg);color:inherit}.cq-conf .cq-dialog::backdrop{background:#0008}.cq-conf :is(button,a,input,select,summary):focus-visible{outline:3px solid #3b82f6;outline-offset:3px}
</style>
@if ($errors->any())<div class="cq-card cq-error" role="alert">{{ $errors->first() }}</div>@endif
<div class="cq-card">
    <label>Open a conference
        <select wire:model.live="eventId"><option value="">Choose a conference</option>
            @foreach ($events as $event)<option value="{{ $event->id }}">{{ $event->title }} — {{ $event->activity_type }}</option>@endforeach
        </select>
    </label>
</div>
<details class="cq-card" @if (! $data) open @endif>
    <summary>Set Up a Conference</summary>
    <p>Choose an existing attendance sheet and the dates included in this conference. Registration and check-in continue on the existing pages.</p>
    <form wire:submit="createConference">
        <div class="cq-grid">
            <label>Attendance sheet<select wire:model.live="setupSheetId" required><option value="">Choose a sheet</option>@foreach ($sheets as $sheet)<option value="{{ $sheet->id }}">{{ $sheet->title }}</option>@endforeach</select></label>
            <label>Activity type<input wire:model="activityType" list="cq-activity-types" maxlength="100" required></label>
            <datalist id="cq-activity-types"><option value="YP Blending"><option value="Conference"><option value="Churching"><option value="Training"><option value="Other Activity"></datalist>
        </div>
        <div class="cq-grid">@foreach ($setupSessions as $session)<label class="cq-check"><input type="checkbox" wire:model="setupSessionIds" value="{{ $session->id }}">{{ $session->dateTimeLabel() }} @if ($session->location)— {{ $session->location }}@endif</label>@endforeach</div>
        <div class="cq-actions"><button class="cq-primary" type="submit" wire:loading.attr="disabled">Create Conference Workspace</button></div>
    </form>
</details>
@if ($data)
@php $roles = ['young_people'=>'Young People', 'serving_one'=>'Serving One', 'other'=>'Other']; @endphp
<div class="cq-card">
    <p>{{ $data['event']->activity_type }}</p>
    @if (! $data['sheet']->is_active)<p>This attendance sheet is archived. Its conference records remain available; existing attendance pages may require reactivating the sheet.</p>@endif
    <label>Meeting date<select wire:model.live="sessionId"><option value="">All conference dates</option>@foreach ($data['sessions'] as $session)<option value="{{ $session->id }}">{{ $session->dateTimeLabel() }} — {{ $session->location ?: 'No location set' }}</option>@endforeach</select></label>
    <div class="cq-actions">
    @foreach ($data['scope'] as $session)
        <a class="cq-link" href="{{ \App\Filament\Pages\AttendanceSheets::getUrl(['sheetId'=>$data['sheet']->id,'sessionId'=>$session->id]) }}">Registration & Participants · {{ $session->session_date->format('M j') }}</a>
        @if ($data['sheet']->is_active)<a class="cq-link" href="{{ \App\Filament\Pages\CheckAttendance::getUrl(['sheetId'=>$data['sheet']->id,'sessionId'=>$session->id]) }}">Check Attendance · {{ $session->session_date->format('M j') }}</a>@endif
        @if ($data['sheet']->meetingFormEnabled() && $session->publicMeetingUrl())<a class="cq-link" target="_blank" rel="noopener" href="{{ $session->publicMeetingUrl() }}">Open Form · {{ $session->session_date->format('M j') }}</a>@endif
    @endforeach
    </div>
    <div class="cq-grid">
        @foreach (['roster'=>'People on Roster','attended'=>'People Attended','responses'=>'Form Responses','yes'=>'Yes Responses','review'=>'Responses Needing Review'] as $key=>$label)
        <div><div class="cq-stat">{{ $data['totals'][$key] }}</div>{{ $label }}</div>
        @endforeach
    </div>
    <p>Totals cover the selected dates before table filters. People are counted once across dates. Form responses are submissions and may include the same person on different dates. Present and Late count as attended.</p>
</div>
<details class="cq-card" open><summary>Teams</summary>
    <div class="cq-actions">@foreach ($data['teams'] as $team)<button type="button" wire:click="editTeam({{ $team->id }})"><span class="cq-dot" style="background:{{ preg_match('/^#[0-9a-fA-F]{6}$/', $team->color) ? $team->color : '#888888' }}"></span> {{ $team->name }} ({{ $all['rows']->where('team_id',$team->id)->count() }})</button>@endforeach</div>
    <form wire:submit="saveTeam"><div class="cq-grid"><label>Team name<input wire:model="teamName" maxlength="100" required></label><label>Team color<input type="color" wire:model="teamColor"></label></div>
        @php
            $presetColors = [
                'Rose' => '#CC0000',
                'Lily' => '#FCE5CD',
                'Peony' => '#C27BA0',
                'Lavender' => '#B4A7D6',
                'Hydrangea' => '#A4C2F4',
                'Cymbidium' => '#B6D7A8',
                'Violet' => '#674EA7',
                'Black Iris' => '#434343',
                'Marigold' => '#F6B26B',
                'Daisy' => '#FFFFFF',
            ];
        @endphp
        <div class="cq-actions">
            <span>Preset colors:</span>
            @foreach ($presetColors as $presetName => $color)
                <button
                    type="button"
                    title="{{ $presetName }} · {{ $color }}"
                    aria-label="Use {{ $presetName }} color {{ $color }}"
                    wire:click="$set('teamColor', '{{ $color }}')"
                >
                    <span
                        class="cq-dot"
                        style="background:{{ $color }}"
                    ></span>
                    {{ $presetName }}
                </button>
            @endforeach
        </div>
        <div class="cq-actions"><button type="submit" class="cq-primary" wire:loading.attr="disabled">{{ $teamEditId ? 'Save Team' : 'Add Team' }}</button>@if ($teamEditId)<button type="button" wire:click="cancelTeam">Cancel Edit</button>@endif</div>
    </form>
</details>
<details class="cq-card" open>
    <summary>Participant Columns</summary>

    <p>
        Add conference-specific columns just like extra
        spreadsheet columns. Optional checkbox fields preserve
        three states: Unknown, Yes, and No.
    </p>

    @if ($participantFields->isNotEmpty())
        <div class="cq-table">
            <table>
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($participantFields as $field)
                        <tr
                            wire:key="conference-field-{{ $field->id }}"
                        >
                            <td>
                                {{ $field->name }}
                            </td>

                            <td>
                                {{
                                    $participantFieldTypes[
                                        $field->field_type
                                    ]
                                    ?? $field->field_type
                                }}
                            </td>

                            <td>
                                {{
                                    $field->is_required
                                        ? 'Yes'
                                        : 'No'
                                }}
                            </td>

                            <td>
                                <button
                                    type="button"
                                    wire:click="editParticipantField({{ $field->id }})"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteParticipantField({{ $field->id }})"
                                    wire:confirm="Remove this participant column and all values stored in it?"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>
            No custom participant columns yet.
        </p>
    @endif

    <form wire:submit="saveParticipantField">
        <div class="cq-grid">
            <label>
                Column name

                <input
                    wire:model="fieldName"
                    maxlength="100"
                    placeholder="e.g. With Invite?"
                    required
                >

                @error('fieldName')
                    <span>{{ $message }}</span>
                @enderror
            </label>

            <label>
                Column type

                <select
                    wire:model.live="fieldType"
                    required
                >
                    @foreach (
                        $participantFieldTypes
                        as $type => $label
                    )
                        <option value="{{ $type }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('fieldType')
                    <span>{{ $message }}</span>
                @enderror
            </label>

            <label>
                <span>
                    Required value
                </span>

                <input
                    type="checkbox"
                    wire:model="fieldRequired"
                >
            </label>

            @if (
                $fieldType
                ===
                \App\Services\ConferenceParticipantFields::TYPE_SELECT
            )
                <label>
                    Dropdown options
                    <textarea
                        wire:model="fieldOptionsText"
                        rows="5"
                        placeholder="One option per line"
                    ></textarea>

                    @error('fieldOptions')
                        <span>{{ $message }}</span>
                    @enderror
                </label>
            @endif
        </div>

        <div class="cq-actions">
            <button
                type="submit"
                class="cq-primary"
                wire:loading.attr="disabled"
            >
                {{
                    $fieldEditId
                        ? 'Save Column'
                        : 'Add Column'
                }}
            </button>

            @if ($fieldEditId)
                <button
                    type="button"
                    wire:click="cancelParticipantField"
                >
                    Cancel Edit
                </button>
            @endif
        </div>
    </form>
</details>

<div class="cq-card"><h2>Participants</h2>
    <p>People, enrolled guests, and contacts share conference totals, teams, and invitations. Enroll guests through Attendance Sheets; check them in through Check Attendance.</p>
    <div class="cq-grid">
        <label>Search names<input type="search" wire:model.live.debounce.350ms="search"></label>
        <label>Locality<select wire:model.live="localityFilter"><option value="">All localities</option>@foreach ($localities as $locality)<option>{{ $locality }}</option>@endforeach</select></label>
        <label>Attendance<select wire:model.live="attendanceFilter"><option value="">All</option><option value="roster">On roster</option><option value="attended">Attended</option><option value="not_attended">No Present / Late record</option><option value="unmarked">No attendance record</option></select></label>
        <label>Team<select wire:model.live="teamFilter"><option value="">All teams</option><option value="unassigned">Unassigned</option>@foreach ($data['teams'] as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach</select></label>
        <label>Event role<select wire:model.live="roleFilter"><option value="">All roles</option><option value="unassigned">Unassigned</option>@foreach ($roles as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
        <label>Filter by form question<select wire:model.live="questionFilter"><option value="">No question filter</option>@foreach ($questions as $q)<option value="{{ $q->id }}">{{ $q->question_text }}</option>@endforeach</select></label>
        @if ($questionFilter)<label>Answer<select wire:model.live="answerFilter"><option value="">All answers</option><option value="__unanswered">Not answered</option>@foreach ($answerOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>@endif
    </div>
    <p>A question filter also filters Form Responses below. Across multiple dates, a person matches if any of their responses contains the chosen answer.</p>
    @if ($editPersonId && $all['rows']->has($editPersonId))
    <form wire:submit="savePerson" class="cq-card" style="margin-top:16px"><h2>Assign {{ $all['rows']->get($editPersonId)['name'] }}</h2><div class="cq-grid">
        <label>Team<select wire:model="personTeamId"><option value="">Unassigned</option>@foreach ($data['teams'] as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach</select></label>
        <label>Event role<select wire:model="personRole"><option value="">Unassigned</option>@foreach ($roles as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>

        @foreach ($participantFields as $field)
            <label>
                {{ $field->name }}
                @if ($field->is_required)
                    *
                @endif

                @if (
                    $field->field_type
                    ===
                    \App\Services\ConferenceParticipantFields::TYPE_CHECKBOX
                )
                    <select
                        wire:model="personFieldValues.{{ $field->id }}"
                        @if ($field->is_required) required @endif
                    >
                        <option value="">
                            Unknown
                        </option>
                        <option value="1">
                            Yes
                        </option>
                        <option value="0">
                            No
                        </option>
                    </select>

                @elseif (
                    $field->field_type
                    ===
                    \App\Services\ConferenceParticipantFields::TYPE_SELECT
                )
                    <select
                        wire:model="personFieldValues.{{ $field->id }}"
                        @if ($field->is_required) required @endif
                    >
                        <option value="">
                            Not entered
                        </option>

                        @foreach ($field->options as $option)
                            <option value="{{ $option }}">
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>

                @elseif (
                    $field->field_type
                    ===
                    \App\Services\ConferenceParticipantFields::TYPE_NUMBER
                )
                    <input
                        type="number"
                        step="any"
                        wire:model="personFieldValues.{{ $field->id }}"
                        @if ($field->is_required) required @endif
                    >

                @elseif (
                    $field->field_type
                    ===
                    \App\Services\ConferenceParticipantFields::TYPE_DATE
                )
                    <input
                        type="date"
                        wire:model="personFieldValues.{{ $field->id }}"
                        @if ($field->is_required) required @endif
                    >

                @else
                    <input
                        type="text"
                        maxlength="500"
                        wire:model="personFieldValues.{{ $field->id }}"
                        @if ($field->is_required) required @endif
                    >
                @endif
            </label>
        @endforeach

        @error('participantFieldValue')
            <div>
                {{ $message }}
            </div>
        @enderror
    </div><div class="cq-actions"><button class="cq-primary" type="submit" wire:loading.attr="disabled">Save Assignment</button><button type="button" wire:click="$set('editPersonId', null)">Cancel</button></div></form>
    @endif
    <div class="cq-table"><table><thead><tr><th>Name</th><th>Locality</th><th>Roster / Attendance</th><th>Team / Role</th>
        @foreach ($participantFields as $field)
            <th>
                {{ $field->name }}
            </th>
        @endforeach
        <th>Invited</th><th>Action</th></tr></thead><tbody>
    @forelse ($rows as $row)<tr wire:key="cq-person-{{ $eventId }}-{{ $row['id'] }}"><td><button type="button" wire:click="showPerson({{ $row['id'] }})">{{ $row['name'] }}</button></td><td>{{ $row['locality'] }}</td><td>{{ $row['roster'] ? 'On roster' : 'Not on roster' }}<br>{{ $row['attended'] ? 'Attended' : ($row['recorded'] ? 'No Present / Late record' : 'No attendance record') }}</td><td>{{ $row['team']?->name ?? 'No team' }}<br>{{ $roles[$row['role']] ?? 'Role unassigned' }}</td>

        @foreach ($participantFields as $field)
            @php
                $participantFieldValue =
                    $participantFieldValues->get(
                        \App\Services\ConferenceParticipantFields::valueKey(
                            (int) $field->id,
                            (int) $row['id']
                        )
                    );
            @endphp

            <td>
                @if (
                    $field->field_type
                    ===
                    \App\Services\ConferenceParticipantFields::TYPE_CHECKBOX
                )
                    @if ($participantFieldValue === true)
                        ☑ Yes
                    @elseif ($participantFieldValue === false)
                        ☐ No
                    @else
                        —
                    @endif
                @else
                    {{
                        $participantFieldValue === null
                            || $participantFieldValue === ''
                                ? '—'
                                : $participantFieldValue
                    }}
                @endif
            </td>
        @endforeach

        <td>
        @foreach ($row['invites'] as $id) @if ($all['rows']->has($id))<button type="button" title="{{ $all['rows']->get($id)['name'] }}" wire:click="showPerson({{ $id }})">{{ $all['rows']->get($id)['name'] }}</button>@endif @endforeach
    </td><td><button type="button" wire:click="editPerson({{ $row['id'] }})">Assign</button></td></tr>
    @empty<tr><td colspan="{{ 6 + $participantFields->count() }}">No attendees match these filters.</td></tr>@endforelse
    </tbody></table></div>
    <div class="cq-actions"><button type="button" wire:click="changePage({{ $currentPage - 1 }})" @disabled($currentPage <= 1)>Previous</button><span>{{ $rowCount }} people · Page {{ $currentPage }} of {{ $lastPage }}</span><button type="button" wire:click="changePage({{ $currentPage + 1 }})" @disabled($currentPage >= $lastPage)>Next</button></div>
</div>
<details class="cq-card"><summary>Invitations</summary><p>Select who invited whom. Guests and contacts can be assigned without creating a Person. Saving replaces any previously recorded inviter for the invited person.</p>
    <form wire:submit="saveInvitation"><div class="cq-grid">@foreach (['inviterId'=>'Invited by','inviteeId'=>'Invited person'] as $field=>$label)<label>{{ $label }}<select wire:model="{{ $field }}" required><option value="">Choose a person</option>@foreach ($all['rows'] as $row)<option value="{{ $row['id'] }}">{{ $row['name'] }}</option>@endforeach</select></label>@endforeach</div><div class="cq-actions"><button class="cq-primary" type="submit" wire:loading.attr="disabled">Save Invitation Link</button></div></form>
    @foreach ($all['invitations'] as $invite)<div class="cq-actions"><span>{{ $all['rows']->get($invite->inviter_person_id)['name'] ?? 'Person no longer in conference list' }} invited {{ $all['rows']->get($invite->invitee_person_id)['name'] ?? 'Person no longer in conference list' }}</span><button type="button" wire:click="removeInvitation({{ $invite->id }})" wire:confirm="Remove this invitation link?">Remove Link</button></div>@endforeach
</details>
<details class="cq-card"><summary>Form Responses ({{ $responses->count() }})</summary>
    <p>Responses remain in the existing attendance system. Names are not automatically merged. Review status follows the same rules as Attendance Sheets.</p>
    <label>Response filter<select wire:model.live="responseFilter"><option value="">All</option><option value="yes">Yes</option><option value="no">No</option><option value="review">Needs review</option></select></label>
    <div class="cq-table"><table><thead><tr><th>Name / Source</th><th>Date</th><th>Response</th><th>Review</th><th>Answers</th></tr></thead><tbody>
    @forelse ($responses as $response)<tr wire:key="cq-response-{{ $response->id }}"><td>{{ $response->respondent_name ?: $response->guest_name ?: ($all['rows']->get($response->person_id)['name'] ?? 'Response #'.$response->id) }}<br>{{ $response->sourceLabel() }}</td><td>{{ $data['sessions']->firstWhere('id',$response->attendance_session_id)?->session_date?->format('M j, Y') }}</td><td>{{ ucfirst($response->response ?? 'Not answered') }}</td><td>{{ $data['workflow']->get($response->id)['label'] ?? 'Review' }}<br><a href="{{ \App\Filament\Pages\AttendanceSheets::getUrl(['sheetId'=>$data['sheet']->id,'sessionId'=>$response->attendance_session_id]) }}">Open Attendance Sheet</a></td><td><details><summary>Show answers</summary>@foreach ($response->formAnswers as $answer)<p><strong>{{ $answer->question?->question_text ?? 'Removed question' }}</strong><br>{{ implode(', ', \App\Services\ConferenceWorkspace::answerValues($answer)) ?: 'Not answered' }}</p>@endforeach</details></td></tr>
    @empty<tr><td colspan="5">No form responses match.</td></tr>@endforelse
    </tbody></table></div>
</details>
@if ($detailPersonId && $all['rows']->has($detailPersonId))
@php $detail = $all['rows']->get($detailPersonId); @endphp
<dialog class="cq-dialog" wire:key="cq-detail-{{ $detailPersonId }}" x-data x-init="$el.showModal()" x-on:cancel.prevent="$wire.closePerson()" aria-labelledby="cq-person-title">
    <h2 id="cq-person-title">{{ $detail['name'] }}</h2><p>{{ $detail['locality'] }}</p><p>Team: {{ $detail['team']?->name ?? 'Unassigned' }}<br>Role: {{ $roles[$detail['role']] ?? 'Unassigned' }}</p>
    <p>{{ $detail['roster'] ? 'On the roster for at least one conference date.' : 'Not on the roster for these conference dates.' }} {{ $detail['attended'] ? 'Attended at least one conference date.' : 'No Present / Late record for the conference dates.' }}</p>
    <div class="cq-actions">@if ($detailPersonId > 0)<a class="cq-link" href="{{ \App\Filament\Resources\People\PersonResource::getUrl('view', ['record'=>$detailPersonId]) }}">View Person</a>@else<a class="cq-link" href="{{ \App\Filament\Pages\AttendanceSheets::getUrl(['sheetId'=>$data['sheet']->id]) }}">Open Guest Enrollment</a>@endif<button type="button" wire:click="closePerson">Close</button></div>
</dialog>
@endif
@endif
</div>
</x-filament-panels::page>
