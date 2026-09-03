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

private function setRangeBackground(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $startRowIndex,
    int $endRowIndex,
    int $startColumnIndex,
    int $endColumnIndex,
    float $red,
    float $green,
    float $blue
): void {
    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [
                new \Google\Service\Sheets\Request([
                    'repeatCell' =>
                        new \Google\Service\Sheets\RepeatCellRequest([
                            'range' =>
                                new \Google\Service\Sheets\GridRange([
                                    'sheetId' => $sheetId,
                                    'startRowIndex' => $startRowIndex,
                                    'endRowIndex' => $endRowIndex,
                                    'startColumnIndex' => $startColumnIndex,
                                    'endColumnIndex' => $endColumnIndex,
                                ]),
                            'cell' =>
                                new \Google\Service\Sheets\CellData([
                                    'userEnteredFormat' =>
                                        new \Google\Service\Sheets\CellFormat([
                                            'backgroundColor' =>
                                                new \Google\Service\Sheets\Color([
                                                    'red' => $red,
                                                    'green' => $green,
                                                    'blue' => $blue,
                                                ]),
                                        ]),
                                ]),
                            'fields' =>
                                'userEnteredFormat.backgroundColor',
                        ]),
                ]),
            ],
        ])
    );
}

private function sheetBorder(
    string $style
): \Google\Service\Sheets\Border {
    return new \Google\Service\Sheets\Border([
        'style' => $style,
    ]);
}

private function applySpecialActivityBorders(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $rowNumber
): void {
    $rowIndex = $rowNumber - 1;

    $medium = $this->sheetBorder(
        'SOLID_MEDIUM'
    );

    /*
     * Outer border around A:G.
     */
    $requests = [
        new \Google\Service\Sheets\Request([
            'updateBorders' =>
                new \Google\Service\Sheets\UpdateBordersRequest([
                    'range' =>
                        new \Google\Service\Sheets\GridRange([
                            'sheetId' => $sheetId,
                            'startRowIndex' => $rowIndex,
                            'endRowIndex' => $rowIndex + 1,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 7,
                        ]),
                    'top' => $medium,
                    'bottom' => $medium,
                    'left' => $medium,
                    'right' => $medium,
                ]),
        ]),

        /*
         * A is the Date.
         * B:G is one merged Special Activity cell.
         *
         * Give the boundary between A and B:G
         * the same medium border.
         */
        new \Google\Service\Sheets\Request([
            'updateBorders' =>
                new \Google\Service\Sheets\UpdateBordersRequest([
                    'range' =>
                        new \Google\Service\Sheets\GridRange([
                            'sheetId' => $sheetId,
                            'startRowIndex' => $rowIndex,
                            'endRowIndex' => $rowIndex + 1,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 1,
                        ]),
                    'right' => $medium,
                ]),
        ]),
    ];

    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ])
    );
}

private function applyNormalLessonBorders(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $firstRow
): void {
    $startRowIndex = $firstRow - 1;
    $endRowIndex = $startRowIndex + 2;

    $medium = $this->sheetBorder(
        'SOLID_MEDIUM'
    );

    $thin = $this->sheetBorder(
        'SOLID'
    );

    $requests = [];

    /*
     * OUTSIDE border around the complete
     * two-row A:G schedule.
     *
     * Also put thin vertical borders between columns.
     */
    $requests[] =
        new \Google\Service\Sheets\Request([
            'updateBorders' =>
                new \Google\Service\Sheets\UpdateBordersRequest([
                    'range' =>
                        new \Google\Service\Sheets\GridRange([
                            'sheetId' => $sheetId,
                            'startRowIndex' =>
                                $startRowIndex,
                            'endRowIndex' =>
                                $endRowIndex,
                            'startColumnIndex' => 0,
                            'endColumnIndex' => 7,
                        ]),
                    'top' => $medium,
                    'bottom' => $medium,
                    'left' => $medium,
                    'right' => $medium,
                    'innerVertical' => $thin,
                ]),
        ]);

    /*
     * Horizontal inside border between row 1 and row 2:
     *
     * B:C only.
     *
     * Do NOT put it through A because Date is vertically
     * merged.
     */
    $requests[] =
        new \Google\Service\Sheets\Request([
            'updateBorders' =>
                new \Google\Service\Sheets\UpdateBordersRequest([
                    'range' =>
                        new \Google\Service\Sheets\GridRange([
                            'sheetId' => $sheetId,
                            'startRowIndex' =>
                                $startRowIndex,
                            'endRowIndex' =>
                                $endRowIndex,
                            'startColumnIndex' => 1,
                            'endColumnIndex' => 3,
                        ]),
                    'innerHorizontal' => $thin,
                ]),
        ]);

    /*
     * Horizontal inside border for E:G.
     *
     * D is omitted because Memory Verse is vertically
     * merged.
     */
    $requests[] =
        new \Google\Service\Sheets\Request([
            'updateBorders' =>
                new \Google\Service\Sheets\UpdateBordersRequest([
                    'range' =>
                        new \Google\Service\Sheets\GridRange([
                            'sheetId' => $sheetId,
                            'startRowIndex' =>
                                $startRowIndex,
                            'endRowIndex' =>
                                $endRowIndex,
                            'startColumnIndex' => 4,
                            'endColumnIndex' => 7,
                        ]),
                    'innerHorizontal' => $thin,
                ]),
        ]);

    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ])
    );
}

/* Phase Book HAHAHA.  */
private function gridMetadata(
    Sheets $service,
    string $spreadsheetId,
    string $sheetTitle,
    array $headers,
    int $lastRow
): array {
    if ($lastRow < 1) {
        return [];
    }

    $range = $this->quoteSheetTitle($sheetTitle)
        . '!A1:G'
        . $lastRow;

    $spreadsheet = $service->spreadsheets->get(
        $spreadsheetId,
        [
            'ranges' => [$range],
            'includeGridData' => true,
        ]
    );

    $sheet = $spreadsheet->getSheets()[0] ?? null;

    if (! $sheet) {
        return [];
    }

    $result = [];

    foreach ($sheet->getData() ?? [] as $data) {
        foreach ($data->getRowData() ?? [] as $rowIndex => $row) {
            $mapped = [];

            foreach ($row->getValues() ?? [] as $columnIndex => $cell) {
                $header = $headers[$columnIndex] ?? null;

                if (! filled($header)) {
                    continue;
                }

$mapped[$header] = [
    'label' => trim(
        (string) $cell->getFormattedValue()
    ),
    'url' => $this->urlFromCellData($cell),
    'is_smart_chip' => $this->cellHasSmartChip($cell),
];
            }

            $result[$rowIndex] = $mapped;
        }
    }

    return $result;
}

private function cellHasSmartChip($cell): bool
{
    foreach ($cell->getChipRuns() ?? [] as $chipRun) {
        if ($chipRun->getChip()) {
            return true;
        }
    }

    return false;
}

private function smartChipLinkFields(
    array $firstMetadata,
    array $secondMetadata
): array {
    /*
     * Google Sheet column/header -> website URL field.
     *
     * We intentionally lock ONLY the URL field.
     * The human-readable title remains editable.
     */
    $map = [
        'lesson' => 'lesson_url',
        'suggested_hymn' => 'suggested_hymn_url',
        'story' => 'story_url',
        'presentation_slides' => 'presentation_slides_url',
        'activity' => 'activity_url',
    ];

    $locked = [];

    foreach ([$firstMetadata, $secondMetadata] as $metadata) {
        foreach ($map as $sheetField => $modelField) {
            if (
                ($metadata[$sheetField]['is_smart_chip'] ?? false)
                === true
            ) {
                $locked[$modelField] = true;
            }
        }
    }

    return array_keys($locked);
}

