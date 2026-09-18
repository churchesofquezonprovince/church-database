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
                /*
                 * Exact Morning Revival reading recorded
                 * for this historical Shepherding Contact.
                 *
                 * The Week identity is stored explicitly
                 * so later schedule edits cannot silently
                 * change old Shepherding records.
                 */
                $table->unsignedBigInteger(
                    'morning_revival_week_id'
                )
                    ->nullable()
                    ->after('outcome');

                $table->unsignedTinyInteger(
                    'morning_revival_day'
                )
                    ->nullable()
                    ->after(
                        'morning_revival_week_id'
                    );

                $table->foreign(
                    'morning_revival_week_id',
                    'sch_contact_mr_week_fk'
                )
                    ->references('id')
                    ->on('morning_revival_weeks')
                    ->restrictOnDelete();

                $table->index(
                    [
                        'morning_revival_week_id',
                        'morning_revival_day',
                    ],
                    'sch_contact_mr_reading_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'sch_contact_mr_week_fk'
                );

                $table->dropIndex(
                    'sch_contact_mr_reading_idx'
                );

                $table->dropColumn([
                    'morning_revival_week_id',
                    'morning_revival_day',
                ]);
            }
        );
    }
};
