<?php

namespace App\Filament\Pages;

use App\Models\CampusWorkActivity;
use App\Models\CampusWorkTerm;
use App\Models\Person;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CampusActivities extends Page
{
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.campus-activities';

    public function getTitle(): string
    {
        return 'Campus Activities';
    }

    public static function getNavigationLabel(): string
    {
        return 'Campus Activities';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Campus Work';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageRecords() ?? false;
    }

    public function terms(): Collection
    {
        return CampusWorkTerm::query()
            ->where('is_archived', false)
            ->orderByDesc('is_active')
            ->orderByDesc('academic_year')
            ->orderByDesc('id')
            ->get();
    }

    public function selectedTerm(): ?CampusWorkTerm
    {
        $termId = request()->integer('termId');

        if ($termId) {
            $term = CampusWorkTerm::query()->find($termId);

            if ($term) {
                return $term;
            }
        }

        return CampusWorkTerm::query()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->latest('id')
            ->first()
            ?? CampusWorkTerm::query()
                ->where('is_archived', false)
                ->latest('id')
                ->first();
    }

    public function selectedType(): string
    {
        $type = (string) request('type', '');

        return array_key_exists(
            $type,
            CampusWorkActivity::typeOptions()
        )
            ? $type
            : '';
    }

    public function activities(): Collection
    {
        $term = $this->selectedTerm();

        return CampusWorkActivity::query()
            ->with(['term'])
            ->when(
                $term,
                fn ($query) =>
                    $query->where('campus_work_term_id', $term->id)
            )
            ->when(
                $this->selectedType(),
                fn ($query, $type) =>
                    $query->where('activity_type', $type)
            )
            ->orderByDesc('activity_date')
            ->orderByDesc('start_time')
            ->get();
    }

    public function activityTypeOptions(): array
    {
        return CampusWorkActivity::typeOptions();
    }

    public function termUrl(?CampusWorkTerm $term): string
    {
        $query = [];

        if ($term) {
            $query['termId'] = $term->id;
        }

        if ($this->selectedType()) {
            $query['type'] = $this->selectedType();
        }

        return static::getUrl()
            . ($query ? '?' . http_build_query($query) : '');
    }

    public function typeUrl(string $type): string
    {
        $query = [];

        if ($this->selectedTerm()) {
            $query['termId'] = $this->selectedTerm()->id;
        }

        if ($type !== '') {
            $query['type'] = $type;
        }

        return static::getUrl()
            . ($query ? '?' . http_build_query($query) : '');
    }

    public function schoolOptions(): Collection
    {
        return Person::query()
            ->whereHas(
                'educationProfile',
                fn ($query) =>
                    $query->whereNotNull('school_workplace')
                        ->where('school_workplace', '!=', '')
            )
            ->with(['educationProfile'])
            ->get()
            ->map(
                fn (Person $person): ?string =>
                    $person->educationProfile?->school_workplace
            )
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    public function localityOptions(): Collection
    {
        return Person::query()
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->distinct()
            ->orderBy('locality')
            ->pluck('locality');
    }
}