private function urlFromCellData(
    \Google\Service\Sheets\CellData $cell
): ?string {
    /*
     * Ordinary whole-cell hyperlink.
     */
    $hyperlink = trim(
        (string) $cell->getHyperlink()
    );

    if ($hyperlink !== '') {
        return $hyperlink;
    }

    /*
     * Smart chips / rich-link chips.
     */
    foreach ($cell->getChipRuns() ?? [] as $chipRun) {
        $chip = $chipRun->getChip();

        if (! $chip) {
            continue;
        }

        $richLink = $chip->getRichLinkProperties();

        if (! $richLink) {
            continue;
        }

        $uri = trim(
            (string) $richLink->getUri()
        );

        if ($uri !== '') {
            return $uri;
        }
    }

    /*
     * Rich-text hyperlinks.
     */
    foreach ($cell->getTextFormatRuns() ?? [] as $run) {
        $link = $run->getFormat()?->getLink();

        if (! $link) {
            continue;
        }

        $uri = trim(
            (string) $link->getUri()
        );

        if ($uri !== '') {
            return $uri;
        }
    }

    /*
     * Plain URL fallback.
     */
    $formatted = trim(
        (string) $cell->getFormattedValue()
    );

    if ($this->looksLikeUrl($formatted)) {
        return $formatted;
    }

    return null;
}

private function mergeTwoMetadataRows(
    array $first,
    array $second
): array {
    $merged = $first;

    /*
     * A continuation row contains the actual resource links.
     * Therefore any URL from row 2 should override the URL
     * belonging to the corresponding row-1 display cell.
     */
    foreach ($second as $key => $metadata) {
        $url = trim(
            (string) ($metadata['url'] ?? '')
        );

        if ($url === '') {
            continue;
        }

        $merged[$key]['url'] = $url;

        if (
            filled($metadata['label'] ?? null)
        ) {
            $merged[$key]['resource_label'] =
                $metadata['label'];
        }
    }

    return $merged;
}

private function metadataUrl(
    array $metadata,
    array $keys
): ?string {
    foreach ($keys as $key) {
        $url = trim(
            (string) ($metadata[$key]['url'] ?? '')
        );

        if ($url !== '') {
            return $url;
        }
    }

    return null;
}

    /**
     * Phase 22D2
     *
     * Google Sheet lesson format:
     *
     *   Row N     = lesson information
     *   Row N + 1 = continuation/resource information
     *
     * Both physical rows represent ONE ChildrenWorkLesson record.
     */
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
         * FORMULA values allow us to preserve formulas such as:
         *
         * =HYPERLINK("url","label")
         *
         * UNFORMATTED values allow us to read actual Google date serials.
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

        $headerRowNumber = max(
            (int) config('children_work.google_sheets.header_row', 1),
            1
        );

        $headerIndex = $headerRowNumber - 1;

        if (! isset($values[$headerIndex])) {
            throw new Exception(
                "Header row {$headerRowNumber} was not found in Google Sheet."
            );
        }

        $headers = $this->normalizeHeaders($values[$headerIndex]);

        $gridMetadata = $this->gridMetadata(
    $service,
    $spreadsheetId,
    $sheetTitle,
    $headers,
    count($values)
);


        $created = 0;
        $updated = 0;
        $skipped = 0;

        /*
         * Phase 22D2:
         *
         * Start at the first data row and consume TWO physical rows
         * for every lesson.
         */
        $dataIndex = $headerIndex + 1;

        while ($dataIndex < count($values)) {
            $firstRowNumber = $dataIndex + 1;

            $formulaFirstRow = $formulaValues[$dataIndex] ?? [];
            $rawFirstRow = $rawValues[$dataIndex] ?? [];

            /*
             * The second physical row belongs to the same lesson.
             */
            // $secondIndex = $dataIndex + 1;

            // $formulaSecondRow = $formulaValues[$secondIndex] ?? [];
            // $rawSecondRow = $rawValues[$secondIndex] ?? [];

            // $firstMapped = $this->mapRow($headers, $formulaFirstRow);
            // $firstRawMapped = $this->mapRow($headers, $rawFirstRow);

            // $secondMapped = $this->mapRow($headers, $formulaSecondRow);
            // $secondRawMapped = $this->mapRow($headers, $rawSecondRow);

$firstMapped = $this->mapRow(
    $headers,
    $formulaFirstRow
);

$firstRawMapped = $this->mapRow(
    $headers,
    $rawFirstRow
);

/*
 * The next physical row is OPTIONAL.
 *
 * We only consume it as part of this lesson when it
 * actually looks like a continuation row.
 */
$secondIndex = $dataIndex + 1;

$formulaSecondRow = [];
$rawSecondRow = [];

$secondMapped = [];
$secondRawMapped = [];

$hasContinuationRow = false;

if ($secondIndex < count($values)) {
    $formulaSecondRow = $formulaValues[$secondIndex] ?? [];
    $rawSecondRow = $rawValues[$secondIndex] ?? [];

    $hasContinuationRow = $this->isContinuationRow(
        $formulaSecondRow,
        $rawSecondRow,
        $headers
    );

    if ($hasContinuationRow) {
        $secondMapped = $this->mapRow(
            $headers,
            $formulaSecondRow
        );

        $secondRawMapped = $this->mapRow(
            $headers,
            $rawSecondRow
        );
    }
}

$firstMetadata =
    $gridMetadata[$dataIndex] ?? [];

$secondMetadata = $hasContinuationRow
    ? ($gridMetadata[$secondIndex] ?? [])
    : [];

$metadata = $this->mergeTwoMetadataRows(
    $firstMetadata,
    $secondMetadata
);

$smartChipFields = $this->smartChipLinkFields(
    $firstMetadata,
    $secondMetadata
);

            /*
             * If there is a completely empty row, do not manufacture a lesson.
             */
            if (
                $this->rowIsEmpty($firstMapped)
                && $this->rowIsEmpty($firstRawMapped)
                && $this->rowIsEmpty($secondMapped)
                && $this->rowIsEmpty($secondRawMapped)
            ) {
                // $skipped++;
                // $dataIndex += 2;
                // continue;
$skipped++;
$dataIndex += $hasContinuationRow ? 2 : 1;
continue;
            }

            /*
             * Combine both physical rows into one logical lesson.
             */
            $mapped = $this->mergeTwoLessonRows(
                $firstMapped,
                $secondMapped
            );

            $rawMapped = $this->mergeTwoLessonRows(
                $firstRawMapped,
                $secondRawMapped
            );

            if ($this->rowIsEmpty($mapped) && $this->rowIsEmpty($rawMapped)) {
                $skipped++;
                $dataIndex += $hasContinuationRow ? 2 : 1;
                continue;
            }

$payload = $this->payloadFromMappedRow(
    $mapped,
    $rawMapped,
    $metadata
);

$payload['google_sheet_smart_chip_fields']
    = $smartChipFields;

            /*
             * A lesson must have at least a date or title.
             */
            if (
                ! filled($payload['scheduled_on'])
                && ! filled($payload['lesson_title'])
            ) {
                $skipped++;
                $dataIndex += $hasContinuationRow ? 2 : 1;
                continue;
            }

            /*
             * Hash BOTH physical rows together.
             *
             * This means changing either row causes the lesson to sync.
             */
            $hash = $this->rowHash([
                'row_1' => $firstMapped,
                'row_2' => $secondMapped,
                '__raw_row_1' => $firstRawMapped,
                '__raw_row_2' => $secondRawMapped,
                '__metadata' => $metadata,
            ]);

            /*
             * The first physical row is the anchor row for the lesson.
             */
            $lesson = ChildrenWorkLesson::query()
                ->where('google_sheet_row_number', $firstRowNumber)
                ->first();

            /*
             * Fallback matching for existing records.
             */
            if (
                ! $lesson
                && filled($payload['scheduled_on'])
                && filled($payload['lesson_title'])
            ) {
                $lesson = ChildrenWorkLesson::query()
                    ->whereDate(
                        'scheduled_on',
                        $payload['scheduled_on']
                    )
                    ->where(
                        'lesson_title',
                        $payload['lesson_title']
                    )
                    ->first();
            }

            if (
                $lesson
                && $lesson->google_sheet_row_hash === $hash
            ) {
                $skipped++;
                $dataIndex += $hasContinuationRow ? 2 : 1;
                continue;
            }

            $payload['google_sheet_row_number'] = $firstRowNumber;
            $payload['google_sheet_row_hash'] = $hash;
            $payload['google_sheet_synced_at'] = now();
            $payload['source'] = 'google_sheet';
            $payload['sync_status'] = 'synced';
            $payload['sync_error'] = null;

if ($lesson) {
    /*
     * Native Smart Chip URLs are Google-managed.
     *
     * Their corresponding titles are website-editable,
     * so preserve the website title during later imports.
     */
    $smartChipTitleMap = [
        'lesson_url' => 'lesson_title',
        'suggested_hymn_url' => 'suggested_hymn',
        'story_url' => 'story',
        'presentation_slides_url' =>
            'presentation_slides',
        'activity_url' => 'activity',
    ];

    foreach (
        $smartChipFields
        as $smartChipUrlField
    ) {
        $titleField =
            $smartChipTitleMap[$smartChipUrlField]
            ?? null;

        if ($titleField) {
            $payload[$titleField] =
                $lesson->{$titleField};
        }
    }
}
            
            if ($lesson) {
                $lesson->update($payload);
                $updated++;
            } else {
                ChildrenWorkLesson::query()->create($payload);
                $created++;
            }

/*
 * Consume the continuation row only when one actually
 * belongs to this lesson.
 */
$dataIndex += $hasContinuationRow ? 2 : 1;
        }

        return [
            'sheet_title' => $sheetTitle,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total_rows' => max(count($values) - $headerRowNumber, 0),
        ];
    }

