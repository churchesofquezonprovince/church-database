<?php

namespace App\Console\Commands;

use App\Models\AttendanceSheetImmichAlbum;
use App\Services\ImmichAttendanceSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncImmichAttendance extends Command
{
    protected $signature = 'attendance:sync-immich';

    protected $description =
        'Synchronize Immich-recognized people into attendance automatically';

    public function handle(
        ImmichAttendanceSyncService $syncService,
    ): int {
        $startedAt = CarbonImmutable::now();

        $albums = AttendanceSheetImmichAlbum::query()
            ->with([
                'attendanceSheet.sessions',
            ])
            ->where('enabled', true)
            ->whereHas(
                'attendanceSheet',
                fn ($query) => $query
                    ->where('is_active', true)
            )
            ->get();

        if ($albums->isEmpty()) {
            $this->info('No active Attendance Sheets have Immich albums.');

            return self::SUCCESS;
        }

        $sheetCount = 0;
        $sessionCount = 0;
        $assetCount = 0;
        $uniquePeopleCount = 0;
        $createdCount = 0;
        $alreadyPresentCount = 0;
        $unmatchedCount = 0;
        $errorCount = 0;

        foreach ($albums as $album) {
            $sheet = $album->attendanceSheet;

            if (! $sheet) {
                continue;
            }

            $sheetCount++;
$sheetHadError = false;

            /*
             * Capture the checkpoint BEFORE processing any sessions.
             *
             * Every session in this sheet gets the same checkpoint.
             */
            $updatedAfter = $album->last_synced_at
                ? CarbonImmutable::parse($album->last_synced_at)
                : null;

            foreach ($sheet->sessions as $session) {
                /*
                 * Do not process future Attendance Sessions.
                 */
                if ($session->session_date->isFuture()) {
                    continue;
                }

                $sessionCount++;

                try {
                    $result = $syncService->sync(
                        session: $session,
                        updatedAfter: $updatedAfter,
                    );

                    $assetCount += $result['assets'];
                    $uniquePeopleCount += $result['unique_people'];
                    $createdCount += $result['matched'];
                    $alreadyPresentCount += $result['already_present'];
                    $unmatchedCount += count($result['unmatched']);
                } catch (\Throwable $e) {
                    $errorCount++;

                    Log::error(
                        'Automatic Immich attendance sync failed.',
                        [
                            'attendance_sheet_id' => $sheet->id,
                            'attendance_session_id' => $session->id,
                            'message' => $e->getMessage(),
                        ]
                    );

                    $this->error(
                        "Session {$session->id}: {$e->getMessage()}"
                    );
                }
            }

            /*
             * Advance the checkpoint only after the entire sheet has
             * been processed.
             */
            if (! $sheetHadError) {
                $album->update([
                    'last_synced_at' => $startedAt,
                    'last_modified_asset_at' => $startedAt,
                ]);
            }
        }

        $this->info(
            'Immich attendance sync complete.'
        );

        $this->line(
            "Sheets: {$sheetCount}"
            . " · Sessions: {$sessionCount}"
            . " · Photos: {$assetCount}"
            . " · Unique people: {$uniquePeopleCount}"
            . " · Added: {$createdCount}"
            . " · Already present: {$alreadyPresentCount}"
            . " · Unmatched detections: {$unmatchedCount}"
            . " · Errors: {$errorCount}"
        );

        return $errorCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
