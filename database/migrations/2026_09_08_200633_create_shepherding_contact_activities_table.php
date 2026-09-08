<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_activities',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                $table->unsignedBigInteger(
                    'shepherding_activity_type_id'
                );

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'sca_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->foreign(
                    'shepherding_activity_type_id',
                    'sca_activity_type_fk'
                )
                    ->references('id')
                    ->on('shepherding_activity_types')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'shepherding_contact_id',
                        'shepherding_activity_type_id',
                    ],
                    'sca_contact_activity_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_activities'
        );
    }
};