public function insertLessonChronologically(
    ChildrenWorkLesson $lesson
): void {
    if (! config('children_work.google_sheets.enabled')) {
        throw new Exception(
            'Children Work Google Sheets sync is disabled.'
        );
    }

    $spreadsheetId = (string) config(
        'children_work.google_sheets.spreadsheet_id'
    );

    if ($spreadsheetId === '') {
        throw new Exception(
            'Missing CHILDREN_WORK_GOOGLE_SHEETS_ID.'
        );
    }

    $service = $this->sheetsService();
    $sheetTitle = $this->sheetTitle(
        $service,
        $spreadsheetId
    );

    $sheetId = $this->sheetId(
        $service,
        $spreadsheetId,
        $sheetTitle
    );

    $values = $this->sheetRowsFromLesson($lesson);
    $rowCount = count($values);

    $insertRow = $this->chronologicalInsertRow(
        $service,
        $spreadsheetId,
        $sheetTitle,
        $lesson
    );

    /*
     * First make room in Google Sheets.
     */
    $this->insertPhysicalRows(
        $service,
        $spreadsheetId,
        $sheetId,
        $insertRow,
        $rowCount
    );

    /*
     * Because Google moved everything below the insertion
     * point, immediately repair our stored anchor rows.
     */
    ChildrenWorkLesson::query()
        ->where('id', '!=', $lesson->id)
        ->whereNotNull('google_sheet_row_number')
        ->where(
            'google_sheet_row_number',
            '>=',
            $insertRow
        )
        ->increment(
            'google_sheet_row_number',
            $rowCount
        );

    /*
     * Give the new lesson its anchor immediately.
     *
     * If writing values fails afterwards, a retry will
     * update this row instead of inserting another pair.
     */
    $lesson->forceFill([
        'google_sheet_row_number' => $insertRow,
        'google_sheet_row_hash' => null,
        'sync_status' => 'local',
        'sync_error' => null,
    ])->save();

    $this->writeSheetRows(
        $service,
        $spreadsheetId,
        $sheetTitle,
        $insertRow,
        $values
    );

if ($lesson->status === 'special_activity') {
    $this->applySpecialActivityLayout(
        $service,
        $spreadsheetId,
        $sheetId,
        $firstRow
    );
} else {
    $this->applyNormalLessonLayout(
        $service,
        $spreadsheetId,
        $sheetId,
        $insertRow
    );
}

    if (
    $currentRowCount === 2
    && $desiredRowCount === 1
) {
    /*
     * Converting an existing normal lesson into a
     * Special Activity removes its resource row.
     *
     * Never silently destroy native Smart Chips.
     */
    if (
        ! empty(
            $lesson->google_sheet_smart_chip_fields
        )
    ) {
        throw new Exception(
            'This lesson contains Google Sheets Smart Chips. '
            . 'Convert or remove those Smart Chips in Google Sheets '
            . 'before changing this lesson to Special Activity.'
        );
    }

    /*
     * First unmerge A and D.
     */
    $this->removeNormalLessonLayout(
        $service,
        $spreadsheetId,
        $sheetId,
        $firstRow
    );

    /*
     * Physically remove row 2.
     */
    $this->deletePhysicalRows(
        $service,
        $spreadsheetId,
        $sheetId,
        $firstRow + 1,
        1
    );

    /*
     * Everything below moved upward.
     */
    ChildrenWorkLesson::query()
        ->where('id', '!=', $lesson->id)
        ->whereNotNull('google_sheet_row_number')
        ->where(
            'google_sheet_row_number',
            '>',
            $firstRow
        )
        ->decrement(
            'google_sheet_row_number',
            1
        );

    $currentRowCount = 1;
}

    $lesson->forceFill([
        'google_sheet_row_hash' => null,
        'google_sheet_synced_at' => now(),
        'sync_status' => 'synced',
        'sync_error' => null,
    ])->save();
}

