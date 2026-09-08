<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Locality;
use App\Models\Person;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function people(): StreamedResponse
    {
        $this->authorizeExport();

        $rows = Person::query()
            ->with(['churchProfile.shepherd', 'churchProfile.introducedBy', 'household'])
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->lazy(100)
            ->map(fn (Person $person): array => [
                $person->id,
                $person->firstname,
                $person->middlename,
                $person->lastname,
                $person->suffix,
                $person->nickname,
                $person->sex,
                $person->birthdate,
                $person->locality,
                $person->contact_number,
                $person->email,
                $person->churchProfile?->status,
                $person->churchProfile?->category,
                $person->churchProfile?->service,
                $person->churchProfile?->shepherd?->display_name,
                $person->churchProfile?->contact_origin,
                $person->churchProfile?->first_contact_date,
                $person->churchProfile?->introducedBy?->display_name,
                $person->churchProfile?->contact_origin_details,
                $person->household?->household_name,
                $person->home_address,
                $person->permanent_address,
                $person->created_at,
            ]);

        return $this->csv('people', [
            'ID',
            'First Name',
            'Middle Name',
            'Last Name',
            'Suffix',
            'Nickname',
            'Sex',
            'Birthdate',
            'Locality',
            'Contact Number',
            'Email',
            'Status',
            'Category',
            'Shepherding Group',
            'Shepherd',
            'Contact Origin',
            'First Contact Date',
            'Introduced By',
            'Origin Details',
            'Household',
            'Home Address',
            'Permanent Address',
            'Created At',
        ], $rows);
    }

    public function households(): StreamedResponse
    {
        $this->authorizeExport();

        $rows = Household::query()
            ->with(['head'])
            ->withCount('members')
            ->orderBy('household_name')
            ->lazy(100)
            ->map(fn (Household $household): array => [
                $household->id,
                $household->household_name,
                $household->head?->display_name,
                $household->locality,
                $household->address,
                $household->members_count,
                $household->remarks,
                $household->created_at,
            ]);

        return $this->csv('households', [
            'ID',
            'Household Name',
            'Household Head',
            'Locality',
            'Address',
            'Members Count',
            'Remarks',
            'Created At',
        ], $rows);
    }

    public function localitySummary(): StreamedResponse
    {
        $this->authorizeExport();

        $peopleByLocality = Person::query()
            ->whereNotNull('locality_id')
            ->selectRaw('locality_id, COUNT(*) as total')
            ->groupBy('locality_id')
            ->pluck('total', 'locality_id');

        $householdsByLocality = Household::query()
            ->whereNotNull('locality_id')
            ->selectRaw('locality_id, COUNT(*) as total')
            ->groupBy('locality_id')
            ->pluck('total', 'locality_id');

        $localityIds = collect()
            ->merge($peopleByLocality->keys())
            ->merge($householdsByLocality->keys())
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $rows = Locality::query()
            ->with('province')
            ->whereIn('id', $localityIds)
            ->orderBy('name')
            ->get()
            ->map(function (Locality $locality) use (
                $peopleByLocality,
                $householdsByLocality
            ): array {
                $peopleCount =
                    (int) ($peopleByLocality[$locality->id] ?? 0);

                $householdCount =
                    (int) ($householdsByLocality[$locality->id] ?? 0);

                $label = $locality->name;

                if (filled($locality->province?->name)) {
                    $label .= ' — ' . $locality->province->name;
                }

                return [
                    $label,
                    $peopleCount,
                    $householdCount,
                    $peopleCount + $householdCount,
                ];
            });

        $peopleWithoutLocality = Person::query()
            ->whereNull('locality_id')
            ->count();

        $householdsWithoutLocality = Household::query()
            ->whereNull('locality_id')
            ->count();

        if (
            $peopleWithoutLocality > 0
            || $householdsWithoutLocality > 0
        ) {
            $rows->push([
                'No Locality',
                $peopleWithoutLocality,
                $householdsWithoutLocality,
                $peopleWithoutLocality + $householdsWithoutLocality,
            ]);
        }

        return $this->csv('locality_summary', [
            'Locality',
            'People Count',
            'Household Count',
            'Total Records',
        ], $rows);
    }

    public function shepherding(): StreamedResponse
    {
        $this->authorizeExport();

        $rows = Person::query()
            ->with(['churchProfile.shepherd', 'churchProfile.introducedBy', 'household'])
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->lazy(100)
            ->map(fn (Person $person): array => [
                $person->display_name,
                $person->locality,
                $person->contact_number,
                $person->churchProfile?->status,
                $person->churchProfile?->category,
                $person->churchProfile?->service,
                $person->churchProfile?->shepherd?->display_name,
                $person->churchProfile?->contact_origin,
                $person->churchProfile?->first_contact_date,
                $person->churchProfile?->introducedBy?->display_name,
                $person->churchProfile?->contact_origin_details,
                $person->household?->household_name,
            ]);

        return $this->csv('shepherding_report', [
            'Name',
            'Locality',
            'Contact Number',
            'Status',
            'Category',
            'Shepherding Group',
            'Shepherd',
            'Contact Origin',
            'First Contact Date',
            'Introduced By',
            'Origin Details',
            'Household',
        ], $rows);
    }

    public function missingPeople(): StreamedResponse
    {
        $this->authorizeExport();

        $rows = Person::query()
            ->with(['churchProfile.shepherd', 'household'])
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('contact_number')
                    ->orWhere('contact_number', '')
                    ->orWhereNull('locality_id')
                    ->orWhereNull('household_id')
                    ->orWhereDoesntHave('churchProfile')
                    ->orWhereHas('churchProfile', function (Builder $query): void {
                        $query
                            ->whereNull('shepherd_id')
                            ->orWhereNull('service')
                            ->orWhere('service', '');
                    });
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->lazy(100)
            ->map(fn (Person $person): array => [
                $person->id,
                $person->display_name,
                $person->locality,
                $person->contact_number,
                $person->household?->household_name,
                $person->churchProfile?->status,
                $person->churchProfile?->service,
                $person->churchProfile?->shepherd?->display_name,
                implode(', ', $this->missingPersonFields($person)),
            ]);

        return $this->csv('missing_people_data', [
            'ID',
            'Name',
            'Locality',
            'Contact Number',
            'Household',
            'Status',
            'Shepherding Group',
            'Shepherd',
            'Missing Fields',
        ], $rows);
    }

    public function missingHouseholds(): StreamedResponse
    {
        $this->authorizeExport();

        $rows = Household::query()
            ->with(['head'])
            ->withCount('members')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('household_head_id')
                    ->orWhereNull('locality_id');
            })
            ->orderBy('household_name')
            ->lazy(100)
            ->map(fn (Household $household): array => [
                $household->id,
                $household->household_name,
                $household->head?->display_name,
                $household->locality,
                $household->members_count,
                implode(', ', $this->missingHouseholdFields($household)),
            ]);

        return $this->csv('missing_household_data', [
            'ID',
            'Household Name',
            'Household Head',
            'Locality',
            'Members Count',
            'Missing Fields',
        ], $rows);
    }


    public function peopleImportTemplate(): StreamedResponse
    {
        $this->authorizeImportTemplate();

        $rows = collect([
            array_fill(0, 35, ''),
        ]);

        return $this->csv('people_import_template', [
            'firstname',
            'middlename',
            'lastname',
            'suffix',
            'sex',
            'nickname',
            'birthdate',
            'birthplace',
            'locality',
            'contact_number',
            'email',
            'home_address',
            'permanent_address',
            'geocoordinates',
            'church_status',
            'baptism_date',
            'contact_origin',
            'first_contact_date',
            'shepherding_group',
            'shepherd_full_name',
            'introduced_by_full_name',
            'contact_origin_details',
            'household_name',
            'occupation',
            'school_workplace',
            'grade_level',
            'course_strand',
            'father_name',
            'mother_name',
            'guardian_name',
            'emergency_contact_full_name',
            'emergency_contact_relationship',
            'emergency_contact_number',
            'remarks',
            'import_notes',
        ], $rows);
    }

    private function authorizeExport(): void
    {
        abort_unless(auth()->user()?->canExportRecords(), 403);
    }

    private function authorizeImportTemplate(): void
    {
        abort_unless(auth()->user()?->canImportRecords(), 403);
    }

    private function missingPersonFields(Person $person): array
    {
        $missing = [];

        if (blank($person->contact_number)) {
            $missing[] = 'Contact Number';
        }

        if (blank($person->locality_id)) {
            $missing[] = 'Locality';
        }

        if (blank($person->household_id)) {
            $missing[] = 'Household';
        }

        if (! $person->churchProfile) {
            $missing[] = 'Church Profile';

            return $missing;
        }

        if (blank($person->churchProfile->shepherd_id)) {
            $missing[] = 'Shepherd';
        }

        if (blank($person->churchProfile->service)) {
            $missing[] = 'Shepherding Group';
        }

        return $missing;
    }

    private function missingHouseholdFields(Household $household): array
    {
        $missing = [];

        if (blank($household->household_head_id)) {
            $missing[] = 'Household Head';
        }

        if (blank($household->locality_id)) {
            $missing[] = 'Locality';
        }

        return $missing;
    }

    private function csv(string $name, array $headers, iterable $rows): StreamedResponse
    {
        $filename = $name . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($value): string => $this->csvValue($value), $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function csvValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }
}
