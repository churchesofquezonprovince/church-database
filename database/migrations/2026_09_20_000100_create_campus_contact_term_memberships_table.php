<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'campus_contact_term_memberships',
            function (Blueprint $table): void {
                $table->increments('id');

                $table
                    ->unsignedInteger('campus_work_term_id');

                $table
                    ->unsignedInteger('campus_contact_id');

                $table->timestamps();

                $table
                    ->foreign(
                        'campus_work_term_id',
                        'cctm_term_fk'
                    )
                    ->references('id')
                    ->on('campus_work_terms')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'campus_contact_id',
                        'cctm_contact_fk'
                    )
                    ->references('id')
                    ->on('campus_contacts')
                    ->cascadeOnDelete();

                /*
                 * One Campus Contact may occur once in each
                 * Academic Term.
                 */
                $table->unique(
                    [
                        'campus_work_term_id',
                        'campus_contact_id',
                    ],
                    'cctm_term_contact_unique'
                );

                $table->index(
                    'campus_contact_id',
                    'cctm_contact_index'
                );
            }
        );

        /*
         * Preserve every existing Campus Contact.
         *
         * Prefer the active Academic Term. If there is no
         * active term, use the latest non-archived term,
         * then finally the latest term of any status.
         */
        $termId =
            DB::table('campus_work_terms')
                ->where('is_active', true)
                ->where('is_archived', false)
                ->orderByDesc('id')
                ->value('id')
            ?? DB::table('campus_work_terms')
                ->where('is_archived', false)
                ->orderByDesc('id')
                ->value('id')
            ?? DB::table('campus_work_terms')
                ->orderByDesc('id')
                ->value('id');

        if (! $termId) {
            throw new RuntimeException(
                'Cannot assign existing Campus Contacts: '
                . 'no Campus Work Academic Term exists.'
            );
        }

        $now = now();

        DB::table('campus_contacts')
            ->orderBy('id')
            ->pluck('id')
            ->chunk(500)
            ->each(
                function ($contactIds) use (
                    $termId,
                    $now
                ): void {
                    $rows =
                        $contactIds
                            ->map(
                                fn ($contactId): array => [
                                    'campus_work_term_id' =>
                                        $termId,

                                    'campus_contact_id' =>
                                        $contactId,

                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]
                            )
                            ->all();

                    if ($rows !== []) {
                        DB::table(
                            'campus_contact_term_memberships'
                        )->insert($rows);
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'campus_contact_term_memberships'
        );
    }
};
