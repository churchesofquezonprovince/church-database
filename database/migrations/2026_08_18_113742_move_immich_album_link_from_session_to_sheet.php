<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sheet_immich_albums', function (Blueprint $table): void {
            $table->increments('id');

            $table->unsignedInteger('attendance_sheet_id');
            $table->string('immich_album_id', 36);
            $table->string('immich_album_name');

            $table->boolean('enabled')
                ->default(true)
                ->index();

            $table->timestamp('last_modified_asset_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique('attendance_sheet_id');
            $table->unique('immich_album_id');

            $table->foreign('attendance_sheet_id')
                ->references('id')
                ->on('attendance_sheets')
                ->cascadeOnDelete();
        });

        /*
         * Preserve existing Phase 23 links.
         *
         * Existing:
         * AttendanceSession -> Immich Album
         *
         * New:
         * AttendanceSheet -> Immich Album
         *
         * If multiple sessions from the same sheet somehow point to
         * different albums, the first encountered mapping is retained.
         */
        $existingMappings = DB::table('attendance_session_immich_albums')
            ->join(
                'attendance_sessions',
                'attendance_sessions.id',
                '=',
                'attendance_session_immich_albums.attendance_session_id'
            )
            ->select([
                'attendance_sessions.attendance_sheet_id',
                'attendance_session_immich_albums.immich_album_id',
                'attendance_session_immich_albums.immich_album_name',
                'attendance_session_immich_albums.enabled',
                'attendance_session_immich_albums.last_modified_asset_at',
                'attendance_session_immich_albums.last_synced_at',
            ])
            ->orderBy('attendance_session_immich_albums.id')
            ->get();

        foreach ($existingMappings as $mapping) {
            $alreadyExists = DB::table('attendance_sheet_immich_albums')
                ->where(
                    'attendance_sheet_id',
                    $mapping->attendance_sheet_id
                )
                ->exists();

            $albumAlreadyUsed = DB::table('attendance_sheet_immich_albums')
                ->where(
                    'immich_album_id',
                    $mapping->immich_album_id
                )
                ->exists();

            if ($alreadyExists || $albumAlreadyUsed) {
                continue;
            }

            DB::table('attendance_sheet_immich_albums')->insert([
                'attendance_sheet_id' => $mapping->attendance_sheet_id,
                'immich_album_id' => $mapping->immich_album_id,
                'immich_album_name' => $mapping->immich_album_name,
                'enabled' => $mapping->enabled,
                'last_modified_asset_at' => $mapping->last_modified_asset_at,
                'last_synced_at' => $mapping->last_synced_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::dropIfExists('attendance_session_immich_albums');
    }

    public function down(): void
    {
        Schema::create('attendance_session_immich_albums', function (Blueprint $table): void {
            $table->increments('id');

            $table->unsignedInteger('attendance_session_id');
            $table->string('immich_album_id', 36);
            $table->string('immich_album_name');

            $table->boolean('enabled')
                ->default(true)
                ->index();

            $table->timestamp('last_modified_asset_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique('attendance_session_id');
            $table->unique('immich_album_id');

            $table->foreign('attendance_session_id')
                ->references('id')
                ->on('attendance_sessions')
                ->cascadeOnDelete();
        });

        DB::table('attendance_sheet_immich_albums')
            ->orderBy('id')
            ->each(function ($mapping): void {
                $session = DB::table('attendance_sessions')
                    ->where(
                        'attendance_sheet_id',
                        $mapping->attendance_sheet_id
                    )
                    ->orderBy('session_date')
                    ->first();

                if (! $session) {
                    return;
                }

                DB::table('attendance_session_immich_albums')->insert([
                    'attendance_session_id' => $session->id,
                    'immich_album_id' => $mapping->immich_album_id,
                    'immich_album_name' => $mapping->immich_album_name,
                    'enabled' => $mapping->enabled,
                    'last_modified_asset_at' => $mapping->last_modified_asset_at,
                    'last_synced_at' => $mapping->last_synced_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::dropIfExists('attendance_sheet_immich_albums');
    }
};
