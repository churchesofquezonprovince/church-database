<?php

namespace App\Console\Commands;

use App\Services\ConferenceRegistrationImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportConferenceRegistration extends Command
{
    protected $signature =
        'conference:import-registration
        {--event=1 : Conference event ID}
        {--date=2026-09-26 : Conference date}
        {--file= : Normalized private JSON payload}
        {--apply : Write the import}
        {--confirm= : Required confirmation token for --apply}';

    protected $description =
        'Preview or import normalized Conference registration data';

    public function handle(
        ConferenceRegistrationImporter $importer
    ): int {
        $eventId =
            (int) $this->option('event');

        $date =
            trim(
                (string) $this->option(
                    'date'
                )
            );

        $file =
            trim(
                (string) $this->option(
                    'file'
                )
            );

        if ($file === '') {
            $file =
                storage_path(
                    'app/private/'
                    . 'conference-registration-import.json'
                );
        }

        try {
            $plan =
                $importer->preview(
                    $eventId,
                    $date,
                    $file
                );

            $this->renderPlan($plan);

            if (! $this->option('apply')) {
                $this->newLine();

                $this->info(
                    'PREVIEW ONLY — database unchanged.'
                );

                return self::SUCCESS;
            }

            if (
                $plan['blockers'] !== []
            ) {
                $this->error(
                    'Import refused because blockers exist.'
                );

                return self::FAILURE;
            }

            if (
                (string) $this->option(
                    'confirm'
                )
                !== 'IMPORT-157'
            ) {
                $this->error(
                    'Apply refused. Use '
                    . '--confirm=IMPORT-157 '
                    . 'after reviewing the preview.'
                );

                return self::FAILURE;
            }

            $this->newLine();

            $this->warn(
                'Applying registration import...'
            );

            $result =
                $importer->apply(
                    $eventId,
                    $date,
                    $file
                );

            $this->newLine();

            $this->info(
                'Registration import completed.'
            );

            $this->line(
                'Attendance records before: '
                . $result[
                    'attendance_records_before'
                ]
            );

            $this->line(
                'Attendance records after: '
                . $result[
                    'attendance_records_after'
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }

    private function renderPlan(
        array $plan
    ): void {
        $this->info(
            'Conference Registration Import Preview'
        );

        $this->line(
            'Event: #'
            . $plan['event_id']
        );

        $this->line(
            'Sheet: #'
            . $plan['sheet_id']
        );

        $this->line(
            'Session: #'
            . $plan['session_id']
            . ' · '
            . $plan['date']
        );

        $this->newLine();

        $this->line(
            'Existing People: '
            . $plan['counts']['person']
        );

        $this->line(
            'Existing form Guests: '
            . $plan['counts']['form_guest']
        );

        $this->line(
            'Existing conference_reg Guests: '
            . $plan['counts'][
                'registration_guest'
            ]
        );

        $this->line(
            'Would create new Guests: '
            . $plan['counts']['new_guest']
        );

        $this->line(
            'Classified: '
            . $plan['classified']
            . ' / 157'
        );

        $this->newLine();

        $this->line(
            'Already enrolled: '
            . $plan['already_enrolled']
        );

        $this->line(
            'Would add enrollment: '
            . $plan['needs_enrollment']
        );

        $this->line(
            'Existing role correct: '
            . $plan['role_correct']
        );

        $this->line(
            'Role missing / would add: '
            . $plan['role_missing']
        );

        $this->newLine();

        $this->line(
            'Participant columns:'
        );

        foreach (
            $plan['fields']
            as $field
        ) {
            $this->line(
                '  '
                . $field['name']
                . ' ['
                . $field['type']
                . '] '
                . (
                    $field['exists']
                        ? 'EXISTS'
                        : 'WOULD CREATE'
                )
            );
        }

        $this->newLine();

        $this->line(
            'People locality differences: '
            . count(
                $plan[
                    'locality_differences'
                ]
            )
        );

        foreach (
            $plan[
                'locality_differences'
            ]
            as $difference
        ) {
            $this->line(
                '  '
                . $difference['name']
                . ': Registration='
                . $difference[
                    'registration'
                ]
                . ' / People DB='
                . $difference[
                    'database'
                ]
            );
        }

        $this->newLine();

        $this->line(
            'Source Attendance true: '
            . $plan[
                'source_attendance_true'
            ]
        );

        $this->line(
            'Source Attendance false: '
            . $plan[
                'source_attendance_false'
            ]
        );

        $this->line(
            'Current Attendance records: '
            . $plan[
                'attendance_records'
            ]
        );

        $this->line(
            'Attendance records this importer writes: 0'
        );

        $this->newLine();

        if (
            $plan['blockers'] === []
        ) {
            $this->info(
                'READY — no blocking conflicts.'
            );
        } else {
            $this->error(
                'BLOCKERS:'
            );

            foreach (
                $plan['blockers']
                as $blocker
            ) {
                $this->line(
                    '  - '
                    . $blocker
                );
            }
        }
    }
}
