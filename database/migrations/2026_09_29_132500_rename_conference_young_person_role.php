<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table(
            'conference_person_details'
        )
            ->where(
                'event_role',
                'young_person'
            )
            ->update([
                'event_role' =>
                    'young_people',
            ]);
    }


    public function down(): void
    {
        DB::table(
            'conference_person_details'
        )
            ->where(
                'event_role',
                'young_people'
            )
            ->update([
                'event_role' =>
                    'young_person',
            ]);
    }
};
