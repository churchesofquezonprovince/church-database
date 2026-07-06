<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Person;
use App\Support\ActivityLogger;
use App\Support\ChurchProfileOptions;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PeopleImportController extends Controller
{
    private array $personNameCache = [];

    public function import(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canImportRecords(), 403);

        $request->validate([
            'csv_file' => ['required', 'file', 'max:5120'],
            'action' => ['required', 'in:validate,import'],
        ]);

        $parsed = $this->parseCsv($request->file('csv_file'));
        $errors = $this->validateParsedCsv($parsed);

        if ($errors !== []) {
            return back()
                ->with('import_status', 'failed')
                ->with('import_summary', [
                    'rows_found' => count($parsed['rows']),
                    'rows_imported' => 0,
                ])
                ->with('import_errors', $errors);
        }

        if ($request->input('action') === 'validate') {
            return back()
                ->with('import_status', 'validated')
                ->with('import_summary', [
                    'rows_found' => count($parsed['rows']),
                    'rows_imported' => 0,
                ]);
        }

        $imported = DB::transaction(function () use ($parsed): int {
            $count = 0;

            foreach ($parsed['rows'] as $entry) {
                $this->importPersonRow($entry['data']);
                $count++;
            }

            return $count;
        });

        ActivityLogger::log(
            action: 'people.imported',
            description: 'Imported people from CSV.',
            newValues: [
                'rows_found' => count($parsed['rows']),
                'rows_imported' => $imported,
            ],
        );

        return back()
            ->with('import_status', 'imported')
            ->with('import_summary', [
                'rows_found' => count($parsed['rows']),
                'rows_imported' => $imported,
            ]);
    }

    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => ['Unable to open uploaded CSV file.'],
            ];
        }

        $rawHeaders = fgetcsv($handle);

        if ($rawHeaders === false) {
            fclose($handle);

            return [
                'headers' => [],
                'rows' => [],
                'errors' => ['CSV file is empty.'],
            ];
        }

        $headers = array_map(fn ($header): string => $this->normalizeHeader($header), $rawHeaders);

        $rows = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isBlankCsvRow($data)) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = trim((string) ($data[$index] ?? ''));
            }

            $rows[] = [
                'line' => $line,
                'data' => $row,
            ];
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'errors' => [],
        ];
    }

    private function validateParsedCsv(array $parsed): array
    {
        $errors = $parsed['errors'] ?? [];

        $headers = $parsed['headers'] ?? [];
        $rows = $parsed['rows'] ?? [];

        foreach (['firstname', 'lastname'] as $requiredHeader) {
            if (! in_array($requiredHeader, $headers, true)) {
                $errors[] = "Missing required header: {$requiredHeader}";
            }
        }

        if (count($rows) > 500) {
            $errors[] = 'Maximum import limit is 500 rows per CSV file.';
        }

        if ($rows === []) {
            $errors[] = 'No person rows found in the CSV file.';
        }

        $allowedSex = ['', 'Male', 'Female'];
        $allowedStatuses = array_keys(ChurchProfileOptions::statuses());
        $allowedGroups = array_keys(ChurchProfileOptions::shepherdingServices());

        $seenPersonIdentities = [];

        foreach ($rows as $entry) {
            $line = $entry['line'];
            $row = $entry['data'];

            if (blank($row['firstname'] ?? null)) {
                $errors[] = "Line {$line}: firstname is required.";
            }

            if (blank($row['lastname'] ?? null)) {
                $errors[] = "Line {$line}: lastname is required.";
            }

            if (! in_array($row['sex'] ?? '', $allowedSex, true)) {
                $errors[] = "Line {$line}: sex must be Male, Female, or blank.";
            }

            $birthdate = $row['birthdate'] ?? '';

            if (filled($birthdate) && ! $this->isValidDate($birthdate)) {
                $errors[] = "Line {$line}: birthdate must use YYYY-MM-DD format.";
            }

            $baptismDate = $row['baptism_date'] ?? '';

            if (filled($baptismDate) && ! $this->isValidPartialBaptismDate($baptismDate)) {
                $errors[] = "Line {$line}: baptism_date must use YYYY, YYYY-MM, or YYYY-MM-DD format.";
            }

            $status = $row['church_status'] ?? '';

            if (filled($status) && ! in_array($status, $allowedStatuses, true)) {
                $errors[] = "Line {$line}: church_status is invalid.";
            }

            $group = $row['shepherding_group'] ?? '';

            if (filled($group) && ! in_array($group, $allowedGroups, true)) {
                $errors[] = "Line {$line}: shepherding_group is invalid.";
            }

            if (filled($row['email'] ?? '') && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Line {$line}: email is invalid.";
            }

            if (filled($row['shepherd_full_name'] ?? '') && ! $this->findPersonByFullName($row['shepherd_full_name'])) {
                $errors[] = "Line {$line}: shepherd_full_name was not found in existing People records.";
            }

            if (filled($row['introduced_by_full_name'] ?? '') && ! $this->findPersonByFullName($row['introduced_by_full_name'])) {
                $errors[] = "Line {$line}: introduced_by_full_name was not found in existing People records.";
            }

            if (filled($row['emergency_contact_full_name'] ?? '') && ! $this->findPersonByFullName($row['emergency_contact_full_name'])) {
                $errors[] = "Line {$line}: emergency_contact_full_name was not found in existing People records.";
            }

            $identityKey = $this->personIdentityKey($row);

            if ($identityKey !== null) {
                if (isset($seenPersonIdentities[$identityKey])) {
                    $firstLine = $seenPersonIdentities[$identityKey];

                    $errors[] = "Line {$line}: duplicate inside CSV. Same firstname, lastname, and birthdate already appears on line {$firstLine}.";
                } else {
                    $seenPersonIdentities[$identityKey] = $line;
                }

                if ($this->existingPersonByIdentity($row)) {
                    $errors[] = "Line {$line}: possible duplicate person already exists in the database with same firstname, lastname, and birthdate.";
                }
            }
        }

        return $errors;
    }


    private function personIdentityKey(array $row): ?string
    {
        if (
            blank($row['firstname'] ?? null)
            || blank($row['lastname'] ?? null)
            || blank($row['birthdate'] ?? null)
        ) {
            return null;
        }

        return strtolower(trim((string) $row['firstname']))
            . '|'
            . strtolower(trim((string) $row['lastname']))
            . '|'
            . trim((string) $row['birthdate']);
    }

    private function existingPersonByIdentity(array $row): ?Person
    {
        if ($this->personIdentityKey($row) === null) {
            return null;
        }

        return Person::query()
            ->whereRaw('LOWER(firstname) = ?', [strtolower(trim((string) $row['firstname']))])
            ->whereRaw('LOWER(lastname) = ?', [strtolower(trim((string) $row['lastname']))])
            ->whereDate('birthdate', trim((string) $row['birthdate']))
            ->first();
    }

    private function importPersonRow(array $row): Person
    {
        $household = null;

        if (filled($row['household_name'] ?? null)) {
            $household = Household::query()
                ->whereRaw('LOWER(household_name) = ?', [strtolower(trim((string) $row['household_name']))])
                ->when(
                    filled($row['locality'] ?? null),
                    fn (Builder $query): Builder => $query->whereRaw('LOWER(locality) = ?', [strtolower(trim((string) $row['locality']))]),
                    fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query->whereNull('locality')->orWhere('locality', ''))
                )
                ->first();

            if (! $household) {
                $household = new Household();
                $household->household_name = $row['household_name'];
                $household->locality = $this->nullable($row['locality'] ?? null);
                $household->address = $this->nullable($row['home_address'] ?? null);
                $household->save();
            }
        }

        $emergencyContact = filled($row['emergency_contact_full_name'] ?? null)
            ? $this->findPersonByFullName($row['emergency_contact_full_name'])
            : null;

        $person = new Person();
        $person->firstname = $this->nullable($row['firstname'] ?? null);
        $person->middlename = $this->nullable($row['middlename'] ?? null);
        $person->lastname = $this->nullable($row['lastname'] ?? null);
        $person->suffix = $this->nullable($row['suffix'] ?? null);
        $person->sex = $this->nullable($row['sex'] ?? null);
        $person->nickname = $this->nullable($row['nickname'] ?? null);
        $person->birthdate = $this->nullable($row['birthdate'] ?? null);
        $person->birthplace = $this->nullable($row['birthplace'] ?? null);
        $person->locality = $this->nullable($row['locality'] ?? null);
        $person->contact_number = $this->nullable($row['contact_number'] ?? null);
        $person->email = $this->nullable($row['email'] ?? null);
        $person->home_address = $this->nullable($row['home_address'] ?? null);
        $person->permanent_address = $this->nullable($row['permanent_address'] ?? null);
        $person->geocoordinates = $this->nullable($row['geocoordinates'] ?? null);
        $person->household_id = $household?->id;
        $person->emergency_contact_id = $emergencyContact?->id;
        $person->emergency_contact_relationship = $this->nullable($row['emergency_contact_relationship'] ?? null);
        $person->emergency_contact_number = $this->nullable($row['emergency_contact_number'] ?? null);
        $person->save();

        $profile = $person->churchProfile()->firstOrNew([]);
        $baptismParts = $this->parsePartialBaptismDate($row['baptism_date'] ?? null);

        $profile->status = $this->nullable($row['church_status'] ?? null) ?: 'Unknown';
        $profile->baptism_year = $baptismParts['year'];
        $profile->baptism_month = $baptismParts['month'];
        $profile->baptism_day = $baptismParts['day'];
        $profile->service = $this->nullable($row['shepherding_group'] ?? null);
        $profile->shepherd_id = filled($row['shepherd_full_name'] ?? null)
            ? $this->findPersonByFullName($row['shepherd_full_name'])?->id
            : null;
        $profile->introduced_by_id = filled($row['introduced_by_full_name'] ?? null)
            ? $this->findPersonByFullName($row['introduced_by_full_name'])?->id
            : null;
        $profile->save();

        if (
            filled($row['occupation'] ?? null)
            || filled($row['school_workplace'] ?? null)
            || filled($row['grade_level'] ?? null)
            || filled($row['course_strand'] ?? null)
        ) {
            $education = $person->educationProfile()->firstOrNew([]);
            $education->occupation = $this->nullable($row['occupation'] ?? null);
            $education->school_workplace = $this->nullable($row['school_workplace'] ?? null);
            $education->grade_level = $this->nullable($row['grade_level'] ?? null);
            $education->course_strand = $this->nullable($row['course_strand'] ?? null);
            $education->save();
        }

        $this->createParentName($person, 'Father', $row['father_name'] ?? null);
        $this->createParentName($person, 'Mother', $row['mother_name'] ?? null);
        $this->createParentName($person, 'Guardian', $row['guardian_name'] ?? null);

        return $person;
    }

    private function createParentName(Person $person, string $relationship, ?string $name): void
    {
        if (blank($name)) {
            return;
        }

        $parent = $person->parentRelationships()->make();
        $parent->relationship = $relationship;
        $parent->parent_name = trim($name);
        $parent->save();
    }

    private function findPersonByFullName(?string $name): ?Person
    {
        if (blank($name)) {
            return null;
        }

        $key = mb_strtolower(trim($name));

        if (array_key_exists($key, $this->personNameCache)) {
            return $this->personNameCache[$key];
        }

        $person = Person::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->first(function (Person $person) use ($name): bool {
                $target = trim($name);

                return strcasecmp($person->display_name, $target) === 0
                    || strcasecmp(trim($person->firstname . ' ' . $person->lastname), $target) === 0;
            });

        $this->personNameCache[$key] = $person;

        return $person;
    }

    private function normalizeHeader(mixed $header): string
    {
        return trim(strtolower(str_replace("\xEF\xBB\xBF", '', (string) $header)));
    }

    private function isBlankCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if (filled(trim((string) $value))) {
                return false;
            }
        }

        return true;
    }


    private function isValidPartialBaptismDate(string $value): bool
    {
        return $this->parsePartialBaptismDate($value) !== [
            'year' => null,
            'month' => null,
            'day' => null,
        ];
    }

    private function parsePartialBaptismDate(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [
                'year' => null,
                'month' => null,
                'day' => null,
            ];
        }

        if (preg_match('/^\d{4}$/', $value)) {
            $year = (int) $value;

            return $this->validYear($year)
                ? ['year' => $year, 'month' => null, 'day' => null]
                : ['year' => null, 'month' => null, 'day' => null];
        }

        if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];

            if (! $this->validYear($year) || $month < 1 || $month > 12) {
                return [
                    'year' => null,
                    'month' => null,
                    'day' => null,
                ];
            }

            return [
                'year' => $year,
                'month' => $month,
                'day' => null,
            ];
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (! $this->validYear($year) || ! checkdate($month, $day, $year)) {
                return [
                    'year' => null,
                    'month' => null,
                    'day' => null,
                ];
            }

            return [
                'year' => $year,
                'month' => $month,
                'day' => $day,
            ];
        }

        return [
            'year' => null,
            'month' => null,
            'day' => null,
        ];
    }

    private function validYear(int $year): bool
    {
        return $year >= 1800 && $year <= ((int) date('Y') + 1);
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function store(\Illuminate\Http\Request $request)
    {
        return match ((string) $request->input('action', 'validate')) {
            'validate' => $this->import($request),
            'import' => $this->import($request),
            default => $this->import($request),
        };
    }


}
