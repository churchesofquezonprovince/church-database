<?php

namespace App\Services;

use App\Models\ChildrenWorkLesson;
use Carbon\CarbonImmutable;
use Exception;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Str;

class ChildrenWorkGoogleSheetsService
{
    public function syncFromGoogleSheet(): array
    {
        if (! config('children_work.google_sheets.enabled')) {
            throw new Exception('Children Work Google Sheets sync is disabled.');
        }

        $spreadsheetId = (string) config('children_work.google_sheets.spreadsheet_id');

        if ($spreadsheetId === '') {
            throw new Exception('Missing CHILDREN_WORK_GOOGLE_SHEETS_ID.');
        }

        $service = $this->sheetsService();
        $sheetTitle = $this->sheetTitle($service, $spreadsheetId);
        $range = $this->quoteSheetTitle($sheetTitle) . '!A:G';

        /*
         * FORMULA values allow us to read =HYPERLINK("url", "label").
         * UNFORMATTED values allow us to read real Google date serials.
         */
        $formulaValues = $service->spreadsheets_values
            ->get($spreadsheetId, $range, [
                'valueRenderOption' => 'FORMULA',
                'dateTimeRenderOption' => 'FORMATTED_STRING',
            ])
            ->getValues() ?? [];

        $rawValues = $service->spreadsheets_values
            ->get($spreadsheetId, $range, [
                'valueRenderOption' => 'UNFORMATTED_VALUE',
                'dateTimeRenderOption' => 'SERIAL_NUMBER',
            ])
            ->getValues() ?? [];

        $values = $formulaValues ?: $rawValues;

        $headerRowNumber = max((int) config('children_work.google_sheets.header_row', 1), 1);
        $headerIndex = $headerRowNumber - 1;

        if (! isset($values[$headerIndex])) {
            throw new Exception("Header row {$headerRowNumber} was not found in Google Sheet.");
        }

        $headers = $this->normalizeHeaders($values[$headerIndex]);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($values as $index => $row) {
            $rowNumber = $index + 1;

            if ($rowNumber <= $headerRowNumber) {
                continue;
            }

            $formulaRow = $formulaValues[$index] ?? [];
            $rawRow = $rawValues[$index] ?? [];

            $mapped = $this->mapRow($headers, $formulaRow);
            $rawMapped = $this->mapRow($headers, $rawRow);

            if ($this->rowIsEmpty($mapped) && $this->rowIsEmpty($rawMapped)) {
                $skipped++;
                continue;
            }

            $payload = $this->payloadFromMappedRow($mapped, $rawMapped);

            if (! filled($payload['scheduled_on']) && ! filled($payload['lesson_title'])) {
                $skipped++;
                continue;
            }

            $hash = $this->rowHash(array_merge($mapped, [
                '__raw_date' => $this->firstValue($rawMapped, ['date', 'schedule', 'scheduled_on', 'meeting_date']),
            ]));

            $lesson = ChildrenWorkLesson::query()
                ->where('google_sheet_row_number', $rowNumber)
                ->first();

            if (! $lesson && filled($payload['scheduled_on']) && filled($payload['lesson_title'])) {
                $lesson = ChildrenWorkLesson::query()
                    ->whereDate('scheduled_on', $payload['scheduled_on'])
                    ->where('lesson_title', $payload['lesson_title'])
                    ->first();
            }

            if ($lesson && $lesson->google_sheet_row_hash === $hash) {
                $skipped++;
                continue;
            }

            $payload['google_sheet_row_number'] = $rowNumber;
            $payload['google_sheet_row_hash'] = $hash;
            $payload['google_sheet_synced_at'] = now();
            $payload['source'] = 'google_sheet';
            $payload['sync_status'] = 'synced';
            $payload['sync_error'] = null;

            if ($lesson) {
                $lesson->update($payload);
                $updated++;
            } else {
                ChildrenWorkLesson::query()->create($payload);
                $created++;
            }
        }

        return [
            'sheet_title' => $sheetTitle,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total_rows' => max(count($values) - $headerRowNumber, 0),
        ];
    }

