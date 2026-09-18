<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shepherding_contact_bible_readings',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                /*
                 * Recovery Version static-file code:
                 * Joh, Rom, 1Co, Rev, etc.
                 */
                $table->string('book_code', 10);

                /*
                 * Human-readable canonical book name:
                 * John, Romans, 1 Corinthians, etc.
                 */
                $table->string('book_name', 100);

                $table->unsignedSmallInteger(
                    'chapter_start'
                );

                $table->unsignedSmallInteger(
                    'verse_start'
                )->nullable();

                /*
                 * End chapter/verse are nullable.
                 *
                 * John 3
                 *   => no verse/end values
                 *
                 * John 3:16
                 *   => verse_start only
                 *
                 * John 3:16-21
                 *   => verse_start + verse_end
                 *
                 * John 3:36-4:3
                 *   => chapter_end + verse_end
                 */
                $table->unsignedSmallInteger(
                    'chapter_end'
                )->nullable();

                $table->unsignedSmallInteger(
                    'verse_end'
                )->nullable();

                $table->unsignedSmallInteger(
                    'sort_order'
                )->default(1);

                $table->timestamps();

                $table->foreign(
                    'shepherding_contact_id',
                    'sc_bible_readings_contact_fk'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();

                $table->index(
                    [
                        'shepherding_contact_id',
                        'sort_order',
                    ],
                    'sc_bible_readings_order_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shepherding_contact_bible_readings'
        );
    }
};
