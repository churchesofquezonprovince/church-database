<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AttendanceSheets;
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
}
