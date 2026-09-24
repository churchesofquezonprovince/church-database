<?php

namespace App\Filament\Resources\Households\Schemas;

use App\Filament\Pages\FamilyTree;
use App\Filament\Resources\People\PersonResource;
use App\Models\CampusContact;
use App\Models\GospelContact;
use App\Models\Household;
use App\Models\Person;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class HouseholdInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Household Overview')
                    ->schema([
                        TextEntry::make('household_overview')
                            ->label('Summary')
                            ->state(fn (Household $record): HtmlString => self::overview($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Household Information')
                    ->schema([
                        TextEntry::make('household_name')
                            ->label('Household Name')
                            ->state(fn (Household $record): HtmlString => self::value($record->household_name, important: true))
                            ->html(),

                        TextEntry::make('household_head')
                            ->label('Household Head')
                            ->state(fn (Household $record): HtmlString => self::personLink($record->head))
                            ->html(),

                        TextEntry::make('locality')
                            ->label('Locality')
                            ->state(fn (Household $record): HtmlString => self::badgeValue($record->locality))
                            ->html(),

                        TextEntry::make('members_count')
                            ->label('Total Members')
                            ->state(fn (Household $record): HtmlString => self::memberCount($record))
                            ->html(),

                        TextEntry::make('address')
                            ->label('Address')
                            ->state(fn (Household $record): HtmlString => self::longValue($record->address))
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('remarks')
                            ->label('Remarks')
                            ->state(fn (Household $record): HtmlString => self::longValue($record->remarks))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Household Members')
                    ->description(
                        'Members recorded in People, Campus Contacts, and Gospel Contacts.'
                    )
                    ->schema([
                        TextEntry::make('people_members_list')
                            ->label('People')
                            ->state(
                                fn (Household $record): HtmlString =>
                                    self::membersList($record)
                            )
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('campus_contact_members_list')
                            ->label('Campus Contacts')
                            ->state(
                                fn (Household $record): HtmlString =>
                                    self::campusContactsList($record)
                            )
                            ->html()
                            ->columnSpanFull(),

                        TextEntry::make('gospel_contact_members_list')
                            ->label('Gospel Contacts')
                            ->state(
                                fn (Household $record): HtmlString =>
                                    self::gospelContactsList($record)
                            )
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function overview(Household $record): HtmlString
    {
        $name = $record->household_name ?: 'Unnamed Household';
        $head = $record->head?->display_name ?? 'No household head';
        $locality = $record->locality ?: 'No locality';
        $membersCount =
            self::totalMemberCount($record);

        $familyTreeButton = filled($record->household_head_id)
            ? '<a href="' . e(FamilyTree::getUrl(['personId' => $record->household_head_id])) . '" class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500">Open head family tree</a>'
            : '<span class="inline-flex rounded-xl border border-dashed border-gray-300 px-4 py-2 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">No head family tree</span>';

        return new HtmlString(
            '<div class="rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 to-white p-6 dark:border-primary-900 dark:from-gray-900 dark:to-gray-950">'
            . '<div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">'
            . '<div class="flex items-center gap-4">'
            . '<div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-600 text-xl font-bold text-white shadow-sm">HH</div>'
            . '<div>'
            . '<p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Household Profile</p>'
            . '<h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">' . e($name) . '</h2>'
            . '<p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Head: ' . e($head) . ' • ' . e($locality) . '</p>'
            . '</div>'
            . '</div>'
            . $familyTreeButton
            . '</div>'
            . '<div class="mt-5 grid gap-3 md:grid-cols-3">'
            . self::summaryBox('Members', (string) $membersCount, 'border-primary-200 bg-primary-50 text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200')
            . self::summaryBox('Locality', $locality, 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200')
            . self::summaryBox('Household Head', $head, 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200')
            . '</div>'
            . '</div>'
        );
    }

    private static function summaryBox(string $label, string $value, string $tone): string
    {
        return '<div class="rounded-xl border p-4 ' . e($tone) . '">'
            . '<p class="text-xs font-semibold uppercase tracking-wide opacity-75">' . e($label) . '</p>'
            . '<p class="mt-1 text-lg font-bold">' . e($value) . '</p>'
            . '</div>';
    }

    private static function value(?string $value, bool $important = false): HtmlString
    {
        if (blank($value)) {
            return self::none();
        }

        $class = $important
            ? 'block rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-base font-bold text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200'
            : 'block rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100';

        return new HtmlString('<span class="' . e($class) . '">' . e($value) . '</span>');
    }

    private static function longValue(?string $value): HtmlString
    {
        if (blank($value)) {
            return self::none();
        }

        return new HtmlString(
            '<div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium leading-6 text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">'
            . nl2br(e($value))
            . '</div>'
        );
    }

    private static function badgeValue(?string $value): HtmlString
    {
        if (blank($value)) {
            return self::none();
        }

        return new HtmlString(
            '<span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sm font-bold text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200">'
            . e($value)
            . '</span>'
        );
    }

    private static function memberCount(Household $record): HtmlString
    {
        $count =
            self::totalMemberCount($record);

        return new HtmlString(
            '<span class="inline-flex rounded-xl border border-primary-200 bg-primary-50 px-4 py-2 text-lg font-bold text-primary-700 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e((string) $count)
            . '</span>'
        );
    }

    private static function totalMemberCount(
        Household $record
    ): int {
        return $record->members()->count()
            + $record->campusContacts()
                ->whereNull('person_id')
                ->count()
            + $record->gospelContacts()
                ->whereNull('person_id')
                ->count();
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
            '<a href="' . e($url) . '" class="inline-flex rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-bold text-primary-700 hover:bg-primary-100 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200">'
            . e($person->display_name)
            . '</a>'
        );
    }

    private static function membersList(Household $record): HtmlString
    {
        $members = $record->members()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        if ($members->isEmpty()) {
            return self::none('No members recorded');
        }

        $cards = $members
            ->map(function (Person $person): string {
                $url = PersonResource::getUrl('view', [
                    'record' => $person->id,
                ]);

                $initials = collect([$person->firstname, $person->lastname])
                    ->filter()
                    ->map(fn (string $part): string => strtoupper(substr(trim($part), 0, 1)))
                    ->join('');

                $details = collect([$person->sex, $person->contact_number])
                    ->filter()
                    ->map(fn (string $value): string => e($value))
                    ->join(' • ');

                return '<a href="' . e($url) . '" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-700 dark:hover:bg-gray-800">'
                    . '<div class="flex items-center gap-3">'
                    . '<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">' . e($initials ?: '?') . '</div>'
                    . '<div>'
                    . '<p class="font-bold text-gray-900 dark:text-white">' . e($person->display_name) . '</p>'
                    . ($details ? '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . $details . '</p>' : '')
                    . '</div>'
                    . '</div>'
                    . '</a>';
            })
            ->join('');

        return new HtmlString('<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">' . $cards . '</div>');
    }

    private static function campusContactsList(
        Household $record
    ): HtmlString {
        $contacts = $record
            ->campusContacts()
            ->whereNull('person_id')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        if ($contacts->isEmpty()) {
            return self::none(
                'No Campus Contact members recorded'
            );
        }

        $cards = $contacts
            ->map(
                function (
                    CampusContact $contact
                ): string {
                    $initials = collect([
                        $contact->firstname,
                        $contact->lastname,
                    ])
                        ->filter()
                        ->map(
                            fn (string $part): string =>
                                strtoupper(
                                    substr(
                                        trim($part),
                                        0,
                                        1
                                    )
                                )
                        )
                        ->join('');

                    $details = collect([
                        $contact->school_campus,
                        $contact->effective_locality,
                        $contact->contact_number,
                    ])
                        ->filter()
                        ->map(
                            fn ($value): string =>
                                e((string) $value)
                        )
                        ->join(' • ');

                    return
                        '<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">'
                        . '<div class="flex items-center gap-3">'
                        . '<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">'
                        . e($initials ?: '?')
                        . '</div>'
                        . '<div class="min-w-0 flex-1">'
                        . '<div class="flex flex-wrap items-center gap-2">'
                        . '<p class="font-bold text-gray-900 dark:text-white">'
                        . e($contact->display_name)
                        . '</p>'
                        . '<span class="inline-flex rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-950 dark:text-sky-200">Campus Contact</span>'
                        . '</div>'
                        . (
                            $details
                                ? '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">'
                                    . $details
                                    . '</p>'
                                : ''
                        )
                        . '</div>'
                        . '</div>'
                        . '</div>';
                }
            )
            ->join('');

        return new HtmlString(
            '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">'
            . $cards
            . '</div>'
        );
    }


    private static function gospelContactsList(
        Household $record
    ): HtmlString {
        $contacts = $record
            ->gospelContacts()
            ->whereNull('person_id')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        if ($contacts->isEmpty()) {
            return self::none(
                'No Gospel Contact members recorded'
            );
        }

        $cards = $contacts
            ->map(
                function (
                    GospelContact $contact
                ): string {
                    $initials = collect([
                        $contact->firstname,
                        $contact->lastname,
                    ])
                        ->filter()
                        ->map(
                            fn (string $part): string =>
                                strtoupper(
                                    substr(
                                        trim($part),
                                        0,
                                        1
                                    )
                                )
                        )
                        ->join('');

                    $details = collect([
                        $contact->contact_place,
                        $contact->effective_locality,
                        $contact->contact_number,
                    ])
                        ->filter()
                        ->map(
                            fn ($value): string =>
                                e((string) $value)
                        )
                        ->join(' • ');

                    return
                        '<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">'
                        . '<div class="flex items-center gap-3">'
                        . '<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">'
                        . e($initials ?: '?')
                        . '</div>'
                        . '<div class="min-w-0 flex-1">'
                        . '<div class="flex flex-wrap items-center gap-2">'
                        . '<p class="font-bold text-gray-900 dark:text-white">'
                        . e($contact->display_name)
                        . '</p>'
                        . '<span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-200">Gospel Contact</span>'
                        . '</div>'
                        . (
                            $details
                                ? '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">'
                                    . $details
                                    . '</p>'
                                : ''
                        )
                        . '</div>'
                        . '</div>'
                        . '</div>';
                }
            )
            ->join('');

        return new HtmlString(
            '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">'
            . $cards
            . '</div>'
        );
    }


    private static function none(string $message = 'Not recorded'): HtmlString
    {
        return new HtmlString(
            '<span class="block rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">'
            . e($message)
            . '</span>'
        );
    }
}
