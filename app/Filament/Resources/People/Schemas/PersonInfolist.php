<?php

namespace App\Filament\Resources\People\Schemas;

use App\Domain\Family\FamilyRelationshipService;
use App\Filament\Pages\FamilyTree;
use App\Filament\Resources\Households\HouseholdResource;
use App\Filament\Resources\People\PersonResource;
use App\Models\ChurchProfile;
use App\Models\Household;
use App\Models\Person;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class PersonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('display_name')
                            ->label('Name'),

                        TextEntry::make('sex')
                            ->placeholder('None recorded'),

                        TextEntry::make('birthdate')
                            ->date()
                            ->placeholder('None recorded'),

                        TextEntry::make('birthplace')
                            ->placeholder('None recorded'),

                        TextEntry::make('contact_number')
                            ->placeholder('None recorded'),

                        TextEntry::make('email')
                            ->placeholder('None recorded'),
                    ])
                    ->columns(2),

                Section::make('Family Summary')
                    ->schema([
                        TextEntry::make('family_tree_link')
                            ->label('Visual Family Tree')
                            ->state(fn (Person $record): HtmlString => self::familyTreeLink($record))
                            ->html(),

                        TextEntry::make('family_household')
                            ->label('Household')
                            ->state(fn (Person $record): HtmlString => self::householdLink($record->household))
                            ->html(),

                        TextEntry::make('family_spouse')
                            ->label('Spouse')
                            ->state(fn (Person $record): HtmlString => self::personLink($record->spouse))
                            ->html(),

                        TextEntry::make('family_father')
                            ->label('Father')
                            ->state(fn (Person $record): HtmlString => self::personLink(self::family()->father($record)))
                            ->html(),

                        TextEntry::make('family_mother')
                            ->label('Mother')
                            ->state(fn (Person $record): HtmlString => self::personLink(self::family()->mother($record)))
                            ->html(),

                        TextEntry::make('family_siblings')
                            ->label('Siblings')
                            ->state(fn (Person $record): HtmlString => self::peopleLinks(self::family()->siblings($record)))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('family_children')
                            ->label('Children')
                            ->state(fn (Person $record): HtmlString => self::peopleLinks(self::family()->children($record)))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

Section::make('Shepherding Responsibility')
    ->schema([

TextEntry::make('view_people_shepherded')
    ->label('People Shepherded Link')
    ->state(fn (Person $record): HtmlString => self::peopleTableLink(
        label: 'View all people shepherded by this person',
        filters: [
            'shepherd_id' => $record->id,
        ],
    ))
    ->html()
    ->columnSpanFull(),

TextEntry::make('view_people_introduced')
    ->label('People Introduced Link')
    ->state(fn (Person $record): HtmlString => self::peopleTableLink(
        label: 'View all people introduced by this person',
        filters: [
            'introduced_by_id' => $record->id,
        ],
    ))
    ->html()
    ->columnSpanFull(),

        TextEntry::make('people_shepherded')
            ->label('People Shepherded')
            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record))
            ->html()
            ->columnSpanFull(),

        TextEntry::make('dormant_under_care')
            ->label('Dormant Under Care')
            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record, 'Dormant'))
            ->html()
            ->columnSpanFull(),

        TextEntry::make('new_ones_under_care')
            ->label('New Ones Under Care')
            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record, 'New One'))
            ->html()
            ->columnSpanFull(),

        TextEntry::make('gospel_friends_under_care')
            ->label('Gospel Friends Under Care')
            ->state(fn (Person $record): HtmlString => self::shepherdedPeople($record, 'Gospel Friend'))
            ->html()
            ->columnSpanFull(),

        TextEntry::make('people_introduced')
            ->label('People Introduced')
            ->state(fn (Person $record): HtmlString => self::introducedPeople($record))
            ->html()
            ->columnSpanFull(),
    ])
    ->columns(2),

                Section::make('Address')
                    ->schema([
                        TextEntry::make('locality')
                            ->placeholder('None recorded'),

                        TextEntry::make('home_address')
                            ->placeholder('None recorded')
                            ->columnSpanFull(),

                        TextEntry::make('permanent_address')
                            ->placeholder('None recorded')
                            ->columnSpanFull(),

                        TextEntry::make('geocoordinates')
                            ->placeholder('None recorded'),
                    ])
                    ->columns(2),

                Section::make('Church Information')
                    ->schema([
                        TextEntry::make('churchProfile.category')
                            ->label('Category')
                            ->placeholder('None recorded'),

                        TextEntry::make('churchProfile.status')
                            ->label('Status')
                            ->placeholder('None recorded'),

                        TextEntry::make('churchProfile.baptism_date')
                            ->label('Baptism Date')
                            ->date()
                            ->placeholder('None recorded'),

                        TextEntry::make('churchProfile.service')
                            ->label('Service')
                            ->placeholder('None recorded'),

                        TextEntry::make('churchProfile.shepherd.display_name')
                            ->label('Shepherd')
                            ->placeholder('None recorded'),

                        TextEntry::make('churchProfile.introducedBy.display_name')
                            ->label('Introduced By')
                            ->placeholder('None recorded'),
                    ])
                    ->columns(2),

                Section::make('Education / Work')
                    ->schema([
                        TextEntry::make('educationProfile.grade_level')
                            ->label('Grade Level')
                            ->placeholder('None recorded'),

                        TextEntry::make('educationProfile.course_strand')
                            ->label('Course / Strand')
                            ->placeholder('None recorded'),

                        TextEntry::make('educationProfile.occupation')
                            ->label('Occupation')
                            ->placeholder('None recorded'),

                        TextEntry::make('educationProfile.school_workplace')
                            ->label('School / Workplace')
                            ->placeholder('None recorded'),
                    ])
                    ->columns(2),

                Section::make('Emergency Contact')
                    ->schema([
                        TextEntry::make('emergencyContact.display_name')
                            ->label('Emergency Contact')
                            ->placeholder('None recorded'),

                        TextEntry::make('emergency_contact_relationship')
                            ->label('Relationship')
                            ->placeholder('None recorded'),

                        TextEntry::make('emergency_contact_number')
                            ->label('Contact Number')
                            ->placeholder('None recorded'),
                    ])
                    ->columns(2),
            ]);
    }

    private static function family(): FamilyRelationshipService
    {
        return app(FamilyRelationshipService::class);
    }

    private static function familyTreeLink(Person $person): HtmlString
    {
        $url = FamilyTree::getUrl([
            'personId' => $person->id,
        ]);

        return new HtmlString(
            '<a href="' . e($url) . '" class="text-primary-600 hover:underline dark:text-primary-400">Open visual family tree</a>'
        );
    }

    private static function personLink(?Person $person): HtmlString
    {
        if (! $person) {
            return self::none();
        }

        $url = PersonResource::getUrl('view', [
            'record' => $person->id,
        ]);

        return new HtmlString(
            '<a href="' . e($url) . '" class="text-primary-600 hover:underline dark:text-primary-400">'
            . e($person->display_name)
            . '</a>'
        );
    }

    private static function peopleLinks(Collection $people): HtmlString
    {
        if ($people->isEmpty()) {
            return self::none();
        }

        $links = $people
            ->sortBy('lastname')
            ->map(function (Person $person): string {
                $url = PersonResource::getUrl('view', [
                    'record' => $person->id,
                ]);

                return '<a href="' . e($url) . '" class="text-primary-600 hover:underline dark:text-primary-400">'
                    . e($person->display_name)
                    . '</a>';
            })
            ->join('<br>');

        return new HtmlString($links);
    }

    private static function householdLink(?Household $household): HtmlString
    {
        if (! $household) {
            return self::none();
        }

        $url = HouseholdResource::getUrl('view', [
            'record' => $household->id,
        ]);

        return new HtmlString(
            '<a href="' . e($url) . '" class="text-primary-600 hover:underline dark:text-primary-400">'
            . e($household->display_name)
            . '</a>'
        );
    }

