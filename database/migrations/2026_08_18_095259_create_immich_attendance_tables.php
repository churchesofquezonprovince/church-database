<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Permanent one-to-one mapping:
         *
         * Immich Person UUID <-> Church Person
         */
        Schema::create('immich_person_mappings', function (Blueprint $table): void {
            $table->increments('id');

            $table->unsignedInteger('person_id');
            $table->string('immich_person_id', 36);
            $table->string('immich_name')->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique('person_id');
            $table->unique('immich_person_id');

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();
        });

        /*
         * Connect one AttendanceSession to one Immich Album.
         */
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

        /*
         * Audit trail:
         * Which Immich asset/person caused a Church Person
         * to be detected for an attendance session?
         */
        Schema::create('attendance_immich_asset_detections', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->unsignedInteger('attendance_session_id');

            $table->string('immich_asset_id', 36);
            $table->string('immich_person_id', 36);

            $table->unsignedInteger('person_id')->nullable();

            $table->timestamp('asset_taken_at')->nullable();
            $table->timestamp('detected_at')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'attendance_session_id',
                    'immich_asset_id',
                    'immich_person_id',
                ],
                'attendance_immich_asset_person_unique'
            );

            $table->index('immich_asset_id');
            $table->index('immich_person_id');

            $table->foreign('attendance_session_id')
                ->references('id')
                ->on('attendance_sessions')
                ->cascadeOnDelete();

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();
        });

        /*
         * Track where an attendance record came from.
         */
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->string('attendance_source')
                ->default('manual')
                ->after('is_present')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropColumn('attendance_source');
        });

        Schema::dropIfExists('attendance_immich_asset_detections');
        Schema::dropIfExists('attendance_session_immich_albums');
        Schema::dropIfExists('immich_person_mappings');
    }
};