public function updateLessonInGoogleSheet(
    ChildrenWorkLesson $lesson
): void {
    if (! $lesson->google_sheet_row_number) {
        $this->insertLessonChronologically($lesson);

        return;
    }

    if (! config('children_work.google_sheets.enabled')) {
        throw new Exception(
            'Children Work Google Sheets sync is disabled.'
        );
    }

    $spreadsheetId = (string) config(
        'children_work.google_sheets.spreadsheet_id'
    );

    if ($spreadsheetId === '') {
        throw new Exception(
            'Missing CHILDREN_WORK_GOOGLE_SHEETS_ID.'
        );
    }

    $service = $this->sheetsService();

    $sheetTitle = $this->sheetTitle(
        $service,
        $spreadsheetId
    );

    $sheetId = $this->sheetId(
        $service,
        $spreadsheetId,
        $sheetTitle
    );

    $firstRow =
        (int) $lesson->google_sheet_row_number;

    /*
     * Determine CURRENT physical structure independently
     * from the lesson's NEW status.
     */
    $currentRowCount =
        $this->sheetLessonRowCount(
            $service,
            $spreadsheetId,
            $sheetTitle,
            $firstRow,
            $lesson->id
        );

    $desiredRows =
        $this->sheetRowsFromLesson($lesson);

    $desiredRowCount =
        count($desiredRows);

    /*
     * ---------------------------------------------------
     * ONE ROW -> TWO ROWS
     * Special Activity -> Normal Meeting
     * ---------------------------------------------------
     */
    if (
        $currentRowCount === 1
        && $desiredRowCount === 2
    ) {
        /*
         * Remove B:G special-activity merge FIRST.
         */
        $this->removeSpecialActivityLayout(
            $service,
            $spreadsheetId,
            $sheetId,
            $firstRow
        );

        $secondRow = $firstRow + 1;

        $this->insertPhysicalRows(
            $service,
            $spreadsheetId,
            $sheetId,
            $secondRow,
            1
        );

        ChildrenWorkLesson::query()
            ->where('id', '!=', $lesson->id)
            ->whereNotNull(
                'google_sheet_row_number'
            )
            ->where(
                'google_sheet_row_number',
                '>=',
                $secondRow
            )
            ->increment(
                'google_sheet_row_number',
                1
            );

        $currentRowCount = 2;
    }

    /*
     * ---------------------------------------------------
     * TWO ROWS -> ONE ROW
     * Normal Meeting -> Special Activity
     * ---------------------------------------------------
     */
    if (
        $currentRowCount === 2
        && $desiredRowCount === 1
    ) {
        /*
         * Do not destroy native Google Smart Chips.
         */
        if (
            ! empty(
                $lesson
                    ->google_sheet_smart_chip_fields
            )
        ) {
            throw new Exception(
                'This lesson contains Google Sheets '
                . 'Smart Chips. Remove or convert those '
                . 'Smart Chips in Google Sheets before '
                . 'changing it to Special Activity.'
            );
        }

        /*
         * A and D may currently span two rows.
         * Unmerge before deleting row 2.
         */
        $this->removeNormalLessonLayout(
            $service,
            $spreadsheetId,
            $sheetId,
            $firstRow
        );

        $secondRow = $firstRow + 1;

        $this->deletePhysicalRows(
            $service,
            $spreadsheetId,
            $sheetId,
            $secondRow,
            1
        );

        ChildrenWorkLesson::query()
            ->where('id', '!=', $lesson->id)
            ->whereNotNull(
                'google_sheet_row_number'
            )
            ->where(
                'google_sheet_row_number',
                '>',
                $firstRow
            )
            ->decrement(
                'google_sheet_row_number',
                1
            );

        $currentRowCount = 1;
    }

    /*
     * ---------------------------------------------------
     * SPECIAL ACTIVITY LAYOUT
     * ---------------------------------------------------
     */
    if ($desiredRowCount === 1) {
        /*
         * Always rebuild the merge.
         *
         * This fixes an existing special activity whose
         * B:G cells were never merged.
         */
        $this->removeSpecialActivityLayout(
            $service,
            $spreadsheetId,
            $sheetId,
            $firstRow
        );

        /*
         * Write Date and title.
         */
        $this->updateSheetCell(
            $service,
            $spreadsheetId,
            $sheetTitle,
            'A' . $firstRow,
            $lesson->scheduled_on
                ?->format('Y-m-d') ?? ''
        );

        $this->updateSheetCell(
            $service,
            $spreadsheetId,
            $sheetTitle,
            'B' . $firstRow,
            $lesson->lesson_title ?? ''
        );

        /*
         * Clear old normal-meeting values before merging.
         */
        foreach (
            ['C', 'D', 'E', 'F', 'G']
            as $column
        ) {
            $this->updateSheetCell(
                $service,
                $spreadsheetId,
                $sheetTitle,
                $column . $firstRow,
                ''
            );
        }

        /*
         * B:G must ALWAYS be merged for a Special Activity.
         */
        $this->applySpecialActivityLayout(
            $service,
            $spreadsheetId,
            $sheetId,
            $firstRow
        );
    }

    /*
     * ---------------------------------------------------
     * NORMAL MEETING LAYOUT
     * ---------------------------------------------------
     */
    if ($desiredRowCount === 2) {
        /*
         * Make sure a stale B:G special merge is gone.
         */
        $this->removeSpecialActivityLayout(
            $service,
            $spreadsheetId,
            $sheetId,
            $firstRow
        );

        /*
         * ROW 1
         */

        $this->updateSheetCell(
            $service,
            $spreadsheetId,
            $sheetTitle,
            'A' . $firstRow,
            $lesson->scheduled_on
                ?->format('Y-m-d') ?? ''
        );

        $this->updateSheetCell(
            $service,
            $spreadsheetId,
            $sheetTitle,
            'D' . $firstRow,
            $lesson->memory_verse ?? ''
        );

        $titleCells = [
            'B' => [
                'field' => 'lesson_title',
            ],
            'C' => [
                'field' => 'suggested_hymn',
            ],
            'E' => [
                'field' => 'story',
            ],
            'F' => [
                'field' =>
                    'presentation_slides',
            ],
            'G' => [
                'field' => 'activity',
            ],
        ];

        foreach (
            $titleCells
            as $column => $settings
        ) {
            $cell =
                $column . $firstRow;

            /*
             * Preserve a Smart Chip if somebody manually
             * placed one in the title row.
             */
            if (
                $this->sheetCellHasSmartChip(
                    $service,
                    $spreadsheetId,
                    $sheetTitle,
                    $cell
                )
            ) {
                continue;
            }

            $field =
                $settings['field'];

            $this->updateSheetCell(
                $service,
                $spreadsheetId,
                $sheetTitle,
                $cell,
                $lesson->{$field} ?? ''
            );
        }

        /*
         * ROW 2 — RESOURCE LINKS
         */

        $secondRow = $firstRow + 1;

        $linkCells = [
            'B' => [
                'url' => 'lesson_url',
                'title' => 'lesson_title',
            ],
            'C' => [
                'url' => 'suggested_hymn_url',
                'title' => 'suggested_hymn',
            ],
            'E' => [
                'url' => 'story_url',
                'title' => 'story',
            ],
            'F' => [
                'url' =>
                    'presentation_slides_url',
                'title' =>
                    'presentation_slides',
            ],
            'G' => [
                'url' => 'activity_url',
                'title' => 'activity',
            ],
        ];

        foreach (
            $linkCells
            as $column => $settings
        ) {
            $urlField =
                $settings['url'];

            /*
             * Native Smart Chip:
             * NEVER rewrite this cell.
             */
            if (
                $lesson->hasSmartChipLink(
                    $urlField
                )
            ) {
                continue;
            }

            $titleField =
                $settings['title'];

            $this->updateSheetCell(
                $service,
                $spreadsheetId,
                $sheetTitle,
                $column . $secondRow,
                $this->resourceLinkValue(
                    $lesson->{$urlField},
                    $lesson->{$titleField}
                )
            );
        }

        /*
         * A and D MUST always span both physical rows.
         *
         * Calling this every Save also repairs an old
         * schedule whose merge formatting is missing.
         */
        $this->applyNormalLessonLayout(
            $service,
            $spreadsheetId,
            $sheetId,
            $firstRow
        );
    }

    $lesson->forceFill([
        /*
         * Let the next Google import calculate its
         * authoritative hash including chip metadata.
         */
        'google_sheet_row_hash' => null,
        'google_sheet_synced_at' => now(),
        'sync_status' => 'synced',
        'sync_error' => null,
    ])->save();
}

public function deleteLessonFromGoogleSheet(
    ChildrenWorkLesson $lesson
): void {
    if (! $lesson->google_sheet_row_number) {
        return;
    }

    if (! config('children_work.google_sheets.enabled')) {
        throw new Exception(
            'Children Work Google Sheets sync is disabled.'
        );
    }

    $spreadsheetId = (string) config(
        'children_work.google_sheets.spreadsheet_id'
    );

    if ($spreadsheetId === '') {
        throw new Exception(
            'Missing CHILDREN_WORK_GOOGLE_SHEETS_ID.'
        );
    }

    $service = $this->sheetsService();

    $sheetTitle = $this->sheetTitle(
        $service,
        $spreadsheetId
    );

    $sheetId = $this->sheetId(
        $service,
        $spreadsheetId,
        $sheetTitle
    );

    $firstRow = (int) $lesson->google_sheet_row_number;

    $rowCount = $this->sheetLessonRowCount(
        $service,
        $spreadsheetId,
        $sheetTitle,
        $firstRow,
        $lesson->id
    );

    /*
     * Real Google Sheets row deletion.
     *
     * Smart Chips on those lesson rows are intentionally
     * deleted because the user is deleting the whole lesson.
     */
    $this->deletePhysicalRows(
        $service,
        $spreadsheetId,
        $sheetId,
        $firstRow,
        $rowCount
    );

    /*
     * Every anchor below the deleted schedule moved upward.
     */
    ChildrenWorkLesson::query()
        ->where('id', '!=', $lesson->id)
        ->whereNotNull('google_sheet_row_number')
        ->where(
            'google_sheet_row_number',
            '>',
            $firstRow
        )
        ->decrement(
            'google_sheet_row_number',
            $rowCount
        );
}

