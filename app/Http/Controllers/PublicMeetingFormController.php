<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMeetingResponse;
use App\Models\AttendanceParticipant;
use App\Models\AttendanceSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicMeetingFormController extends Controller
{
    public function show(string $slug): View
    {
        $session = $this->publicSession($slug);

        return view('meeting.show', [
            'session' => $session,
            'sheet' => $session->sheet,
            'participants' => $this->participantsForSession(
                $session
            ),
        ]);
    }

    public function store(
        Request $request,
        string $slug
    ): RedirectResponse {
        $session = $this->publicSession($slug);

        $data = $request->validate([
            'person_id' => [
                'required',
                'integer',
                'exists:persons,id',
            ],
            'response' => [
                'required',
                'in:yes,no',
            ],
        ]);

        /*
         * Do not trust person_id just because the person
         * exists.
         *
         * They must actually belong to this attendance sheet
         * and be active for this specific meeting date.
         */
        $participant = $this
            ->participantsForSession($session)
            ->firstWhere(
                'person_id',
                (int) $data['person_id']
            );

        if (! $participant) {
            throw ValidationException::withMessages([
                'person_id' =>
                    'That person is not available for this meeting.',
            ]);
        }

        $response = AttendanceMeetingResponse::query()
            ->updateOrCreate(
                [
                    'attendance_session_id' =>
                        $session->id,

                    'person_id' =>
                        (int) $participant->person_id,
                ],
                [
                    'response' => $data['response'],
                    'responded_at' => now(),
                ],
            );

        return redirect()
            ->to(
                secure_url(
                    '/meeting/' . $session->public_slug
                )
            )
            ->with('meeting_response_saved', true)
            ->with(
                'meeting_response_name',
                $participant->person?->display_name
                    ?? 'Participant'
            )
            ->with(
                'meeting_response_value',
                $response->response
            );
    }

    private function publicSession(
        string $slug
    ): AttendanceSession {
        $session = AttendanceSession::query()
            ->where('public_slug', $slug)
            ->with('sheet')
            ->firstOrFail();

        /*
         * Public links only work while:
         *
         * - the attendance sheet is active
         * - Normal Meeting Form is enabled
         */
        abort_unless(
            $session->sheet
                && $session->sheet->is_active
                && $session->sheet->meetingFormEnabled(),
            404
        );

        return $session;
    }

    private function participantsForSession(
        AttendanceSession $session
    ): Collection {
        $sessionDate = $session
            ->session_date
            ->format('Y-m-d');

        return AttendanceParticipant::query()
            ->with('person')
            ->where(
                'attendance_sheet_id',
                $session->attendance_sheet_id
            )
            ->where('is_active', true)
            ->where(function ($query) use (
                $sessionDate
            ): void {
                $query
                    ->whereNull('starts_on')
                    ->orWhereDate(
                        'starts_on',
                        '<=',
                        $sessionDate
                    );
            })
            ->where(function ($query) use (
                $sessionDate
            ): void {
                $query
                    ->whereNull('ends_on')
                    ->orWhereDate(
                        'ends_on',
                        '>=',
                        $sessionDate
                    );
            })
            ->whereHas('person')
            ->get()
            ->sortBy(
                fn (
                    AttendanceParticipant $participant
                ): string =>
                    mb_strtolower(
                        $participant
                            ->person
                            ?->display_name
                            ?? ''
                    )
            )
            ->values();
    }
}
