<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table(
            'shepherding_activity_types'
        )->updateOrInsert(
            [
                'code' => 'HM',
            ],
            [
                'name' => 'Home Meeting',
                'category' => 'Shepherding',
                'sort_order' => 45,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table(
            'shepherding_activity_types'
        )
            ->where('code', 'HM')
            ->delete();
    }
};
