<?php

namespace App\Filament\Pages;

use App\Models\CampusContact;
use App\Models\CampusWorkStudentCenter;
use App\Models\Locality;
use App\Models\ProvinceSetting;
use App\Models\School;
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
        $schoolId = (int) request('school_id', 0);
        $localityId = (int) request('locality_id', 0);

        return CampusWorkStudentCenter::query()
            ->with([
                'school',
                'members.contact.school',
            ])
            ->withCount('members')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas(
                            'school',
                            function ($schoolQuery) use ($search): void {
                                $schoolQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('short_name', 'like', "%{$search}%");
                            }
                        )
                        ->orWhere('locality', 'like', "%{$search}%")
                        ->orWhere('place', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when(
                $schoolId > 0,
                fn ($query) => $query->where('school_id', $schoolId)
            )
            ->when($localityId > 0, fn ($query) => $query->where('locality_id', $localityId))
            ->orderBy('locality')
            ->orderBy(
                School::query()
                    ->select('name')
                    ->whereColumn(
                        'schools.id',
                        'campus_work_student_centers.school_id'
                    )
                    ->limit(1)
            )
            ->orderBy('name')
            ->get();
    }

    public function schoolOptions(): Collection
    {
        return School::query()
            ->with('province')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function localityOptions(): Collection
    {
        $provinceId = ProvinceSetting::query()
            ->value('primary_province_id');

        if (! $provinceId) {
            return collect();
        }

        return Locality::query()
            ->where('province_id', $provinceId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function availableContactsForCenter(CampusWorkStudentCenter $center): Collection
    {
        $addedContactIds = $center->members()
            ->pluck('campus_contact_id')
            ->all();

        return CampusContact::query()
            ->with('school')
            ->whereNotIn('id', $addedContactIds)
            ->orderBy(
                School::query()
                    ->select('name')
                    ->whereColumn(
                        'schools.id',
                        'campus_contacts.school_id'
                    )
                    ->limit(1)
            )
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

    public function contactSchoolName(
        ?CampusContact $contact
    ): ?string {
        return $contact?->school?->name;
    }

    public function contactSearchText(CampusContact $contact): string
    {
        return Str::lower(collect([
            $this->contactName($contact),
            $this->contactSchoolName($contact),
            $this->contactField($contact, 'locality'),
            $this->contactField($contact, 'course_strand'),
            $this->contactField($contact, 'grade_level'),
            $this->contactField($contact, 'contact_number'),
            $this->contactField($contact, 'email'),
            $this->contactField($contact, 'facebook_account'),
        ])->filter()->implode(' '));
    }
}