    public function pushToGoogleSheet(): array
    {
        if (! config('children_work.google_sheets.enabled')) {
            throw new Exception('Children Work Google Sheets sync is disabled.');
        }

        $spreadsheetId = (string) config('children_work.google_sheets.spreadsheet_id');

        if ($spreadsheetId === '') {
            throw new Exception('Missing CHILDREN_WORK_GOOGLE_SHEETS_ID.');
        }

        $service = $this->sheetsService();
        $sheetTitle = $this->sheetTitle($service, $spreadsheetId);

        $lessons = ChildrenWorkLesson::query()
            ->where(function ($query): void {
                $query->whereNull('google_sheet_row_number')
                    ->orWhereNull('sync_status')
                    ->orWhere('sync_status', '!=', 'synced');
            })
            ->orderByRaw('CASE WHEN scheduled_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_on')
            ->orderBy('id')
            ->get();

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($lessons as $lesson) {
            try {
                $values = $this->sheetRowFromLesson($lesson);
                $rowHash = $this->rowHash($this->mappedRowFromSheetValues($values));

                if ($lesson->google_sheet_row_number) {
                    $range = $this->quoteSheetTitle($sheetTitle) . '!A' . $lesson->google_sheet_row_number . ':G' . $lesson->google_sheet_row_number;

                    $service->spreadsheets_values->update(
                        $spreadsheetId,
                        $range,
                        new ValueRange([
                            'values' => [$values],
                        ]),
                        [
                            'valueInputOption' => 'USER_ENTERED',
                        ]
                    );

                    $lesson->forceFill([
                        'google_sheet_row_hash' => $rowHash,
                        'google_sheet_synced_at' => now(),
                        'sync_status' => 'synced',
                        'sync_error' => null,
                    ])->save();

                    $updated++;

                    continue;
                }

                $response = $service->spreadsheets_values->append(
                    $spreadsheetId,
                    $this->quoteSheetTitle($sheetTitle) . '!A:G',
                    new ValueRange([
                        'values' => [$values],
                    ]),
                    [
                        'valueInputOption' => 'USER_ENTERED',
                        'insertDataOption' => 'INSERT_ROWS',
                    ]
                );

                $updatedRange = $response->getUpdates()?->getUpdatedRange();
                $rowNumber = $this->extractRowNumberFromUpdatedRange($updatedRange);

                $lesson->forceFill([
                    'google_sheet_row_number' => $rowNumber,
                    'google_sheet_row_hash' => $rowHash,
                    'google_sheet_synced_at' => now(),
                    'sync_status' => 'synced',
                    'sync_error' => null,
                ])->save();

                $created++;
            } catch (\Throwable $exception) {
                $lesson->forceFill([
                    'sync_status' => 'failed',
                    'sync_error' => $exception->getMessage(),
                ])->save();

                $failed++;
            }
        }

        return [
            'sheet_title' => $sheetTitle,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total_rows' => $lessons->count(),
        ];
    }

    private function sheetsService(): Sheets
    {
        $credentialsPath = base_path((string) config('children_work.google_sheets.credentials_path'));

        if (! file_exists($credentialsPath)) {
            throw new Exception("Google credentials file not found: {$credentialsPath}");
        }

        $client = new Client();
        $client->setApplicationName('COQP Children Work Lessons');
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([
            Sheets::SPREADSHEETS,
        ]);

        return new Sheets($client);
    }

    private function sheetTitle(Sheets $service, string $spreadsheetId): string
    {
        $configured = config('children_work.google_sheets.sheet_name');

        if (filled($configured)) {
            return (string) $configured;
        }

        $spreadsheet = $service->spreadsheets->get($spreadsheetId, [
            'fields' => 'sheets.properties.title',
        ]);

        $sheets = $spreadsheet->getSheets();

        if ($sheets === []) {
            throw new Exception('No sheets found in spreadsheet.');
        }

        return (string) $sheets[0]->getProperties()->getTitle();
    }

    private function quoteSheetTitle(string $title): string
    {
        return "'" . str_replace("'", "''", $title) . "'";
    }

