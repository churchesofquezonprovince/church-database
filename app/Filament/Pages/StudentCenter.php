<?php

namespace App\Filament\Pages;

use App\Models\CampusContact;
use App\Models\CampusWorkStudentCenter;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentCenter extends Page
{
    protected static ?string $slug = 'campus-work/student-center';

    protected string $view = 'filament.pages.student-center';

    public static function getNavigationLabel(): string
    {
        return 'Student Center';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Campus Work';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-library';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public function getTitle(): string
    {
        return 'Student Center';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function centers(): Collection
    {
        $search = trim((string) request('search', ''));
        $school = trim((string) request('school', ''));
        $locality = trim((string) request('locality', ''));

        return CampusWorkStudentCenter::query()
            ->with([
                'members.contact',
            ])
            ->withCount('members')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('school_campus', 'like', "%{$search}%")
                        ->orWhere('locality', 'like', "%{$search}%")
                        ->orWhere('place', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($school !== '', fn ($query) => $query->where('school_campus', $school))
            ->when($locality !== '', fn ($query) => $query->where('locality', $locality))
            ->orderBy('locality')
            ->orderBy('school_campus')
            ->orderBy('name')
            ->get();
    }

    public function schoolOptions(): Collection
    {
        $fromContacts = CampusContact::query()
            ->whereNotNull('school_campus')
            ->where('school_campus', '!=', '')
            ->distinct()
            ->pluck('school_campus');

        $fromCenters = CampusWorkStudentCenter::query()
            ->whereNotNull('school_campus')
            ->where('school_campus', '!=', '')
            ->distinct()
            ->pluck('school_campus');

        return $fromContacts
            ->merge($fromCenters)
            ->filter()
            ->unique()
            ->sort(fn ($a, $b) => strcasecmp((string) $a, (string) $b))
            ->values();
    }

    public function localityOptions(): Collection
    {
        $fromContacts = CampusContact::query()
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->distinct()
            ->pluck('locality');

        $fromCenters = CampusWorkStudentCenter::query()
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->distinct()
            ->pluck('locality');

        return $fromCenters
            ->merge($fromContacts)
            ->filter()
            ->unique()
            ->sort(fn ($a, $b) => strcasecmp((string) $a, (string) $b))
            ->values();
    }

    public function availableContactsForCenter(CampusWorkStudentCenter $center): Collection
    {
        $addedContactIds = $center->members()
            ->pluck('campus_contact_id')
            ->all();

        return CampusContact::query()
            ->whereNotIn('id', $addedContactIds)
            ->orderBy('school_campus')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(300)
            ->get();
    }

    public function contactName(?CampusContact $contact): string
    {
        if (! $contact) {
            return 'Unknown contact';
        }

        $first = $this->contactField($contact, 'firstname');
        $last = $this->contactField($contact, 'lastname');

        $name = trim(collect([$first, $last])->filter()->implode(' '));

        return $name !== '' ? $name : 'Unnamed contact';
    }

    public function contactField(?CampusContact $contact, string $field): ?string
    {
        if (! $contact) {
            return null;
        }

        $effectiveField = 'effective_' . $field;

        return data_get($contact, $effectiveField) ?: data_get($contact, $field);
    }

    public function contactSearchText(CampusContact $contact): string
    {
        return Str::lower(collect([
            $this->contactName($contact),
            $this->contactField($contact, 'school_campus'),
            $this->contactField($contact, 'locality'),
            $this->contactField($contact, 'course_strand'),
            $this->contactField($contact, 'grade_level'),
            $this->contactField($contact, 'contact_number'),
            $this->contactField($contact, 'email'),
            $this->contactField($contact, 'facebook_account'),
        ])->filter()->implode(' '));
    }
}
