<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ChurchProfileOptions
{
    public static function categories(): array
    {
        return [
            'Children' => 'Children',
            'Junior Young People' => 'Junior Young People',
            'Young People' => 'Young People',
            'Collegian' => 'Collegian',
            'Young Adults' => 'Young Adults',
            'Middle Age' => 'Middle Age',
            'Elderly' => 'Elderly',
            'Unknown' => 'Unknown',
        ];
    }

    public static function statuses(): array
    {
return [
    'Active' => 'Active',
    'Full-Timer' => 'Full-Timer',
    'New One' => 'New One',
    'Gospel Friend' => 'Gospel Friend',
    'Visiting' => 'Visiting',
    'Dormant' => 'Dormant',
    'Moved' => 'Moved',
    'Deceased' => 'Deceased',
    'Unknown' => 'Unknown',
];
    }

    public static function shepherdingServices(): array
    {
        return [
            'Children (Toddler-Kinder)' => 'Children (Toddler-Kinder)',
            'Children (G1-G4)' => 'Children (G1-G4)',
            'Junior Young People (G5-G7)' => 'Junior Young People (G5-G7)',
            'Young People (G8-G10)' => 'Young People (G8-G10)',
            'Collegian (G11-C1)' => 'Collegian (G11-C1)',
            'Collegian (C2-Graduating)' => 'Collegian (C2-Graduating)',
            'Young Adults (Graduates - Age 45)' => 'Young Adults (Graduates - Age 45)',
            'Middle Age (Age 46 - Age 59)' => 'Middle Age (Age 46 - Age 59)',
            'Elderly (Age 60 - Above)' => 'Elderly (Age 60 - Above)',
        ];
    }

    public static function categoryFromBirthdate($birthdate): string
    {
        if (blank($birthdate)) {
            return 'Unknown';
        }

        $age = Carbon::parse($birthdate)->age;

        return match (true) {
            $age >= 1 && $age <= 9 => 'Children',
            $age >= 10 && $age <= 12 => 'Junior Young People',
            $age >= 13 && $age <= 15 => 'Young People',
            $age >= 16 && $age <= 22 => 'Collegian',
            $age >= 23 && $age <= 45 => 'Young Adults',
            $age >= 46 && $age <= 59 => 'Middle Age',
            $age >= 60 => 'Elderly',
            default => 'Unknown',
        };
    }

public static function statusColor(?string $status): string
{
return match ($status) {
    'Active' => 'success',
    'Full-Timer' => 'info',
    'New One' => 'sky',
    'Gospel Friend' => 'warning',
    'Visiting' => 'info',
    'Dormant' => 'gray',
    'Moved' => 'purple',
    'Deceased' => 'danger',
    'Unknown' => 'gray',
    default => 'gray',
};
}

public static function categoryColor(?string $category): string
{
    return match ($category) {
        'Children' => 'sky',
        'Junior Young People' => 'info',
        'Young People' => 'primary',
        'Collegian' => 'warning',
        'Young Adults' => 'success',
        'Middle Age' => 'gray',
        'Elderly' => 'purple',
        'Unknown' => 'gray',
        default => 'gray',
    };
}


}
