<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $types = [
            [
                'code' => 'B',
                'name' => 'Mystery of Christian Life',
                'category' => 'Gospel Work',
                'is_active' => true,
                'sort_order' => 86,
            ],
            [
                'code' => 'C',
                'name' => '5 Points of Baptism',
                'category' => 'Gospel Work',
                'is_active' => true,
                'sort_order' => 87,
            ],
        ];

        foreach ($types as $type) {
            $exists =
                DB::table('shepherding_activity_types')
                    ->where('code', $type['code'])
                    ->exists();

            if ($exists) {
                continue;
            }

            DB::table('shepherding_activity_types')
                ->insert([
                    ...$type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('shepherding_activity_types')
            ->where(function ($query): void {
                $query
                    ->where(function ($query): void {
                        $query
                            ->where('code', 'B')
                            ->where(
                                'name',
                                'Mystery of Christian Life'
                            );
                    })
                    ->orWhere(function ($query): void {
                        $query
                            ->where('code', 'C')
                            ->where(
                                'name',
                                '5 Points of Baptism'
                            );
                    });
            })
            ->delete();
    }
};
