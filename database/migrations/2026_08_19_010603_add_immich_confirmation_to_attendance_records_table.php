<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->boolean('immich_confirmed')
                ->default(false)
                ->after('attendance_source');

            $table->timestamp('immich_confirmed_at')
                ->nullable()
                ->after('immich_confirmed');

            $table->foreignId('immich_confirmed_by_id')
                ->nullable()
                ->after('immich_confirmed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropForeign(['immich_confirmed_by_id']);
            $table->dropColumn([
                'immich_confirmed',
                'immich_confirmed_at',
                'immich_confirmed_by_id',
            ]);
        });
    }
};
