<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AttendanceSheets;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheet;
use App\Support\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceSheetSessionController extends Controller
{
    public function store(
        Request $request,
        AttendanceSheet $sheet,
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        abort_unless(
            $sheet->sheet_type === AttendanceSheet::TYPE_CUSTOM,
            404
        );

        abort_unless(
            $sheet->schedule_type === AttendanceSheet::SCHEDULE_MANUAL,
            404
        );

        $data = $request->validate([
            'manual_session_date' => [
                'required',
                'date',
            ],
        ]);

        $sessionDate =
            CarbonImmutable::parse(
                $data['manual_session_date']
            )->startOfDay();

        $alreadyExists =
            $sheet->sessions()
                ->whereDate(
                    'session_date',
                    $sessionDate->toDateString()
                )
                ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'manual_session_date' =>
                    'This Session date already exists.',
            ]);
        }

        $session = DB::transaction(
            function () use (
                $sheet,
                $sessionDate
            ) {
                $session =
                    $sheet->sessions()->create([
                        'session_date' =>
                            $sessionDate->toDateString(),

                        'session_time' =>
                            $sheet->meeting_time,

                        'session_end_time' =>
                            $sheet->end_time,

                        'title' =>
                            $sheet->title
                            . ' - '
                            . $sessionDate->format(
                                'M d, Y'
                            ),
                    ]);

                /*
                 * Keep Start/End Date as the envelope
                 * of all manually-added Session dates.
                 */
                $startDate =
                    $sheet->start_date
                        ? CarbonImmutable::parse(
                            $sheet->start_date
                        )
                        : $sessionDate;

                $endDate =
                    $sheet->end_date
                        ? CarbonImmutable::parse(
                            $sheet->end_date
                        )
                        : $sessionDate;

                if ($sessionDate->lessThan($startDate)) {
                    $startDate = $sessionDate;
                }

                if ($sessionDate->greaterThan($endDate)) {
                    $endDate = $sessionDate;
                }

                $sheet->forceFill([
                    'start_date' =>
                        $startDate->toDateString(),

                    'end_date' =>
                        $endDate->toDateString(),
                ])->save();

                /*
                 * If the public Meeting Form is enabled,
                 * give the new Session its stable URL.
                 */
                $sheet->ensureMeetingFormSlugs();

                ActivityLogger::log(
                    action:
                        'attendance_sheet.session.created',

                    subject: $sheet,

                    description:
                        'Added a Manual Date attendance Session.',

                    newValues: [
                        'sheet_id' => $sheet->id,
                        'session_id' => $session->id,

                        'session_date' =>
                            $sessionDate->toDateString(),

                        'session_time' =>
                            $session->session_time,

                        'session_end_time' =>
                            $session->session_end_time,
                    ],
                );

                return $session;
            }
        );

        return redirect(
            AttendanceSheets::getUrl()
            . '?'
            . http_build_query([
                'sheetId' => $sheet->id,
                'sessionId' => $session->id,
            ])
        )
            ->with(
                'attendance_session_added',
                true
            )
            ->with(
                'attendance_session_added_date',
                $sessionDate->format('M d, Y')
            );
    }

    public function destroy(
        AttendanceSheet $sheet,
        AttendanceSession $session,
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        abort_unless(
            $sheet->sheet_type === AttendanceSheet::TYPE_CUSTOM,
            404
        );

        abort_unless(
            $sheet->schedule_type === AttendanceSheet::SCHEDULE_MANUAL,
            404
        );

        abort_unless(
            (int) $session->attendance_sheet_id === (int) $sheet->id,
            404
        );

        $session->loadCount([
            'records',
            'meetingResponses',
            'immichAssets',
            'immichDetections',
        ]);

        $sessionDate =
            $session->session_date->format('Y-m-d');

        $hasCampusActivity =
            $session->campusActivity()->exists();

        /*
         * Exact one-day participant periods are Session-specific
         * preparation and should not be silently orphaned.
         *
         * Wider participant periods do not block removing one
         * Manual Date.
         */
        $exactParticipantPeriods =
            AttendanceParticipant::query()
                ->where(
                    'attendance_sheet_id',
                    $sheet->id
                )
                ->whereDate(
                    'starts_on',
                    $sessionDate
                )
                ->whereDate(
                    'ends_on',
                    $sessionDate
                )
                ->count();

        $dependencyCount =
            $session->records_count
            + $session->meeting_responses_count
            + $session->immich_assets_count
            + $session->immich_detections_count
            + $exactParticipantPeriods
            + ($hasCampusActivity ? 1 : 0);

        if ($dependencyCount > 0) {
            throw ValidationException::withMessages([
                'manual_session_delete' =>
                    'This Session Date cannot be removed because it already has attendance, pre-listed responses, Immich evidence, participant preparation, or another linked activity.',
            ]);
        }

        $deletedValues = [
            'session_id' => $session->id,
            'session_date' => $sessionDate,
            'session_time' => $session->session_time,
            'session_end_time' => $session->session_end_time,
        ];

        DB::transaction(
            function () use (
                $sheet,
                $session,
                $deletedValues
            ): void {
                $session->delete();

                $remainingDates =
                    $sheet->sessions()
                        ->orderBy('session_date')
                        ->pluck('session_date');

                $sheet->forceFill([
                    'start_date' =>
                        $remainingDates->isEmpty()
                            ? null
                            : $remainingDates->first(),

                    'end_date' =>
                        $remainingDates->isEmpty()
                            ? null
                            : $remainingDates->last(),
                ])->save();

                ActivityLogger::log(
                    action:
                        'attendance_sheet.session.deleted',

                    subject: $sheet,

                    description:
                        'Removed an unused Manual Date attendance Session.',

                    oldValues: $deletedValues,
                );
            }
        );

        $nextSession =
            $sheet->sessions()
                ->orderBy('session_date')
                ->orderBy('id')
                ->first();

        return redirect(
            AttendanceSheets::getUrl()
            . '?'
            . http_build_query(
                array_filter([
                    'sheetId' => $sheet->id,
                    'sessionId' => $nextSession?->id,
                ])
            )
        )
            ->with(
                'attendance_session_removed',
                true
            )
            ->with(
                'attendance_session_removed_date',
                CarbonImmutable::parse($sessionDate)
                    ->format('M d, Y')
            );
    }

}