public function pushToGoogleSheet(): array
{
    if (! config('children_work.google_sheets.enabled')) {
        throw new Exception(
            'Children Work Google Sheets sync is disabled.'
        );
    }

    $spreadsheetId = (string) config(
        'children_work.google_sheets.spreadsheet_id'
    );

    if ($spreadsheetId === '') {
        throw new Exception(
            'Missing CHILDREN_WORK_GOOGLE_SHEETS_ID.'
        );
    }

    $service = $this->sheetsService();

    $sheetTitle = $this->sheetTitle(
        $service,
        $spreadsheetId
    );

    $lessons = ChildrenWorkLesson::query()
        ->where(function ($query): void {
            $query
                ->whereNull('google_sheet_row_number')
                ->orWhereNull('sync_status')
                ->orWhere(
                    'sync_status',
                    '!=',
                    'synced'
                );
        })
        ->orderByRaw(
            'CASE WHEN scheduled_on IS NULL THEN 1 ELSE 0 END'
        )
        ->orderBy('scheduled_on')
        ->orderBy('id')
        ->get();

    $created = 0;
    $updated = 0;
    $failed = 0;

    foreach ($lessons as $lesson) {
        try {
            if ($lesson->google_sheet_row_number) {
                $this->updateLessonInGoogleSheet(
                    $lesson
                );

                $updated++;
            } else {
                $this->insertLessonChronologically(
                    $lesson
                );

                $created++;
            }
        } catch (\Throwable $exception) {
            $lesson->forceFill([
                'sync_status' => 'failed',
                'sync_error' =>
                    $exception->getMessage(),
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

    /**
     * Update the two physical rows belonging to an existing lesson.
     *
     * We deliberately avoid updating the chip/resource columns so
     * Google Sheets smart chips are not destroyed.
     */
    /**
     * Update an existing lesson in Google Sheets without
     * overwriting a following standalone schedule.
     *
     * Phase 22D3:
     *
     * A lesson occupies:
     *   - one row when it has no continuation content
     *   - two rows when it has story/slides/activity content
     *
     * We deliberately avoid updating smart-chip cells.
     */
    /**
     * Update ordinary non-chip fields of an existing lesson.
     *
     * Phase 22D3:
     *
     * Only A (Date) and D (Memory Verse) are written back to
     * Google Sheets.
     *
     * B/C/E/F/G are treated as protected cells because they may
     * contain Google Sheets smart chips or other user-managed
     * content.
     *
     * This also means a one-row schedule will never cause the
     * following row to be overwritten.
     */
    private function updateExistingSheetRowsSafely(
        Sheets $service,
        string $spreadsheetId,
        string $sheetTitle,
        ChildrenWorkLesson $lesson,
    ): void {
        $firstRow = (int) $lesson->google_sheet_row_number;

        if ($firstRow <= 0) {
            return;
        }

        $updates = [
            'A' . $firstRow => $lesson->scheduled_on?->format('Y-m-d') ?? '',
            'D' . $firstRow => $lesson->memory_verse ?? '',
        ];

        foreach ($updates as $cell => $value) {
            $range = $this->quoteSheetTitle($sheetTitle)
                . '!'
                . $cell;

            $service->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                new ValueRange([
                    'values' => [[$value]],
                ]),
                [
                    'valueInputOption' => 'USER_ENTERED',
                ]
            );
        }
    }


private function isContinuationRow(
    array $formulaRow,
    array $rawRow,
    array $headers
): bool {
    $formulaMapped = $this->mapRow(
        $headers,
        $formulaRow
    );

    $rawMapped = $this->mapRow(
        $headers,
        $rawRow
    );

    /*
     * Children's Work sheet structure:
     *
     * Main row:
     *   Has a date in column A.
     *
     * Optional continuation/resource row:
     *   Has NO date, but may contain values in B-G.
     *
     * Therefore a value in Lesson, Hymn, Story, Slides,
     * or Activity does NOT make this a new schedule.
     */

    $date = $this->firstValue(
        $rawMapped,
        [
            'date',
            'schedule',
            'scheduled_on',
            'meeting_date',
        ]
    ) ?: $this->firstValue(
        $formulaMapped,
        [
            'date',
            'schedule',
            'scheduled_on',
            'meeting_date',
        ]
    );

    /*
     * A dated row always starts a new logical schedule.
     */
    if (filled($date)) {
        return false;
    }

    /*
     * Completely empty rows are not continuation rows.
     */
    if (
        $this->rowIsEmpty($formulaMapped)
        && $this->rowIsEmpty($rawMapped)
    ) {
        return false;
    }

    /*
     * Any non-empty undated row immediately following
     * a main row is its resource/continuation row.
     */
    return true;
}



private function mergeTwoLessonRows(
    array $first,
    array $second
): array {
    $merged = $first;

    /*
     * Preserve special resource cells from the continuation
     * row without replacing the human-readable title on row 1.
     */
    $lessonResource = $this->firstValue(
        $second,
        [
            'lesson',
            'lessons',
            'lesson_title',
            'topic',
            'title',
        ]
    );

    if (filled($lessonResource)) {
        $merged['__lesson_resource'] = $lessonResource;
    }

    $hymnResource = $this->firstValue(
        $second,
        [
            'suggested_hymn',
            'hymn',
            'song',
        ]
    );

    if (filled($hymnResource)) {
        $merged['__hymn_resource'] = $hymnResource;
    }

    /*
     * Column F on the first row frequently contains:
     *
     *   c/o Dorothy
     *   PPT Presentation:
     *
     * Preserve it before row 2 replaces Presentation Slides.
     */
    $presentationHeading = $this->firstValue(
        $first,
        [
            'presentation_slides',
            'slides',
            'presentation',
        ]
    );

    if (filled($presentationHeading)) {
        $merged['__presentation_heading'] =
            $presentationHeading;
    }

    /*
     * Story, Presentation Slides and Activity normally contain
     * the actual resource/title on the continuation row.
     *
     * Prefer row 2 for these fields.
     */
    foreach ([
        'story',
        'bible_story',
        'presentation_slides',
        'slides',
        'presentation',
        'activity',
        'activities',
    ] as $key) {
        if (
            isset($second[$key])
            && trim((string) $second[$key]) !== ''
        ) {
            $merged[$key] = trim(
                (string) $second[$key]
            );
        }
    }

    /*
     * For every other field, allow row 2 to fill an empty
     * value but never replace a populated main-row value.
     */
    foreach ($second as $key => $value) {
        $value = trim((string) $value);

        if ($value === '') {
            continue;
        }

        if (in_array($key, [
            'story',
            'bible_story',
            'presentation_slides',
            'slides',
            'presentation',
            'activity',
            'activities',
        ], true)) {
            continue;
        }

        if (
            ! isset($merged[$key])
            || trim((string) $merged[$key]) === ''
        ) {
            $merged[$key] = $value;
        }
    }

    return $merged;
}


    private function sheetsService(): Sheets
    {
        $credentialsPath = base_path(
            (string) config(
                'children_work.google_sheets.credentials_path'
            )
        );

        if (! file_exists($credentialsPath)) {
            throw new Exception(
                "Google credentials file not found: {$credentialsPath}"
            );
        }

        $client = new Client();
        $client->setApplicationName(
            'COQP Children Work Lessons'
        );
        $client->setAuthConfig($credentialsPath);
        $client->setScopes([
            Sheets::SPREADSHEETS,
        ]);

        return new Sheets($client);
    }

    private function sheetTitle(
        Sheets $service,
        string $spreadsheetId
    ): string {
        $configured = config(
            'children_work.google_sheets.sheet_name'
        );

        if (filled($configured)) {
            return (string) $configured;
        }

        $spreadsheet = $service->spreadsheets->get(
            $spreadsheetId,
            [
                'fields' => 'sheets.properties.title',
            ]
        );

        $sheets = $spreadsheet->getSheets();

        if ($sheets === []) {
            throw new Exception(
                'No sheets found in spreadsheet.'
            );
        }

        return (string) $sheets[0]
            ->getProperties()
            ->getTitle();
    }

    private function quoteSheetTitle(string $title): string
    {
        return "'" . str_replace(
            "'",
            "''",
            $title
        ) . "'";
    }

    private function normalizeHeaders(array $headerRow): array
    {
        $headers = [];

        foreach ($headerRow as $index => $header) {
            $headers[$index] = Str::of((string) $header)
                ->lower()
                ->replaceMatches(
                    '/[^a-z0-9]+/',
                    '_'
                )
                ->trim('_')
                ->toString();
        }

        return $headers;
    }

    private function mapRow(
        array $headers,
        array $row
    ): array {
        $mapped = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $mapped[$header] = trim(
                (string) ($row[$index] ?? '')
            );
        }

        return $mapped;
    }

private function payloadFromMappedRow(
    array $mapped,
    array $rawMapped,
    array $metadata = []
): array {
    $lessonCell = $this->firstHyperlinkedValue(
        $mapped,
        [
            'lesson',
            'lessons',
            'lesson_title',
            'topic',
            'title',
        ]
    );

    $lessonResourceCell = $this->firstHyperlinkedValue(
        $mapped,
        [
            '__lesson_resource',
        ]
    );

    $hymnCell = $this->firstHyperlinkedValue(
        $mapped,
        [
            'suggested_hymn',
            'hymn',
            'song',
        ]
    );

    $hymnResourceCell = $this->firstHyperlinkedValue(
        $mapped,
        [
            '__hymn_resource',
        ]
    );

    $slidesCell = $this->firstHyperlinkedValue(
        $mapped,
        [
            'presentation_slides',
            'slides',
            'presentation',
        ]
    );

    $activityCell = $this->firstHyperlinkedValue(
        $mapped,
        [
            'activity',
            'activities',
        ]
    );

    $lessonTitle = $lessonCell['label'];
    $hymnTitle = $hymnCell['label'];
    $slidesTitle = $slidesCell['label'];
    $activityTitle = $activityCell['label'];

        return [
            'scheduled_on' => $this->parseDate(
                $this->firstValue(
                    $rawMapped,
                    [
                        'date',
                        'schedule',
                        'scheduled_on',
                        'meeting_date',
                    ]
                )
                ?: $this->firstValue(
                    $mapped,
                    [
                        'date',
                        'schedule',
                        'scheduled_on',
                        'meeting_date',
                    ]
                )
            ),

            'lesson_code' => $this->parseLessonCode(
                $lessonTitle
            ),

            'lesson_title' => $lessonTitle,


'lesson_url' => $this->metadataUrl(
    $metadata,
    [
        'lesson',
        'lessons',
        'lesson_title',
        'topic',
        'title',
    ]
)
    ?: $lessonCell['url']
    ?: $lessonResourceCell['url']
    ?: $this->firstValue(
                    $mapped,
                    [
                        'lesson_link',
                        'lesson_url',
                        'link',
                    ]
                ),

            'suggested_hymn' => $hymnTitle,

'suggested_hymn_url' => $this->metadataUrl(
    $metadata,
    [
        'suggested_hymn',
        'hymn',
        'song',
    ]
)
    ?: $hymnCell['url']
    ?: $hymnResourceCell['url']
    ?: $this->firstValue(
                    $mapped,
                    [
                        'suggested_hymn_link',
                        'hymn_link',
                        'hymn_url',
                    ]
                ),

            'memory_verse' => $this->firstValue(
                $mapped,
                [
                    'memory_verse',
                    'verse',
                ]
            ),

            'story' => $this->firstValue(
                $mapped,
                [
                    'story',
                    'bible_story',
                ]
            ),

'story_url' => $this->metadataUrl(
    $metadata,
    [
        'story',
        'bible_story',
    ]
),

            'presentation_slides' => $slidesTitle,

'presentation_slides_url' => $this->metadataUrl(
    $metadata,
    [
        'presentation_slides',
        'slides',
        'presentation',
    ]
)
    ?: $slidesCell['url']
    ?: $this->firstValue(
        $mapped,
        [
            'presentation_slides_link',
            'slides_link',
            'slides_url',
        ]
    ),

            'activity' => $activityTitle,

'activity_url' => $this->metadataUrl(
    $metadata,
    [
        'activity',
        'activities',
    ]
)
    ?: $activityCell['url']
    ?: $this->firstValue(
        $mapped,
        [
            'activity_link',
            'activity_url',
        ]
    ),

'assigned_to' => $this->firstValue(
    $mapped,
    [
        'assigned_to',
        'c_o',
        'co',
        'person_in_charge',
        'in_charge',
    ]
) ?: $this->extractAssignedTo(
    $this->firstValue(
        $mapped,
        [
            '__presentation_heading',
        ]
    )
),

            'notes' => $this->firstValue(
                $mapped,
                [
                    'notes',
                    'remarks',
                ]
            ),

            'status' => 'scheduled',
        ];
    }

    private function firstHyperlinkedValue(
        array $mapped,
        array $keys
    ): array {
        foreach ($keys as $key) {
            if (
                ! isset($mapped[$key])
                || trim((string) $mapped[$key]) === ''
            ) {
                continue;
            }

            return $this->parseHyperlinkCell(
                (string) $mapped[$key]
            );
        }

        return [
            'label' => null,
            'url' => null,
        ];
    }

    private function parseHyperlinkCell(
        string $value
    ): array {
        $value = trim($value);

        if (preg_match(
            '/^=HYPERLINK\(\s*"((?:[^"]|"")*)"\s*[,;]\s*"((?:[^"]|"")*)"\s*\)$/iu',
            $value,
            $matches
        )) {
            return [
                'url' => str_replace(
                    '""',
                    '"',
                    $matches[1]
                ),
                'label' => str_replace(
                    '""',
                    '"',
                    $matches[2]
                ),
            ];
        }

        return [
            'label' => $value,
            'url' => $this->looksLikeUrl($value)
                ? $value
                : null,
        ];
    }

private function extractAssignedTo(
    ?string $value
): ?string {
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    if (preg_match(
        '/\bc\s*\/?\s*o\s*[:\-]?\s*(.+?)(?:\r?\n|$)/iu',
        $value,
        $matches
    )) {
        $assignedTo = trim($matches[1]);

        return $assignedTo !== ''
            ? $assignedTo
            : null;
    }

    return null;
}


    private function firstValue(
        array $mapped,
        array $keys
    ): ?string {
        foreach ($keys as $key) {
            if (
                isset($mapped[$key])
                && trim((string) $mapped[$key]) !== ''
            ) {
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

    private function parseDate(
        null|string|int|float $value
    ): ?string {
        if (
            $value === null
            || trim((string) $value) === ''
        ) {
            return null;
        }

        $value = trim((string) $value);

        if (is_numeric($value)) {
            return CarbonImmutable::createFromDate(
                1899,
                12,
                30
            )
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
                $date = CarbonImmutable::createFromFormat(
                    $format,
                    $value
                );

                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return CarbonImmutable::parse($value)
                ->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseLessonCode(
        ?string $lesson
    ): ?string {
        if (! filled($lesson)) {
            return null;
        }

        if (preg_match(
            '/lesson\s*[\-#:]?\s*(\d+)/i',
            (string) $lesson,
            $matches
        )) {
            return 'Lesson ' . $matches[1];
        }

        return null;
    }

    /**
     * Produce TWO physical Google Sheet rows.
     *
     * Phase 22D2 layout:
     *
     * Row 1:
     *   A Date
     *   B Lesson
     *   C Suggested Hymn
     *   D Memory Verse
     *
     * Row 2:
     *   E Story
     *   F Presentation Slides
     *   G Activity
     *
     * The remaining cells are intentionally blank.
     */
    /**
     * Produce the Google Sheet rows for one lesson.
     *
     * Phase 22D3:
     *
     * A lesson uses TWO physical rows only when it has continuation
     * content (story, presentation slides, or activity).
     *
     * A standalone schedule/review/no-meeting entry uses ONE row.
     *
     * No Schedule Type column is required.
     */
private function sheetRowsFromLesson(
    ChildrenWorkLesson $lesson
): array {
    /*
     * SPECIAL ACTIVITY
     *
     * One physical row.
     * B:G will be merged by the layout method.
     */
    if ($lesson->status === 'special_activity') {
        return [[
            $lesson->scheduled_on?->format('Y-m-d') ?? '',
            $lesson->lesson_title ?? '',
            '',
            '',
            '',
            '',
            '',
        ]];
    }

    /*
     * NORMAL MEETING / LESSON
     *
     * Always two physical rows.
     *
     * Row 1 = display titles/text
     * Row 2 = resource links
     *
     * A and D are vertically merged by the layout method.
     */
    return [
        [
            $lesson->scheduled_on?->format('Y-m-d') ?? '',
            $lesson->lesson_title ?? '',
            $lesson->suggested_hymn ?? '',
            $lesson->memory_verse ?? '',
            $lesson->story ?? '',
            $lesson->presentation_slides ?? '',
            $lesson->activity ?? '',
        ],
        [
            '',
            $this->resourceLinkValue(
                $lesson->lesson_url,
                $lesson->lesson_title
            ),
            $this->resourceLinkValue(
                $lesson->suggested_hymn_url,
                $lesson->suggested_hymn
            ),
            '',
            $this->resourceLinkValue(
                $lesson->story_url,
                $lesson->story
            ),
            $this->resourceLinkValue(
                $lesson->presentation_slides_url,
                $lesson->presentation_slides
            ),
            $this->resourceLinkValue(
                $lesson->activity_url,
                $lesson->activity
            ),
        ],
    ];
}

private function resourceLinkValue(
    ?string $url,
    ?string $label
): string {
    if (! filled($url)) {
        return '';
    }

    return $this->hyperlinkFormula(
        $url,
        $label
    );
}

    private function mappedRowFromSheetValues(
        array $values
    ): array {
        return [
            'date' => trim(
                (string) ($values[0] ?? '')
            ),
            'lesson' => trim(
                (string) ($values[1] ?? '')
            ),
            'suggested_hymn' => trim(
                (string) ($values[2] ?? '')
            ),
            'memory_verse' => trim(
                (string) ($values[3] ?? '')
            ),
            'story' => trim(
                (string) ($values[4] ?? '')
            ),
            'presentation_slides' => trim(
                (string) ($values[5] ?? '')
            ),
            'activity' => trim(
                (string) ($values[6] ?? '')
            ),
        ];
    }

    private function hyperlinkFormula(
        ?string $url,
        ?string $label
    ): string {
        $url = trim((string) $url);
        $label = trim((string) $label);

        if ($url === '') {
            return $label;
        }

        if ($label === '') {
            $label = $url;
        }

        return '=HYPERLINK("' .
            $this->escapeFormulaString($url) .
            '","' .
            $this->escapeFormulaString($label) .
            '")';
    }

    private function escapeFormulaString(
        string $value
    ): string {
        return str_replace(
            '"',
            '""',
            $value
        );
    }

    private function looksLikeUrl(
        string $value
    ): bool {
        return preg_match(
            '/^https?:\/\//i',
            trim($value)
        ) === 1;
    }

    private function extractRowNumberFromUpdatedRange(
        ?string $range
    ): ?int {
        if (! filled($range)) {
            return null;
        }

        if (preg_match(
            '/![A-Z]+(\d+):[A-Z]+(\d+)$/',
            (string) $range,
            $matches
        )) {
            return (int) $matches[1];
        }

        if (preg_match(
            '/![A-Z]+(\d+)$/',
            (string) $range,
            $matches
        )) {
            return (int) $matches[1];
        }

        return null;
    }

private function sheetId(
    Sheets $service,
    string $spreadsheetId,
    string $sheetTitle
): int {
    $spreadsheet = $service->spreadsheets->get(
        $spreadsheetId
    );

    foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
        $properties = $sheet->getProperties();

        if (
            $properties
            && $properties->getTitle() === $sheetTitle
        ) {
            return (int) $properties->getSheetId();
        }
    }

    throw new Exception(
        "Google Sheet tab '{$sheetTitle}' was not found."
    );
}

private function chronologicalInsertRow(
    Sheets $service,
    string $spreadsheetId,
    string $sheetTitle,
    ChildrenWorkLesson $lesson
): int {
    /*
     * Put the new schedule after all schedules with the same
     * date, but before the first later date.
     */
    if ($lesson->scheduled_on) {
        $nextLesson = ChildrenWorkLesson::query()
            ->where('id', '!=', $lesson->id)
            ->whereNotNull('google_sheet_row_number')
            ->whereNotNull('scheduled_on')
            ->whereDate(
                'scheduled_on',
                '>',
                $lesson->scheduled_on->format('Y-m-d')
            )
            ->orderBy('google_sheet_row_number')
            ->first();

        if ($nextLesson) {
            return (int)
                $nextLesson->google_sheet_row_number;
        }
    }

    /*
     * Nothing later exists: append immediately after the
     * last logical Google schedule.
     */
    $lastLesson = ChildrenWorkLesson::query()
        ->where('id', '!=', $lesson->id)
        ->whereNotNull('google_sheet_row_number')
        ->orderByDesc('google_sheet_row_number')
        ->first();

    if (! $lastLesson) {
        return max(
            (int) config(
                'children_work.google_sheets.header_row',
                1
            ) + 1,
            2
        );
    }

    $lastRow = (int)
        $lastLesson->google_sheet_row_number;

    return $lastRow + $this->sheetLessonRowCount(
        $service,
        $spreadsheetId,
        $sheetTitle,
        $lastRow,
        $lastLesson->id
    );
}

private function sheetLessonRowCount(
    Sheets $service,
    string $spreadsheetId,
    string $sheetTitle,
    int $firstRow,
    ?int $lessonId = null
): int {
    /*
     * First use the next database anchor.
     *
     * Our database row numbers are repaired whenever rows
     * are inserted/deleted, so this is the most reliable
     * indicator:
     *
     * next anchor = first + 1  => one-row schedule
     * next anchor >= first + 2 => two-row schedule
     */
    $nextAnchor = ChildrenWorkLesson::query()
        ->when(
            $lessonId,
            fn ($query) =>
                $query->where('id', '!=', $lessonId)
        )
        ->whereNotNull('google_sheet_row_number')
        ->where(
            'google_sheet_row_number',
            '>',
            $firstRow
        )
        ->orderBy('google_sheet_row_number')
        ->value('google_sheet_row_number');

    if ($nextAnchor !== null) {
        $gap = (int) $nextAnchor - $firstRow;

        if ($gap === 1) {
            return 1;
        }

        if ($gap >= 2) {
            return 2;
        }
    }

    /*
     * Last schedule in the sheet:
     *
     * A normal lesson may have a completely empty row 2,
     * so check whether A or D is vertically merged.
     */
    $sheetId = $this->sheetId(
        $service,
        $spreadsheetId,
        $sheetTitle
    );

    $startRowIndex = $firstRow - 1;
    $endRowIndex = $startRowIndex + 2;

    if (
        $this->hasExactMerge(
            $service,
            $spreadsheetId,
            $sheetId,
            $startRowIndex,
            $endRowIndex,
            0,
            1
        )
        || $this->hasExactMerge(
            $service,
            $spreadsheetId,
            $sheetId,
            $startRowIndex,
            $endRowIndex,
            3,
            4
        )
    ) {
        return 2;
    }

    /*
     * Final fallback: inspect B:G of the next physical row.
     */
    $nextRow = $firstRow + 1;

    $range = $this->quoteSheetTitle($sheetTitle)
        . '!B'
        . $nextRow
        . ':G'
        . $nextRow;

    $values = $service->spreadsheets_values
        ->get(
            $spreadsheetId,
            $range,
            [
                'valueRenderOption' => 'FORMULA',
            ]
        )
        ->getValues();

    $row = $values[0] ?? [];

    foreach ($row as $value) {
        if (trim((string) $value) !== '') {
            return 2;
        }
    }

    return 1;
}

private function insertPhysicalRows(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $rowNumber,
    int $count
): void {
    if ($count < 1) {
        return;
    }

    $startIndex = $rowNumber - 1;

    $request = new \Google\Service\Sheets\Request([
        'insertDimension' =>
            new \Google\Service\Sheets\InsertDimensionRequest([
                'range' =>
                    new \Google\Service\Sheets\DimensionRange([
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $startIndex,
                        'endIndex' => $startIndex + $count,
                    ]),
                'inheritFromBefore' => $rowNumber > 2,
            ]),
    ]);

    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [$request],
        ])
    );
}

private function deletePhysicalRows(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $rowNumber,
    int $count
): void {
    if ($count < 1) {
        return;
    }

    $startIndex = $rowNumber - 1;

    $request = new \Google\Service\Sheets\Request([
        'deleteDimension' =>
            new \Google\Service\Sheets\DeleteDimensionRequest([
                'range' =>
                    new \Google\Service\Sheets\DimensionRange([
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $startIndex,
                        'endIndex' => $startIndex + $count,
                    ]),
            ]),
    ]);

    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [$request],
        ])
    );
}

private function writeSheetRows(
    Sheets $service,
    string $spreadsheetId,
    string $sheetTitle,
    int $firstRow,
    array $values
): void {
    $lastRow = $firstRow + count($values) - 1;

    $range = $this->quoteSheetTitle($sheetTitle)
        . '!A'
        . $firstRow
        . ':G'
        . $lastRow;

    $service->spreadsheets_values->update(
        $spreadsheetId,
        $range,
        new ValueRange([
            'values' => $values,
        ]),
        [
            'valueInputOption' => 'USER_ENTERED',
        ]
    );
}

private function updateSheetCell(
    Sheets $service,
    string $spreadsheetId,
    string $sheetTitle,
    string $cell,
    string $value
): void {
    $range = $this->quoteSheetTitle($sheetTitle)
        . '!'
        . $cell;

    $service->spreadsheets_values->update(
        $spreadsheetId,
        $range,
        new ValueRange([
            'values' => [[$value]],
        ]),
        [
            'valueInputOption' => 'USER_ENTERED',
        ]
    );
}

private function sheetCellHasSmartChip(
    Sheets $service,
    string $spreadsheetId,
    string $sheetTitle,
    string $cellReference
): bool {
    $range = $this->quoteSheetTitle($sheetTitle)
        . '!'
        . $cellReference;

    $spreadsheet = $service->spreadsheets->get(
        $spreadsheetId,
        [
            'ranges' => [$range],
            'includeGridData' => true,
        ]
    );

    $sheet = $spreadsheet->getSheets()[0] ?? null;

    $cell = $sheet
        ?->getData()[0]
        ?->getRowData()[0]
        ?->getValues()[0]
        ?? null;

    if (! $cell) {
        return false;
    }

    return $this->cellHasSmartChip($cell);
}

private function hasExactMerge(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $startRowIndex,
    int $endRowIndex,
    int $startColumnIndex,
    int $endColumnIndex
): bool {
    $spreadsheet = $service->spreadsheets->get(
        $spreadsheetId,
        [
            'fields' => 'sheets(properties(sheetId),merges)',
        ]
    );

    foreach ($spreadsheet->getSheets() ?? [] as $sheet) {
        if (
            (int) $sheet->getProperties()?->getSheetId()
            !== $sheetId
        ) {
            continue;
        }

        foreach ($sheet->getMerges() ?? [] as $merge) {
            if (
                (int) $merge->getStartRowIndex()
                    === $startRowIndex
                && (int) $merge->getEndRowIndex()
                    === $endRowIndex
                && (int) $merge->getStartColumnIndex()
                    === $startColumnIndex
                && (int) $merge->getEndColumnIndex()
                    === $endColumnIndex
            ) {
                return true;
            }
        }
    }

    return false;
}

private function mergeCellsIfNeeded(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $startRowIndex,
    int $endRowIndex,
    int $startColumnIndex,
    int $endColumnIndex
): void {
    if ($this->hasExactMerge(
        $service,
        $spreadsheetId,
        $sheetId,
        $startRowIndex,
        $endRowIndex,
        $startColumnIndex,
        $endColumnIndex
    )) {
        return;
    }

    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [
                new \Google\Service\Sheets\Request([
                    'mergeCells' =>
                        new \Google\Service\Sheets\MergeCellsRequest([
                            'range' =>
                                new \Google\Service\Sheets\GridRange([
                                    'sheetId' => $sheetId,
                                    'startRowIndex' => $startRowIndex,
                                    'endRowIndex' => $endRowIndex,
                                    'startColumnIndex' => $startColumnIndex,
                                    'endColumnIndex' => $endColumnIndex,
                                ]),
                            'mergeType' => 'MERGE_ALL',
                        ]),
                ]),
            ],
        ])
    );
}

