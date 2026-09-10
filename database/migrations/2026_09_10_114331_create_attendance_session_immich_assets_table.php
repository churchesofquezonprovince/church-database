<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'attendance_session_immich_assets',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * attendance_sessions.id is INT UNSIGNED.
                 */
                $table->unsignedInteger(
                    'attendance_session_id'
                );

                $table->string(
                    'immich_asset_id',
                    36
                );

                $table->string(
                    'immich_asset_name'
                )->nullable();

                $table->timestamp(
                    'asset_taken_at'
                )->nullable()->index();

                $table->timestamps();

                $table
                    ->foreign(
                        'attendance_session_id'
                    )
                    ->references('id')
                    ->on('attendance_sessions')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'attendance_session_id',
                        'immich_asset_id',
                    ],
                    'attendance_session_immich_asset_unique'
                );

                $table->index(
                    'immich_asset_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attendance_session_immich_assets'
        );
    }
};
