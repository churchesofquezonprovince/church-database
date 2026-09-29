<?php

namespace Tests\Feature;

use App\Services\ConferenceParticipantFields as Fields;
use App\Services\ConferenceWorkspace as Workspace;
use App\Services\GuestAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConferenceParticipantFieldsTest
    extends GuestAttendanceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        (
            require database_path(
                'migrations/'
                . '2026_09_29_150000_'
                . 'create_conference_participant_fields.php'
            )
        )->up();
    }


    public function test_checkbox_preserves_unknown_yes_and_no(): void
    {
        $eventId =
            Workspace::create(
                1,
                [1],
                'Conference'
            );

        $fieldId =
            Fields::create(
                $eventId,
                'With Invite?',
                'checkbox'
            );

        $this->assertNull(
            Fields::value(
                $eventId,
                $fieldId,
                1
            )
        );

        Fields::saveValue(
            $eventId,
            $fieldId,
            1,
            true
        );

        $this->assertTrue(
            Fields::value(
                $eventId,
                $fieldId,
                1
            )
        );

        Fields::saveValue(
            $eventId,
            $fieldId,
            1,
            false
        );

        $this->assertFalse(
            Fields::value(
                $eventId,
                $fieldId,
                1
            )
        );

        Fields::saveValue(
            $eventId,
            $fieldId,
            1,
            null
        );

        $this->assertNull(
            Fields::value(
                $eventId,
                $fieldId,
                1
            )
        );
    }


    public function test_dropdown_rejects_unknown_option(): void
    {
        $eventId =
            Workspace::create(
                1,
                [1],
                'Conference'
            );

        $fieldId =
            Fields::create(
                $eventId,
                'Transportation',
                'select',
                false,
                [
                    'Bus',
                    'Private Vehicle',
                ]
            );

        Fields::saveValue(
            $eventId,
            $fieldId,
            1,
            'Bus'
        );

        $this->assertSame(
            'Bus',
            Fields::value(
                $eventId,
                $fieldId,
                1
            )
        );

        $this->expectException(
            ValidationException::class
        );

        Fields::saveValue(
            $eventId,
            $fieldId,
            1,
            'Airplane'
        );
    }


    public function test_guest_field_value_moves_when_linked_to_person(): void
    {
        $guestId =
            GuestAttendance::enroll(
                1,
                1,
                'this_meeting',
                null,
                null,
                'Conference Guest',
                'Lucban'
            );

        $eventId =
            Workspace::create(
                1,
                [1],
                'Conference'
            );

        $fieldId =
            Fields::create(
                $eventId,
                'IDs in Canva',
                'checkbox'
            );

        Fields::saveValue(
            $eventId,
            $fieldId,
            -$guestId,
            true
        );

        GuestAttendance::linkPerson(
            1,
            $guestId,
            3
        );

        $this->assertTrue(
            Fields::value(
                $eventId,
                $fieldId,
                3
            )
        );

        $this->assertFalse(
            DB::table(
                'conference_participant_field_values'
            )
                ->where(
                    'conference_participant_field_id',
                    $fieldId
                )
                ->where(
                    'attendee_key',
                    -$guestId
                )
                ->exists()
        );
    }


    public function test_conflicting_values_stop_identity_link(): void
    {
        $guestId =
            GuestAttendance::enroll(
                1,
                1,
                'this_meeting',
                null,
                null,
                'Conference Guest',
                'Lucban'
            );

        /*
         * Person #3 must already be a real attendee in this
         * conference so both identities can legitimately
         * carry conference values before the attempted link.
         */
        DB::table(
            'attendance_participants'
        )->insert([
            'attendance_sheet_id' => 1,
            'person_id' => 3,
            'starts_on' => '2026-09-26',
            'ends_on' => '2026-09-26',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventId =
            Workspace::create(
                1,
                [1],
                'Conference'
            );

        $fieldId =
            Fields::create(
                $eventId,
                'IDs in Canva',
                'checkbox'
            );

        Fields::saveValue(
            $eventId,
            $fieldId,
            -$guestId,
            true
        );

        Fields::saveValue(
            $eventId,
            $fieldId,
            3,
            false
        );

        try {
            GuestAttendance::linkPerson(
                1,
                $guestId,
                3
            );

            $this->fail(
                'Conflicting participant fields '
                . 'should stop the identity link.'
            );
        } catch (ValidationException) {
            //
        }

        $this->assertNull(
            DB::table(
                'attendance_guests'
            )
                ->where(
                    'id',
                    $guestId
                )
                ->value(
                    'linked_person_id'
                )
        );

        $this->assertTrue(
            Fields::value(
                $eventId,
                $fieldId,
                -$guestId
            )
        );

        $this->assertFalse(
            Fields::value(
                $eventId,
                $fieldId,
                3
            )
        );
    }
}
