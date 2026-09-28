<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * -------------------------------------------------------
         * Schedules: canonical Locality
         * -------------------------------------------------------
         */
        Schema::table('schedules', function (Blueprint $table): void {
            $table
                ->foreignId('locality_id')
                ->nullable()
                ->after('locality')
                ->constrained('localities')
                ->restrictOnDelete();
        });

        /*
         * A Google event ID is only meaningful together with its
         * Google Calendar ID.
         */
        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropUnique([
                'google_event_id',
            ]);

            $table->unique(
                [
                    'google_calendar_id',
                    'google_event_id',
                ],
                'schedules_google_calendar_event_unique'
            );
        });

        /*
         * -------------------------------------------------------
         * Attendance Sessions: trace back to Schedule and retain
         * the Schedule venue/location.
         * -------------------------------------------------------
         */
        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table
                    ->foreignId('schedule_id')
                    ->nullable()
                    ->after('attendance_sheet_id')
                    ->constrained('schedules')
                    ->nullOnDelete();

                $table->unique([
                    'schedule_id',
                ]);

                $table
                    ->string('location')
                    ->nullable()
                    ->after('title');
            }
        );

        /*
         * -------------------------------------------------------
         * Backfill canonical Locality IDs.
         * -------------------------------------------------------
         *
         * Only active configured Localities are accepted.
         * Unknown legacy text is preserved but is not assigned
         * an ID.
         */
        $localities = DB::table('localities')
            ->where('is_active', true)
            ->get([
                'id',
                'name',
            ])
            ->mapWithKeys(
                fn ($locality): array => [
                    mb_strtolower(
                        trim((string) $locality->name)
                    ) => $locality,
                ]
            );

        DB::table('schedules')
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($schedules) use ($localities): void {
                    foreach ($schedules as $schedule) {
                        $key = mb_strtolower(
                            trim((string) $schedule->locality)
                        );

                        $locality = $localities->get($key);

                        if (! $locality) {
                            continue;
                        }

                        DB::table('schedules')
                            ->where('id', $schedule->id)
                            ->update([
                                'locality_id' =>
                                    $locality->id,

                                'locality' =>
                                    $locality->name,
                            ]);
                    }
                }
            );

        /*
         * Older Attendance Sheets created by the Calendar used
         * only the Locality text field. Canonicalize those too.
         */
        DB::table('attendance_sheets')
            ->whereNull('locality_id')
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($sheets) use ($localities): void {
                    foreach ($sheets as $sheet) {
                        $key = mb_strtolower(
                            trim((string) $sheet->locality)
                        );

                        $locality = $localities->get($key);

                        if (! $locality) {
                            continue;
                        }

                        DB::table('attendance_sheets')
                            ->where('id', $sheet->id)
                            ->update([
                                'locality_id' =>
                                    $locality->id,

                                'locality' =>
                                    $locality->name,
                            ]);
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::table(
            'attendance_sessions',
            function (Blueprint $table): void {
                $table->dropUnique([
                    'schedule_id',
                ]);

                $table->dropConstrainedForeignId(
                    'schedule_id'
                );

                $table->dropColumn('location');
            }
        );

        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropUnique(
                'schedules_google_calendar_event_unique'
            );

            $table->unique([
                'google_event_id',
            ]);
        });

        Schema::table('schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId(
                'locality_id'
            );
        });
    }
};