private function unmergeCellsIfNeeded(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $startRowIndex,
    int $endRowIndex,
    int $startColumnIndex,
    int $endColumnIndex
): void {
    if (! $this->hasExactMerge(
        $service,
        $spreadsheetId,
        $sheetId,
        $startRowIndex,
        $endRowIndex,
        $startColumnIndex,
        $endColumnIndex
    )) {
        return;
    }

    $service->spreadsheets->batchUpdate(
        $spreadsheetId,
        new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [
                new \Google\Service\Sheets\Request([
                    'unmergeCells' =>
                        new \Google\Service\Sheets\UnmergeCellsRequest([
                            'range' =>
                                new \Google\Service\Sheets\GridRange([
                                    'sheetId' => $sheetId,
                                    'startRowIndex' => $startRowIndex,
                                    'endRowIndex' => $endRowIndex,
                                    'startColumnIndex' => $startColumnIndex,
                                    'endColumnIndex' => $endColumnIndex,
                                ]),
                        ]),
                ]),
            ],
        ])
    );
}

private function applySpecialActivityLayout(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $rowNumber
): void {
    $rowIndex = $rowNumber - 1;

    /*
     * 1. Merge B:G first.
     */
    $this->mergeCellsIfNeeded(
        $service,
        $spreadsheetId,
        $sheetId,
        $rowIndex,
        $rowIndex + 1,
        1,
        7
    );

    /*
     * 2. Yellow background across A:G.
     */
    $this->setRangeBackground(
        $service,
        $spreadsheetId,
        $sheetId,
        $rowIndex,
        $rowIndex + 1,
        0,
        7,
        1.0,
        1.0,
        0.0
    );

    /*
     * 3. Borders AFTER merge.
     */
    $this->applySpecialActivityBorders(
        $service,
        $spreadsheetId,
        $sheetId,
        $rowNumber
    );
}

