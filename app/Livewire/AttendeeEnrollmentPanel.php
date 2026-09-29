<?php

namespace App\Livewire;

use App\Models\{CampusContact, GospelContact, Person};
use App\Services\AttendeeEnrollment;
use Filament\Notifications\Notification;

class AttendeeEnrollmentPanel extends GuestAttendancePanel
{
    public string $addSource = 'person';
    public string $identitySearch = '';
    public ?int $identityId = null;
    public string $sourceFilter = '';
    public bool $showHistory = false;

    public function updatedAddSource(): void
    {
        $this->reset('identityId', 'identitySearch', 'responseId', 'existingId', 'name', 'locality');
        $this->resetValidation();
    }

    public function updatedIdentitySearch(): void { $this->identityId = null; }

    public function addAttendee(): void
    {
        abort_unless($this->mode === 'enroll', 403);
        $this->validate(['addSource' => 'required|in:person,campus,gospel,guest']);
        if ($this->addSource === 'guest') {
            $this->responseId = null;
            $this->enroll();
            return;
        }
        $this->validate(['identityId' => 'required|integer|min:1']);
        AttendeeEnrollment::add($this->sheetId, $this->sessionId, $this->addSource, $this->identityId, $this->scope);
        $this->reset('identityId', 'identitySearch');
        Notification::make()->title('Attendee enrolled. Attendance has not been marked.')->success()->send();
        $this->dispatch('guest-attendance-updated');
    }

    public function render()
    {
        $data = parent::render()->getData();
        $rows = AttendeeEnrollment::rows($this->sheetId, $this->sessionId, $this->showHistory);
        $data['totalAttendees'] = $rows->count();
        if ($this->sourceFilter !== '') {
            $rows = $rows->filter(fn ($r) => $this->sourceFilter === 'guest'
                ? in_array($r['source'], ['guest', 'manual'], true) : $r['source'] === $this->sourceFilter);
        }
        if (trim($this->search) !== '') {
            $needle = mb_strtolower(trim($this->search));
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['name'].' '.$r['locality']), $needle));
        }
        $data['attendeeRows'] = $rows;
        $data['identityChoices'] = collect();
        $model = match ($this->addSource) {
            'person' => Person::class, 'campus' => CampusContact::class, 'gospel' => GospelContact::class, default => null,
        };
        if ($model && trim($this->identitySearch) !== '') {
            $term = '%'.trim($this->identitySearch).'%';
            $data['identityChoices'] = $model::query()->where(fn ($q) => $q->where('firstname', 'like', $term)
                ->orWhere('lastname', 'like', $term))->orderBy('lastname')->limit(30)->get();
        }
        return view('livewire.attendee-enrollment-panel', $data);
    }
}