    private function normalizeHeaders(array $headerRow): array
    {
        $headers = [];

        foreach ($headerRow as $index => $header) {
            $headers[$index] = Str::of((string) $header)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
        }

        return $headers;
    }

    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $mapped[$header] = trim((string) ($row[$index] ?? ''));
        }

        return $mapped;
    }

    private function payloadFromMappedRow(array $mapped, array $rawMapped): array
    {
        $lessonCell = $this->firstHyperlinkedValue($mapped, ['lesson', 'lessons', 'lesson_title', 'topic', 'title']);
        $hymnCell = $this->firstHyperlinkedValue($mapped, ['suggested_hymn', 'hymn', 'song']);
        $slidesCell = $this->firstHyperlinkedValue($mapped, ['presentation_slides', 'slides', 'presentation']);

        $lessonTitle = $lessonCell['label'];
        $hymnTitle = $hymnCell['label'];
        $slidesTitle = $slidesCell['label'];

        return [
            'scheduled_on' => $this->parseDate(
                $this->firstValue($rawMapped, ['date', 'schedule', 'scheduled_on', 'meeting_date'])
                ?: $this->firstValue($mapped, ['date', 'schedule', 'scheduled_on', 'meeting_date'])
            ),
            'lesson_code' => $this->parseLessonCode($lessonTitle),
            'lesson_title' => $lessonTitle,
            'lesson_url' => $lessonCell['url'] ?: $this->firstValue($mapped, ['lesson_link', 'lesson_url', 'link']),
            'suggested_hymn' => $hymnTitle,
            'suggested_hymn_url' => $hymnCell['url'] ?: $this->firstValue($mapped, ['suggested_hymn_link', 'hymn_link', 'hymn_url']),
            'memory_verse' => $this->firstValue($mapped, ['memory_verse', 'verse']),
            'story' => $this->firstValue($mapped, ['story', 'bible_story']),
            'presentation_slides' => $slidesTitle,
            'presentation_slides_url' => $slidesCell['url'] ?: $this->firstValue($mapped, ['presentation_slides_link', 'slides_link', 'slides_url']),
            'activity' => $this->firstValue($mapped, ['activity', 'activities']),
            'assigned_to' => $this->firstValue($mapped, ['assigned_to', 'c_o', 'co', 'person_in_charge', 'in_charge']),
            'notes' => $this->firstValue($mapped, ['notes', 'remarks']),
            'status' => 'scheduled',
        ];
    }

    private function firstHyperlinkedValue(array $mapped, array $keys): array
    {
        foreach ($keys as $key) {
            if (! isset($mapped[$key]) || trim((string) $mapped[$key]) === '') {
                continue;
            }

            return $this->parseHyperlinkCell((string) $mapped[$key]);
        }

        return [
            'label' => null,
            'url' => null,
        ];
    }

    private function parseHyperlinkCell(string $value): array
    {
        $value = trim($value);

        if (preg_match('/^=HYPERLINK\(\s*"((?:[^"]|"")*)"\s*[,;]\s*"((?:[^"]|"")*)"\s*\)$/iu', $value, $matches)) {
            return [
                'url' => str_replace('""', '"', $matches[1]),
                'label' => str_replace('""', '"', $matches[2]),
            ];
        }

        return [
            'label' => $value,
            'url' => $this->looksLikeUrl($value) ? $value : null,
        ];
    }

    private function firstValue(array $mapped, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($mapped[$key]) && trim((string) $mapped[$key]) !== '') {
                return trim((string) $mapped[$key]);
            }
        }

        return null;
    }

    private function rowIsEmpty(array $mapped): bool
    {
        foreach ($mapped as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseDate(null|string|int|float $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);

        if (is_numeric($value)) {
            return CarbonImmutable::createFromDate(1899, 12, 30)
                ->addDays((int) floor((float) $value))
                ->format('Y-m-d');
        }

        foreach ([
            'Y-m-d',
            'm/d/Y',
            'n/j/Y',
            'm-d-Y',
            'n-j-Y',
            'M d, Y',
            'F d, Y',
            'F j, Y',
            'M j, Y',
        ] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);

                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return CarbonImmutable::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseLessonCode(?string $lesson): ?string
    {
        if (! filled($lesson)) {
            return null;
        }

        if (preg_match('/lesson\s*[\-#:]?\s*(\d+)/i', (string) $lesson, $matches)) {
            return 'Lesson ' . $matches[1];
        }

        return null;
    }

    private function sheetRowFromLesson(ChildrenWorkLesson $lesson): array
    {
        return [
            $lesson->scheduled_on?->format('Y-m-d') ?? '',
            $this->hyperlinkFormula($lesson->lesson_url, $lesson->lesson_title),
            $this->hyperlinkFormula($lesson->suggested_hymn_url, $lesson->suggested_hymn),
            $lesson->memory_verse ?? '',
            $lesson->story ?? '',
            $this->hyperlinkFormula($lesson->presentation_slides_url, $lesson->presentation_slides),
            $lesson->activity ?? '',
        ];
    }

    private function mappedRowFromSheetValues(array $values): array
    {
        return [
            'date' => trim((string) ($values[0] ?? '')),
            'lesson' => trim((string) ($values[1] ?? '')),
            'suggested_hymn' => trim((string) ($values[2] ?? '')),
            'memory_verse' => trim((string) ($values[3] ?? '')),
            'story' => trim((string) ($values[4] ?? '')),
            'presentation_slides' => trim((string) ($values[5] ?? '')),
            'activity' => trim((string) ($values[6] ?? '')),
        ];
    }

    private function hyperlinkFormula(?string $url, ?string $label): string
    {
        $url = trim((string) $url);
        $label = trim((string) $label);

        if ($url === '') {
            return $label;
        }

        if ($label === '') {
            $label = $url;
        }

        return '=HYPERLINK("' . $this->escapeFormulaString($url) . '","' . $this->escapeFormulaString($label) . '")';
    }

    private function escapeFormulaString(string $value): string
    {
        return str_replace('"', '""', $value);
    }

    private function looksLikeUrl(string $value): bool
    {
        return preg_match('/^https?:\/\//i', trim($value)) === 1;
    }

    private function extractRowNumberFromUpdatedRange(?string $range): ?int
    {
        if (! filled($range)) {
            return null;
        }

        if (preg_match('/![A-Z]+(\d+):[A-Z]+(\d+)$/', (string) $range, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/![A-Z]+(\d+)$/', (string) $range, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function rowHash(array $mapped): string
    {
        ksort($mapped);

        return hash('sha256', json_encode($mapped, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