private function removeSpecialActivityLayout(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $rowNumber
): void {
    $rowIndex = $rowNumber - 1;

    $this->unmergeCellsIfNeeded(
        $service,
        $spreadsheetId,
        $sheetId,
        $rowIndex,
        $rowIndex + 1,
        1,
        7
    );
}

private function applyNormalLessonLayout(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $firstRow
): void {
    $startRowIndex = $firstRow - 1;
    $endRowIndex = $startRowIndex + 2;

    /*
     * 1. Merge Date vertically.
     *
     * A(row 1):A(row 2)
     */
    $this->mergeCellsIfNeeded(
        $service,
        $spreadsheetId,
        $sheetId,
        $startRowIndex,
        $endRowIndex,
        0,
        1
    );

    /*
     * 2. Merge Memory Verse vertically.
     *
     * D(row 1):D(row 2)
     */
    $this->mergeCellsIfNeeded(
        $service,
        $spreadsheetId,
        $sheetId,
        $startRowIndex,
        $endRowIndex,
        3,
        4
    );

    /*
     * 3. White background across both rows A:G.
     */
    $this->setRangeBackground(
        $service,
        $spreadsheetId,
        $sheetId,
        $startRowIndex,
        $endRowIndex,
        0,
        7,
        1.0,
        1.0,
        1.0
    );

    /*
     * 4. Borders AFTER merging.
     *
     * Medium outside, thin inside.
     */
    $this->applyNormalLessonBorders(
        $service,
        $spreadsheetId,
        $sheetId,
        $firstRow
    );
}

private function removeNormalLessonLayout(
    Sheets $service,
    string $spreadsheetId,
    int $sheetId,
    int $firstRow
): void {
    $startRowIndex = $firstRow - 1;
    $endRowIndex = $startRowIndex + 2;

    $this->unmergeCellsIfNeeded(
        $service,
        $spreadsheetId,
        $sheetId,
        $startRowIndex,
        $endRowIndex,
        0,
        1
    );

    $this->unmergeCellsIfNeeded(
        $service,
        $spreadsheetId,
        $sheetId,
        $startRowIndex,
        $endRowIndex,
        3,
        4
    );
}

    private function rowHash(array $mapped): string
    {
        ksort($mapped);

        return hash(
            'sha256',
            json_encode(
                $mapped,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );
    }
}
