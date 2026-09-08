<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immich_person_caches', function (Blueprint $table): void {
            $table->id();

            $table->string('immich_person_id', 191)->unique();
            $table->string('immich_name')->nullable();

            /*
             * Immich's own person updatedAt.
             * Used to determine whether our cached thumbnail/name
             * needs refreshing.
             */
            $table->timestamp('immich_updated_at')->nullable()->index();

            /*
             * Relative path on the Laravel public storage disk.
             */
            $table->string('thumbnail_path')->nullable();

            $table->timestamp('thumbnail_synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immich_person_caches');
    }
};
