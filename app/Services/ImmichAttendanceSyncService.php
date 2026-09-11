<?php

namespace App\Services;

use App\Models\AttendanceImmichAssetDetection;
use App\Models\AttendanceParticipant;
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
    $response = $this->scanSessionAssets($session);

    $assetCount = 0;

    /*
     * Unique Immich people detected across all photos.
     *
     * Keyed by Immich UUID so a person appearing in 20 photos
     * is still counted as ONE person.
     */
    $detectedPeople = [];

    $detectionCount = 0;

    $mappedPeople = [];
    $alreadyPresentPeople = [];
    $createdPeople = [];

    $createdCount = 0;
    $alreadyPresentCount = 0;

    /*
     * Unmatched people, also keyed by Immich UUID.
     */
    $unmatched = [];

    foreach ($response['assets'] as $asset) {
        $assetId = $asset['id'] ?? null;

        if (! $assetId) {
            continue;
        }

        $assetCount++;

        foreach (($asset['people'] ?? []) as $immichPerson) {
            if (! is_array($immichPerson)) {
                continue;
            }

            $immichPersonId = $immichPerson['id'] ?? null;

            if (! $immichPersonId) {
                continue;
            }

            $detectionCount++;

            /*
             * Record the unique Immich person.
             */
            $detectedPeople[$immichPersonId] = [
                'id' => $immichPersonId,
                'name' => trim(
                    (string) ($immichPerson['name'] ?? '')
                ),
                'thumbnailPath' =>
                    $immichPerson['thumbnailPath'] ?? null,
            ];

            /*
             * Permanent Immich UUID → Church Person mapping.
             */
            $mapping = ImmichPersonMapping::query()
                ->where('immich_person_id', $immichPersonId)
                ->first();

            $churchPersonId = $mapping?->person_id;

            /*
             * Preserve every asset/person detection for auditing.
             */
            AttendanceImmichAssetDetection::updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'immich_asset_id' => $assetId,
                    'immich_person_id' => $immichPersonId,
                ],
                [
                    'person_id' => $churchPersonId,
                    'asset_taken_at' =>
                        $asset['localDateTime'] ?? null,
                    'detected_at' => now(),
                ],
            );

            /*
             * No permanent mapping yet.
             */
            if (! $churchPersonId) {
                $unmatched[$immichPersonId] = [
                    'id' => $immichPersonId,
                    'name' => trim(
                        (string) ($immichPerson['name'] ?? '')
                    ),
                    'thumbnailPath' =>
                        $immichPerson['thumbnailPath'] ?? null,
                ];

                continue;
            }

            /*
             * Mapped person → attendance.
             */

