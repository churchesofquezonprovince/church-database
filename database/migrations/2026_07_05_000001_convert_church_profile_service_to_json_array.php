<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE church_profiles MODIFY service TEXT NULL');

        DB::table('church_profiles')
            ->whereNotNull('service')
            ->orderBy('id')
            ->chunkById(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $service = trim((string) $profile->service);

                    if ($service === '') {
                        DB::table('church_profiles')
                            ->where('id', $profile->id)
                            ->update(['service' => null]);

                        continue;
                    }

                    $decoded = json_decode($service, true);

                    $groups = is_array($decoded)
                        ? $decoded
                        : explode(',', $service);

                    $groups = collect($groups)
                        ->map(fn ($group): string => trim((string) $group))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    DB::table('church_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'service' => $groups === [] ? null : json_encode($groups),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('church_profiles')
            ->whereNotNull('service')
            ->orderBy('id')
            ->chunkById(100, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $decoded = json_decode((string) $profile->service, true);

                    DB::table('church_profiles')
                        ->where('id', $profile->id)
                        ->update([
                            'service' => is_array($decoded)
                                ? collect($decoded)->filter()->implode(', ')
                                : $profile->service,
                        ]);
                }
            });

        DB::statement('ALTER TABLE church_profiles MODIFY service VARCHAR(100) NULL');
    }
};