private static function shepherdedPeople(Person $record, ?string $status = null): HtmlString
{
    $people = ChurchProfile::query()
        ->with('person')
        ->where('shepherd_id', $record->id)
        ->when($status, fn ($query) => $query->where('status', $status))
        ->get()
        ->pluck('person')
        ->filter()
        ->values();

    return self::peopleLinks($people);
}

private static function introducedPeople(Person $record): HtmlString
{
    $people = ChurchProfile::query()
        ->with('person')
        ->where('introduced_by_id', $record->id)
        ->get()
        ->pluck('person')
        ->filter()
        ->values();

    return self::peopleLinks($people);
}


private static function peopleTableLink(string $label, array $filters): HtmlString
{
    $queryFilters = [];

    foreach ($filters as $filter => $value) {
        $queryFilters[$filter] = [
            'value' => (string) $value,
        ];
    }

    $url = PersonResource::getUrl('index') . '?' . http_build_query([
        'filters' => $queryFilters,
    ]);

    return new HtmlString(
        '<a href="' . e($url) . '" class="text-primary-600 hover:underline dark:text-primary-400">'
        . e($label)
        . '</a>'
    );
}


    private static function none(): HtmlString
    {
        return new HtmlString('<span class="text-gray-500 dark:text-gray-400">None recorded</span>');
    }
}
