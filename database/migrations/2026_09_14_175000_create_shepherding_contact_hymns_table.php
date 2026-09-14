<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_hymns',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'shepherding_contact_id'
                )
                    ->constrained(
                        'shepherding_contacts'
                    )
                    ->cascadeOnDelete();

                $table->foreignId('hymn_id')
                    ->constrained('hymns')
                    ->restrictOnDelete();

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(1);

                $table->timestamps();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'hymn_id',
                    ],
                    'shepherding_contact_hymns_unique'
                );

                $table->index(
                    [
                        'shepherding_contact_id',
                        'sort_order',
                    ],
                    'shepherding_contact_hymns_order_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_hymns'
        );
    }
};
