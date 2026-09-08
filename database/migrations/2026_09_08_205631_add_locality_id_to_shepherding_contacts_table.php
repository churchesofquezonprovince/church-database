<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->foreignId('locality_id')
                    ->nullable()
                    ->after('person_id')
                    ->constrained('localities')
                    ->restrictOnDelete();

                $table->index(
                    ['locality_id', 'contact_date'],
                    'shepherding_contacts_locality_date_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'shepherding_contacts_locality_date_idx'
                );

                $table->dropConstrainedForeignId(
                    'locality_id'
                );
            }
        );
    }
};