$mappedPeople[$churchPersonId] = true;

            $result = $this->markPresent(
                session: $session,
                personId: $churchPersonId,
            );

            if ($result === 'created') {
                $createdPeople[$churchPersonId] = true;
            } elseif (
                in_array(
                    $result,
                    [
                        'already_present',
                        'manual_preserved',
                    ],
                    true,
                )
            ) {
                /*
                 * Keep the existing public sync result contract.
                 * A protected manual decision is not counted as
                 * newly-added Immich attendance.
                 */
                $alreadyPresentPeople[$churchPersonId] = true;
            }



        }
    }

    if ($response['source'] === 'album') {
        $session
            ->sheet
            ?->immichAlbum
            ?->update([
                'last_modified_asset_at' => now(),
                'last_synced_at' => now(),
            ]);
    }

    return [
        'source' => $response['source'],
        'assets' => $assetCount,

        /*
         * Total photo/person detections.
         * Useful for diagnostics but not the primary attendance count.
         */
        'detections' => $detectionCount,

        /*
         * Actual unique people seen in the day's photos.
         */
        'unique_people' => count($detectedPeople),

        /*
         * Number of unique detected people having a Church mapping.
         */
        'mapped_people' =>
            count($detectedPeople) - count($unmatched),

'matched' => count($createdPeople),
'already_present' => count($alreadyPresentPeople),

        'unmatched' => array_values($unmatched),
    ];
}

    /**
     * Scan all assets belonging to the linked album that fall on
     * the Attendance Session's date.
     *
     * We deliberately rescan the day's assets on every manual sync.
     * This keeps the behavior correct when Immich recognizes a face
     * after the original photograph was uploaded.
     */
    protected function scanSessionAssets(
        AttendanceSession $session,
    ): array {
        /*
         * Exact Session photos have priority.
         *
         * If even one exact photo is linked, the
         * Sheet album is deliberately ignored.
         */
        $exactAssets = $session
            ->immichAssets()
            ->orderBy('id')
            ->get();

        if ($exactAssets->isNotEmpty()) {
            $assets = [];

            foreach ($exactAssets as $exactAsset) {
                $asset = $this->immich->asset(
                    $exactAsset->immich_asset_id
                );

                if (
                    ! is_array($asset)
                    || blank($asset['id'] ?? null)
                ) {
                    continue;
                }

                $assets[$asset['id']] = $asset;
            }

            return [
                'source' => 'exact_photos',
                'assets' => array_values($assets),
            ];
        }

        /*
         * No exact Session photos:
         * fall back to the Sheet's linked album.
         */
        $album = $session->sheet?->immichAlbum;

        if (! $album) {
            throw new RuntimeException(
                'No exact Immich photo is linked to '
                . 'this Session and no Immich album '
                . 'is linked to the Attendance Sheet.'
            );
        }

        if (! $album->enabled) {
            throw new RuntimeException(
                'Immich synchronization is disabled '
                . 'for this Attendance Sheet.'
            );
        }

        $allAssets = [];

        $page = 1;

        do {
            $response =
                $this->immich
                    ->searchAlbumAssetsForDate(
                        albumId:
                            $album->immich_album_id,

                        date:
                            $session
                                ->session_date
                                ->format('Y-m-d'),

                        page: $page,
                        size: 1000,
                    );

            $items = data_get(
                $response,
                'assets.items',
                [],
            );

            foreach ($items as $asset) {
                if (
                    is_array($asset)
                    && filled($asset['id'] ?? null)
                ) {
                    $allAssets[$asset['id']] =
                        $asset;
                }
            }

            $nextPage = data_get(
                $response,
                'assets.nextPage',
            );

            if (! $nextPage) {
                break;
            }

            $page = (int) $nextPage;
        } while ($page <= 100);

        return [
            'source' => 'album',
            'assets' =>
                array_values($allAssets),
        ];
    }

    /**
     * Mark a mapped Church Person present.
     *
     * Returns:
     *
     * created           = new Immich attendance record
     * already_present   = existing Immich attendance
     * manual_preserved  = administrator decision preserved
     *
     * Manual attendance and roster corrections are NEVER
     * overwritten by Immich.
     */
    protected function markPresent(
        AttendanceSession $session,
        int $personId,
    ): string {
        return DB::transaction(
            function () use ($session, $personId): string {
                /*
                 * Manual attendance is authoritative.
                 *
                 * Check the AttendanceRecord BEFORE changing roster
                 * membership. If an administrator has corrected this
                 * Session manually, Immich may preserve its detection
                 * history but must not change attendance or reactivate
                 * the Person.
                 */
                $record =
                    AttendanceRecord::query()
                        ->where(
                            'attendance_session_id',
                            $session->id
                        )
                        ->where(
                            'person_id',
                            $personId
                        )
                        ->first();

                if (
                    $record
                    &&
                    $record->attendance_source
                        === AttendanceRecord::SOURCE_MANUAL
                ) {
                    return 'manual_preserved';
                }

                /*
                 * There is no manual decision protecting this Session.
                 *
                 * Immich may establish a meeting-only participant
                 * identity when necessary.
                 */
                $sessionDate =
                    $session
                        ->session_date
                        ->format('Y-m-d');

                /*
                 * Reuse only a participant period that actually
                 * covers this Session.
                 *
                 * A July 29 meeting-only participant must not be
                 * mistaken for an August 12 participant merely
                 * because Sheet + Person are the same.
                 */
                $participant =
                    AttendanceParticipant::query()
                        ->where(
                            'attendance_sheet_id',
                            $session->attendance_sheet_id
                        )
                        ->where(
                            'person_id',
                            $personId
                        )
                        ->activeOn($sessionDate)
                        ->first();

                if (! $participant) {
                    /*
                     * A Person removed from this exact Session may
                     * be detected again on a later Immich sync.
                     *
                     * Re-sync means "re-read the Immich evidence",
                     * so reactivate the same meeting-only participant
                     * rather than treating removal as permanent.
                     */
                    $participant =
                        AttendanceParticipant::query()
                            ->where(
                                'attendance_sheet_id',
                                $session->attendance_sheet_id
                            )
                            ->where(
                                'person_id',
                                $personId
                            )
                            ->where(
                                'is_active',
                                false
                            )
                            ->whereDate(
                                'starts_on',
                                $sessionDate
                            )
                            ->whereDate(
                                'ends_on',
                                $sessionDate
                            )
                            ->first();

                    if ($participant) {
                        $participant->update([
                            'is_active' => true,
                        ]);
                    } else {
                        AttendanceParticipant::create([
                            'attendance_sheet_id' =>
                                $session->attendance_sheet_id,

                            'person_id' =>
                                $personId,

                            'starts_on' =>
                                $sessionDate,

                            'ends_on' =>
                                $sessionDate,

                            'is_active' =>
                                true,
                        ]);
                    }
                }

                /*
                 * No attendance record yet.
                 */
                if (! $record) {
                    AttendanceRecord::create([
                        'attendance_session_id' =>
                            $session->id,

                        'person_id' =>
                            $personId,

                        'status' =>
                            AttendanceRecord::STATUS_PRESENT,

                        'is_present' =>
                            true,

                        'attendance_source' =>
                            AttendanceRecord::SOURCE_IMMICH,

                        'immich_confirmed' =>
                            false,

                        'immich_confirmed_at' =>
                            null,

                        'immich_confirmed_by_id' =>
                            null,

                        'marked_by_id' =>
                            null,

                        'marked_at' =>
                            now(),
                    ]);

                    return 'created';
                }

                /*
                 * Existing Immich record.
                 *
                 * Keep it present and simply refresh its timestamp.
                 */
                $record->update([
                    'status' => AttendanceRecord::STATUS_PRESENT,
                    'is_present' => true,
                    'attendance_source' => AttendanceRecord::SOURCE_IMMICH,
                    'marked_by_id' => null,
                    'marked_at' => now(),
                ]);

                return 'already_present';
            },
        );
    }
}
