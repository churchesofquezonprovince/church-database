<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $sourceTitle =
        '2026 International Memorial Day Blending Conference';

    private string $generalSubject =
        'The Great Need for a New Revival';

    private string $startDate =
        '2026-08-10';

    public function up(): void
    {
        $now = now();

        $publication =
            DB::table(
                'morning_revival_publications'
            )
                ->where(
                    'source_title',
                    $this->sourceTitle
                )
                ->where(
                    'general_subject',
                    $this->generalSubject
                )
                ->where(
                    'start_date',
                    $this->startDate
                )
                ->first();

        if ($publication) {
            $publicationId =
                (int) $publication->id;

            DB::table(
                'morning_revival_publications'
            )
                ->where(
                    'id',
                    $publicationId
                )
                ->update([
                    'is_active' =>
                        true,

                    'updated_at' =>
                        $now,
                ]);
        } else {
            $publicationId =
                DB::table(
                    'morning_revival_publications'
                )
                    ->insertGetId([
                        'source_title' =>
                            $this->sourceTitle,

                        'general_subject' =>
                            $this->generalSubject,

                        'start_date' =>
                            $this->startDate,

                        'is_active' =>
                            true,

                        'created_at' =>
                            $now,

                        'updated_at' =>
                            $now,
                    ]);
        }

        $weeks = [
            1 => [
                'start_date' =>
                    '2026-08-10',

                'title' =>
                    'Cooperating with the Lord to Bring In a New Revival That Will End This Age',
            ],

            2 => [
                'start_date' =>
                    '2026-08-17',

                'title' =>
                    'Arriving at the Highest Peak of the Divine Revelation',
            ],

            3 => [
                'start_date' =>
                    '2026-08-24',

                'title' =>
                    'The God-man Living for a New Revival',
            ],

            4 => [
                'start_date' =>
                    '2026-08-31',

                'title' =>
                    'Living the Life of a God-man by Living in the Kingdom of God as the Realm of the Divine Species',
            ],

            5 => [
                'start_date' =>
                    '2026-09-07',

                'title' =>
                    'The Apostolic Ministry in Cooperation with Christ\'s Heavenly Ministry to Shepherd the Church of God as His Flock for the Building Up of the Body of Christ and a New Revival',
            ],

            6 => [
                'start_date' =>
                    '2026-09-14',

                'title' =>
                    'Shepherding according to God',
            ],
        ];

        foreach (
            $weeks
            as $weekNumber => $week
        ) {
            DB::table(
                'morning_revival_weeks'
            )
                ->updateOrInsert(
                    [
                        'morning_revival_publication_id' =>
                            $publicationId,

                        'week_number' =>
                            $weekNumber,
                    ],
                    [
                        'title' =>
                            $week['title'],

                        'start_date' =>
                            $week['start_date'],

                        'is_active' =>
                            true,

                        'created_at' =>
                            $now,

                        'updated_at' =>
                            $now,
                    ]
                );
        }
    }

    public function down(): void
    {
        $publicationId =
            DB::table(
                'morning_revival_publications'
            )
                ->where(
                    'source_title',
                    $this->sourceTitle
                )
                ->where(
                    'general_subject',
                    $this->generalSubject
                )
                ->where(
                    'start_date',
                    $this->startDate
                )
                ->value('id');

        if (! $publicationId) {
            return;
        }

        /*
         * Weeks cascade automatically.
         */
        DB::table(
            'morning_revival_publications'
        )
            ->where(
                'id',
                $publicationId
            )
            ->delete();
    }
};
