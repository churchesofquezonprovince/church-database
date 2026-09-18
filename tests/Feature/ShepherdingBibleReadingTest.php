<?php

namespace Tests\Feature;

use App\Models\ShepherdingContact;
use App\Models\ShepherdingContactBibleReading;
use App\Support\RecoveryVersionBible;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class ShepherdingBibleReadingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Phase 29M regression tests exercise only the
         * Shepherding Bible Reading model/support layer.
         *
         * Historical application migrations contain
         * MySQL-specific SQL, so do not use
         * RefreshDatabase here. Build only the required
         * post-29M schema inside the guarded SQLite
         * :memory: testing database.
         */
        DB::statement('PRAGMA foreign_keys = ON');

        Schema::create(
            'shepherding_contacts',
            function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            }
        );

        Schema::create(
            'shepherding_contact_bible_readings',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'shepherding_contact_id'
                );

                $table->string('book_code', 10);
                $table->string('book_name', 100);

                $table->unsignedSmallInteger(
                    'chapter_start'
                );

                $table->unsignedSmallInteger(
                    'verse_start'
                )->nullable();

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
                    'shepherding_contact_id'
                )
                    ->references('id')
                    ->on('shepherding_contacts')
                    ->cascadeOnDelete();
            }
        );
    }

    public function test_common_bible_references_are_normalized(): void
    {
        $cases = [
            'John 3:16-21' => [
                'code' => 'Joh',
                'name' => 'John',
                'chapter_start' => 3,
                'verse_start' => 16,
                'chapter_end' => null,
                'verse_end' => 21,
                'label' => 'John 3:16–21',
                'path' => '/Joh.htm#v3_16',
            ],

            'Jn 3:16-21' => [
                'code' => 'Joh',
                'name' => 'John',
                'chapter_start' => 3,
                'verse_start' => 16,
                'chapter_end' => null,
                'verse_end' => 21,
                'label' => 'John 3:16–21',
                'path' => '/Joh.htm#v3_16',
            ],

            'John 3:36-4:3' => [
                'code' => 'Joh',
                'name' => 'John',
                'chapter_start' => 3,
                'verse_start' => 36,
                'chapter_end' => 4,
                'verse_end' => 3,
                'label' => 'John 3:36–4:3',
                'path' => '/Joh.htm#v3_36',
            ],

            'Psalms 23' => [
                'code' => 'Psa',
                'name' => 'Psalms',
                'chapter_start' => 23,
                'verse_start' => null,
                'chapter_end' => null,
                'verse_end' => null,
                'label' => 'Psalms 23',
                'path' => '/Psa.htm#v23',
            ],

            'Song of Songs 1:2-4' => [
                'code' => 'SoS',
                'name' => 'Song of Songs',
                'chapter_start' => 1,
                'verse_start' => 2,
                'chapter_end' => null,
                'verse_end' => 4,
                'label' => 'Song of Songs 1:2–4',
                'path' => '/SoS.htm#v1_2',
            ],
        ];

        foreach ($cases as $reference => $expected) {
            $parsed =
                RecoveryVersionBible::parse(
                    $reference
                );

            $this->assertSame(
                $expected['code'],
                $parsed['book_code'],
                $reference
            );

            $this->assertSame(
                $expected['name'],
                $parsed['book_name'],
                $reference
            );

            $this->assertSame(
                $expected['chapter_start'],
                $parsed['chapter_start'],
                $reference
            );

            $this->assertSame(
                $expected['verse_start'],
                $parsed['verse_start'],
                $reference
            );

            $this->assertSame(
                $expected['chapter_end'],
                $parsed['chapter_end'],
                $reference
            );

            $this->assertSame(
                $expected['verse_end'],
                $parsed['verse_end'],
                $reference
            );

            $this->assertSame(
                $expected['label'],
                RecoveryVersionBible::formatReference(
                    bookName:
                        $parsed['book_name'],
                    chapterStart:
                        $parsed['chapter_start'],
                    verseStart:
                        $parsed['verse_start'],
                    chapterEnd:
                        $parsed['chapter_end'],
                    verseEnd:
                        $parsed['verse_end'],
                ),
                $reference
            );

            $this->assertSame(
                $expected['path'],
                RecoveryVersionBible::pathFor(
                    bookCode:
                        $parsed['book_code'],
                    chapter:
                        $parsed['chapter_start'],
                    verse:
                        $parsed['verse_start'],
                ),
                $reference
            );
        }
    }

    public function test_recovery_version_filename_codes_are_preserved(): void
    {
        $cases = [
            'Proverbs 3:5' => 'Prv',
            'Song of Songs 1:2' => 'SoS',
            'Ezekiel 1:1' => 'Ezk',
            'Mark 1:1' => 'Mrk',
        ];

        foreach ($cases as $reference => $code) {
            $this->assertSame(
                $code,
                RecoveryVersionBible::parse(
                    $reference
                )['book_code']
            );
        }
    }

    public function test_bible_readings_are_ordered_and_build_public_urls(): void
    {
        config()->set(
            'bible.recovery_version_url',
            'https://rvbible.overcomers.win'
        );

        $contact =
            ShepherdingContact::query()
                ->create();

        $contact
            ->bibleReadings()
            ->create([
                'book_code' => 'Mrk',
                'book_name' => 'Mark',
                'chapter_start' => 1,
                'verse_start' => 1,
                'verse_end' => 8,
                'sort_order' => 2,
            ]);

        $contact
            ->bibleReadings()
            ->create([
                'book_code' => 'Joh',
                'book_name' => 'John',
                'chapter_start' => 3,
                'verse_start' => 16,
                'verse_end' => 21,
                'sort_order' => 1,
            ]);

        $readings =
            $contact
                ->fresh()
                ->bibleReadings;

        $this->assertSame(
            [
                'John 3:16–21',
                'Mark 1:1–8',
            ],
            $readings
                ->map(
                    fn ($reading) =>
                        $reading
                            ->referenceLabel()
                )
                ->all()
        );

        $this->assertSame(
            'https://rvbible.overcomers.win/Joh.htm#v3_16',
            $readings
                ->first()
                ->recoveryVersionUrl()
        );
    }

    public function test_existing_readings_can_be_replaced_without_stale_rows(): void
    {
        $contact =
            ShepherdingContact::query()
                ->create();

        $contact
            ->bibleReadings()
            ->create([
                'book_code' => 'Joh',
                'book_name' => 'John',
                'chapter_start' => 1,
                'verse_start' => null,
                'sort_order' => 1,
            ]);

        $contact
            ->bibleReadings()
            ->create([
                'book_code' => 'Gen',
                'book_name' => 'Genesis',
                'chapter_start' => 3,
                'verse_start' => 1,
                'sort_order' => 2,
            ]);

        $contact
            ->bibleReadings()
            ->delete();

        $contact
            ->bibleReadings()
            ->createMany([
                [
                    'book_code' => 'Joh',
                    'book_name' => 'John',
                    'chapter_start' => 3,
                    'verse_start' => 16,
                    'verse_end' => 21,
                    'sort_order' => 1,
                ],
                [
                    'book_code' => 'Mrk',
                    'book_name' => 'Mark',
                    'chapter_start' => 1,
                    'verse_start' => 1,
                    'verse_end' => 8,
                    'sort_order' => 2,
                ],
            ]);

        $readings =
            $contact
                ->fresh()
                ->bibleReadings;

        $this->assertCount(
            2,
            $readings
        );

        $this->assertSame(
            [
                'John 3:16–21',
                'Mark 1:1–8',
            ],
            $readings
                ->map(
                    fn ($reading) =>
                        $reading
                            ->referenceLabel()
                )
                ->all()
        );

        $this->assertFalse(
            ShepherdingContactBibleReading::query()
                ->where('book_code', 'Gen')
                ->exists()
        );
    }

    public function test_deleting_contact_cascades_bible_readings(): void
    {
        $contact =
            ShepherdingContact::query()
                ->create();

        $reading =
            $contact
                ->bibleReadings()
                ->create([
                    'book_code' => 'Joh',
                    'book_name' => 'John',
                    'chapter_start' => 3,
                    'verse_start' => 16,
                    'sort_order' => 1,
                ]);

        $readingId =
            (int) $reading->id;

        $contact->delete();

        $this->assertFalse(
            ShepherdingContactBibleReading::query()
                ->whereKey($readingId)
                ->exists()
        );
    }

    public function test_invalid_reference_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        RecoveryVersionBible::parse(
            'John 0:1'
        );
    }
}
