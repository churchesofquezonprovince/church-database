<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_participants',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                $table->unsignedInteger(
                    'person_id'
                );

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'scp_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'person_id',
                    'scp_person_fk'
                )
                    ->references('id')
                    ->on('persons')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'person_id',
                    ],
                    'scp_contact_person_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_participants'
        );
    }
};
