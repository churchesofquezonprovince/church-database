<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'parent_relationships',
            function (Blueprint $table): void {
                /*
                 * Temporary identity link while the parent /
                 * guardian exists only in Gospel Contacts.
                 *
                 * gospel_contacts.id = INT UNSIGNED
                 */
                $table->unsignedInteger(
                    'gospel_contact_id'
                )
                    ->nullable()
                    ->after('parent_id');

                $table->foreign(
                    'gospel_contact_id',
                    'parent_relationships_gospel_contact_fk'
                )
                    ->references('id')
                    ->on('gospel_contacts')
                    ->nullOnDelete();

                $table->index(
                    'gospel_contact_id',
                    'parent_relationships_gospel_contact_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'parent_relationships',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'parent_relationships_gospel_contact_fk'
                );

                $table->dropIndex(
                    'parent_relationships_gospel_contact_idx'
                );

                $table->dropColumn(
                    'gospel_contact_id'
                );
            }
        );
    }
};
