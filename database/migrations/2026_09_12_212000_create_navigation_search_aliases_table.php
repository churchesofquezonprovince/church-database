<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'navigation_search_aliases',
            function (Blueprint $table): void {
                $table->id();

                $table->string('phrase', 150);

                /*
                 * item  = one navigation entry
                 * group = an entire navigation group
                 */
                $table->string('target_type', 20);

                /*
                 * Optional group restriction for item targets.
                 *
                 * Null means:
                 * match this item label wherever it is visible.
                 */
                $table->string(
                    'target_group',
                    150
                )->nullable();

                $table->string(
                    'target_label',
                    150
                );

                $table->boolean('is_active')
                    ->default(true);

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(0);

                $table->timestamps();

                $table->index([
                    'phrase',
                    'is_active',
                ]);
            }
        );

        $now = now();

        DB::table(
            'navigation_search_aliases'
        )->insert([
            [
                'phrase' => "Lord's Table Meeting",
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Check Attendance',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'lords table',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Check Attendance',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'ltm',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Check Attendance',
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'Prayer Meeting',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Check Attendance',
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'pm',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Check Attendance',
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'meeting',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Check Attendance',
                'is_active' => true,
                'sort_order' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'immich',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Attendance Sheets',
                'is_active' => true,
                'sort_order' => 70,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'young people',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'YP Meeting',
                'is_active' => true,
                'sort_order' => 80,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'gow',
                'target_type' => 'group',
                'target_group' => null,
                'target_label' => 'Shepherding',
                'is_active' => true,
                'sort_order' => 90,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'gospel work',
                'target_type' => 'group',
                'target_group' => null,
                'target_label' => 'Shepherding',
                'is_active' => true,
                'sort_order' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'bnpb',
                'target_type' => 'group',
                'target_group' => null,
                'target_label' => 'Shepherding',
                'is_active' => true,
                'sort_order' => 110,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'setup',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Ministry Books',
                'is_active' => true,
                'sort_order' => 120,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'setup',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Users',
                'is_active' => true,
                'sort_order' => 130,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'set-up',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Ministry Books',
                'is_active' => true,
                'sort_order' => 140,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'phrase' => 'set-up',
                'target_type' => 'item',
                'target_group' => null,
                'target_label' => 'Users',
                'is_active' => true,
                'sort_order' => 150,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'navigation_search_aliases'
        );
    }
};
