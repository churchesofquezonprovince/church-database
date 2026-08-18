<?php

namespace App\Services;

use App\Models\AttendanceImmichAssetDetection;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ImmichPersonMapping;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImmichAttendanceSyncService
{
    public function __construct(
        protected ImmichApiService $immich,
    ) {
    }

    public function sync(AttendanceSession $session): array
    {
        $album = $session->immichAlbum;

        if (! $album) {
            throw new RuntimeException(
                'No Immich album is linked to this attendance session.'
            );
        }

        if (! $album->enabled) {
            throw new RuntimeException(
                'Immich synchronization is disabled for this session.'
            );
        }

        $assetCount = 0;
        $detectionCount = 0;
        $matchedCount = 0;
        $unmatched = [];

        $page = 1;

        do {
            $response = $this->immich->searchAlbumAssets(
                albumId: $album->immich_album_id,
                page: $page,
                size: 1000,
                updatedAfter: $album->last_synced_at?->toIso8601String(),
            );

            $items = data_get($response, 'assets.items', []);

            foreach ($items as $asset) {
                $assetId = $asset['id'] ?? null;

                if (! $assetId) {
                    continue;
                }

                $assetCount++;

                foreach (($asset['people'] ?? []) as $immichPerson) {
                    $immichPersonId = $immichPerson['id'] ?? null;
                    $immichName = $immichPerson['name'] ?? null;

                    if (! $immichPersonId) {
                        continue;
                    }

                    $detectionCount++;

                    $mapping = ImmichPersonMapping::query()
                        ->with('person')
                        ->where('immich_person_id', $immichPersonId)
                        ->first();

                    $churchPersonId = $mapping?->person_id;

                    AttendanceImmichAssetDetection::updateOrCreate(
                        [
                            'attendance_session_id' => $session->id,
                            'immich_asset_id' => $assetId,
                            'immich_person_id' => $immichPersonId,
                        ],
                        [
                            'person_id' => $churchPersonId,
                            'asset_taken_at' => $asset['localDateTime'] ?? null,
                            'detected_at' => now(),
                        ],
                    );

                    if (! $churchPersonId) {
                        $unmatched[$immichPersonId] = [
                            'id' => $immichPersonId,
                            'name' => $immichName,
                        ];

                        continue;
                    }

                    $matchedCount++;

                    $this->markPresent(
                        session: $session,
                        personId: $churchPersonId,
                    );
                }
            }

            $nextPage = data_get($response, 'assets.nextPage');

            if (! $nextPage) {
                break;
            }

            $page = (int) $nextPage;
        } while (true);

        $album->update([
            'last_modified_asset_at' => now(),
            'last_synced_at' => now(),
        ]);

        return [
            'assets' => $assetCount,
            'detections' => $detectionCount,
            'matched' => $matchedCount,
            'unmatched' => array_values($unmatched),
        ];
    }

    protected function markPresent(
        AttendanceSession $session,
        int $personId,
    ): void {
        DB::transaction(function () use ($session, $personId): void {
            $record = AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('person_id', $personId)
                ->first();

            if (! $record) {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'person_id' => $personId,
                    'status' => AttendanceRecord::STATUS_PRESENT,
                    'is_present' => true,
                    'attendance_source' => AttendanceRecord::SOURCE_IMMICH,
                    'marked_by_id' => null,
                    'marked_at' => now(),
                ]);

                return;
            }

            /*
             * Manual attendance always wins.
             *
             * An Immich sync must never overwrite a manually
             * entered attendance decision.
             */
            if ($record->attendance_source === AttendanceRecord::SOURCE_MANUAL) {
                return;
            }

            $record->update([
                'status' => AttendanceRecord::STATUS_PRESENT,
                'is_present' => true,
                'attendance_source' => AttendanceRecord::SOURCE_IMMICH,
                'marked_by_id' => null,
                'marked_at' => now(),
            ]);
        });
    }
}
