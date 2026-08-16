<?php

namespace App\Http\Controllers;

use App\Models\CampusContact;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampusContactImportController extends Controller
{
    private const HEADERS = [
        'firstname',
        'lastname',
        'sex',
        'locality',
        'school_campus',
        'course_strand',
        'grade_level',
        'contact_number',
        'email',
        'facebook_account',
        'notes',
    ];

    public function import(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canImportRecords(), 403);

        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'max:5120',
            ],

            'action' => [
                'required',
                'in:validate,import',
            ],
        ]);

        $parsed = $this->parseCsv(
            $request->file('csv_file')
        );

        $errors = $this->validateParsedCsv($parsed);

        if ($errors !== []) {
            return back()
                ->with(
                    'campus_contact_import_status',
                    'failed'
                )
                ->with(
                    'campus_contact_import_summary',
                    [
                        'rows_found' => count(
                            $parsed['rows'] ?? []
                        ),

                        'rows_imported' => 0,
                    ]
                )
                ->with(
                    'campus_contact_import_errors',
                    $errors
                );
        }

        if ($request->input('action') === 'validate') {
            return back()
                ->with(
                    'campus_contact_import_status',
                    'validated'
                )
                ->with(
                    'campus_contact_import_summary',
                    [
                        'rows_found' => count(
                            $parsed['rows']
                        ),

                        'rows_imported' => 0,
                    ]
                );
        }

        $imported = 0;

        foreach ($parsed['rows'] as $entry) {
            $row = $entry['data'];

            CampusContact::query()->create([
                'firstname' => $this->nullable(
                    $row['firstname'] ?? null
                ),

                'lastname' => $this->nullable(
                    $row['lastname'] ?? null
                ),

                'sex' => $this->nullable(
                    $row['sex'] ?? null
                ),

                'locality' => $this->nullable(
                    $row['locality'] ?? null
                ),

                'school_campus' => $this->nullable(
                    $row['school_campus'] ?? null
                ),

                'course_strand' => $this->nullable(
                    $row['course_strand'] ?? null
                ),

                'grade_level' => $this->nullable(
                    $row['grade_level'] ?? null
                ),

                'contact_number' => $this->nullable(
                    $row['contact_number'] ?? null
                ),

                'email' => $this->nullable(
                    $row['email'] ?? null
                ),

                'facebook_account' => $this->nullable(
                    $row['facebook_account'] ?? null
                ),

                'notes' => $this->nullable(
                    $row['notes'] ?? null
                ),
            ]);

            $imported++;
        }

        ActivityLogger::log(
            action: 'campus_contacts.imported',
            description: 'Imported Campus Contacts from CSV.',
            newValues: [
                'rows_found' => count($parsed['rows']),
                'rows_imported' => $imported,
            ],
        );

        return back()
            ->with(
                'campus_contact_import_status',
                'imported'
            )
            ->with(
                'campus_contact_import_summary',
                [
                    'rows_found' => count(
                        $parsed['rows']
                    ),

                    'rows_imported' => $imported,
                ]
            );
    }

    public function template(): StreamedResponse
    {
        abort_unless(auth()->user()?->canImportRecords(), 403);

        return response()->streamDownload(
            function (): void {
                $output = fopen('php://output', 'w');

                if ($output === false) {
                    return;
                }

                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, self::HEADERS);

                fputcsv($output, [
                    'Juan',
                    'Santos',
                    'Male',
                    'Lucban',
                    'Southern Luzon State University',
                    'BSECE',
                    '3rd Year',
                    '09171234567',
                    'juan@example.com',
                    'https://www.facebook.com/juan.santos',
                    'Met during campus visitation.',
                ]);

                fclose($output);
            },
            'campus-contacts-import-template.csv',
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    private function parseCsv(
        UploadedFile $file
    ): array {
        $handle = fopen(
            $file->getRealPath(),
            'r'
        );

        if ($handle === false) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => [
                    'Unable to open uploaded CSV file.',
                ],
            ];
        }

        $rawHeaders = fgetcsv($handle);

        if ($rawHeaders === false) {
            fclose($handle);

            return [
                'headers' => [],
                'rows' => [],
                'errors' => [
                    'CSV file is empty.',
                ],
            ];
        }

        $headers = array_map(
            fn ($header): string =>
                $this->normalizeHeader($header),
            $rawHeaders
        );

        $rows = [];
        $line = 1;

        while (
            ($data = fgetcsv($handle)) !== false
        ) {
            $line++;

            if ($this->isBlankCsvRow($data)) {
                continue;
            }

            $row = [];

            foreach (
                $headers as $index => $header
            ) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = trim(
                    (string) (
                        $data[$index] ?? ''
                    )
                );
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

    private function validateParsedCsv(
        array $parsed
    ): array {
        $errors = $parsed['errors'] ?? [];

        $headers = $parsed['headers'] ?? [];
        $rows = $parsed['rows'] ?? [];

        $recognizedHeaders = array_intersect(
            self::HEADERS,
            $headers
        );

        if ($recognizedHeaders === []) {
            $errors[] =
                'No recognized Campus Contact headers were found.';
        }

        if (count($rows) > 500) {
            $errors[] =
                'Maximum import limit is 500 rows per CSV file.';
        }

        if ($rows === []) {
            $errors[] =
                'No Campus Contact rows found in the CSV file.';
        }

        foreach ($rows as $entry) {
            $line = $entry['line'];
            $row = $entry['data'];

            $sex = trim(
                (string) ($row['sex'] ?? '')
            );

            if (
                $sex !== ''
                && ! in_array(
                    $sex,
                    ['Male', 'Female'],
                    true
                )
            ) {
                $errors[] =
                    "Line {$line}: sex must be Male or Female.";
            }

            $email = trim(
                (string) ($row['email'] ?? '')
            );

            if (
                $email !== ''
                && ! filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $errors[] =
                    "Line {$line}: email is invalid.";
            }

            $maximumLengths = [
                'firstname' => 100,
                'lastname' => 100,
                'locality' => 150,
                'school_campus' => 255,
                'course_strand' => 255,
                'grade_level' => 100,
                'contact_number' => 20,
                'email' => 255,
                'facebook_account' => 255,
            ];

            foreach (
                $maximumLengths as $field => $maximum
            ) {
                $value = (string) (
                    $row[$field] ?? ''
                );

                if (
                    mb_strlen($value) > $maximum
                ) {
                    $errors[] =
                        "Line {$line}: {$field} must not exceed {$maximum} characters.";
                }
            }
        }

        return $errors;
    }

    private function normalizeHeader(
        mixed $header
    ): string {
        return trim(
            strtolower(
                str_replace(
                    "\xEF\xBB\xBF",
                    '',
                    (string) $header
                )
            )
        );
    }

    private function isBlankCsvRow(
        array $row
    ): bool {
        foreach ($row as $value) {
            if (
                trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }

    private function nullable(
        mixed $value
    ): ?string {
        return filled($value)
            ? trim((string) $value)
            : null;
    }
}
