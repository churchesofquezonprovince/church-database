<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table) {
            /*
             * A Household may be headed by an unlinked
             * Gospel Contact before that contact is promoted
             * into the People Database.
             *
             * household_head_id remains the canonical Person
             * head field.
             */
            $table
                ->unsignedInteger('gospel_contact_head_id')
                ->nullable()
                ->after('household_head_id');

            $table
                ->foreign('gospel_contact_head_id')
                ->references('id')
                ->on('gospel_contacts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropForeign([
                'gospel_contact_head_id',
            ]);

            $table->dropColumn(
                'gospel_contact_head_id'
            );
        });
    }
};
