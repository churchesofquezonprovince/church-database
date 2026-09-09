<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists =
            DB::table('shepherding_activity_types')
                ->where('code', 'A')
                ->exists();

        if ($exists) {
            return;
        }

        DB::table('shepherding_activity_types')
            ->insert([
                'code' =>
                    'A',

                'name' =>
                    'Mystery of Human Life',

                'category' =>
                    'Gospel Work',

                'is_active' =>
                    true,

                'sort_order' =>
                    85,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);
    }

    public function down(): void
    {
        DB::table('shepherding_activity_types')
            ->where('code', 'A')
            ->where(
                'name',
                'Mystery of Human Life'
            )
            ->delete();
    }
};
