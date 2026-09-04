<?php

namespace App\Http\Controllers;

use App\Models\AttendanceMeetingResponse;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AttendanceMeetingResponseController extends Controller
{
    public function destroy(
        AttendanceMeetingResponse $response
    ): RedirectResponse {
        abort_unless(
            auth()->user()?->canManageRecords(),
            403
        );

        /*
         * Preserve enough information for the activity log
         * before deleting the response itself.
         *
         * Do not log guest_profile because it may contain
         * contact details and is not needed for this audit.
         */
        $oldValues = [
            'meeting_response_id' =>
                $response->id,

            'attendance_session_id' =>
                $response->attendance_session_id,

            'respondent_type' =>
                $response->respondent_type,

            'original_source' =>
                $response->original_source,

            'respondent_name' =>
                $response->respondent_name,

            'response' =>
                $response->response,

            'person_id' =>
                $response->person_id,

            'campus_contact_id' =>
                $response->campus_contact_id,

            'responded_at' =>
                $response->responded_at
                    ?->format('Y-m-d H:i:s'),
        ];

        /*
         * Use the AttendanceSession as the durable activity-log
         * subject because the response itself is being deleted.
         */
        $session = $response->session;

        DB::transaction(
            function () use ($response): void {
                /*
                 * IMPORTANT:
                 *
                 * Deleting a pre-listed response deletes ONLY
                 * the AttendanceMeetingResponse.
                 *
                 * It must never remove:
                 * - Person
                 * - Campus Contact
                 * - AttendanceParticipant
                 * - AttendanceRecord
                 * - Immich detections
                 */
                $response->delete();
            }
        );

        ActivityLogger::log(
            action:
                'attendance_meeting_response.deleted',

            subject:
                $session,

            description:
                'Deleted pre-listed response while preserving linked People, Campus, participant, attendance, and Immich records.',

            oldValues:
                $oldValues,
        );

        return back()
            ->with(
                'meeting_response_deleted',
                true
            )
            ->with(
                'meeting_response_deleted_name',
                $oldValues['respondent_name']
            );
    }
}
