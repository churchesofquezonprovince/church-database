<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_profiles', function (Blueprint $table): void {
            $table->unsignedSmallInteger('baptism_year')->nullable()->after('baptism_date');
            $table->unsignedTinyInteger('baptism_month')->nullable()->after('baptism_year');
            $table->unsignedTinyInteger('baptism_day')->nullable()->after('baptism_month');
        });

        DB::table('church_profiles')
            ->whereNotNull('baptism_date')
            ->orderBy('id')
            ->chunkById(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $date = \Carbon\CarbonImmutable::parse($profile->baptism_date);

                    DB::table('church_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'baptism_year' => (int) $date->format('Y'),
                            'baptism_month' => (int) $date->format('m'),
                            'baptism_day' => (int) $date->format('d'),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('church_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'baptism_year',
                'baptism_month',
                'baptism_day',
            ]);
        });
    }
};
