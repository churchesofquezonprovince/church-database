<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $series = [
            [
                'code' => 'HG',
                'title' => 'The High Gospel',
                'description' =>
                    'Shepherding Materials Series 1',
                'sort_order' => 30,
                'topics' => [
                    'The Mystery of Human Life',
                    'Christ is the Meaning of Human Life',
                    'The Church Life is the Real Communal Life',
                    'The Bible',
                    'There is God',
                    'Christ is God',
                    'Christ is Sprit',
                    'Christ is Life',
                    'The Redemption of Christ',
                    'The Salvation of Christ',
                    'Life trough Faith',
                    'The Loving Father',
                    'Jesus Being the Friend of Sinner',
                    'Repentance and Confession',
                    'Baptism',
                    'Facing Persecution',
                ],
            ],

            [
                'code' => 'AB',
                'title' => 'After Being Saved',
                'description' =>
                    'Shepherding Materials Series 2',
                'sort_order' => 40,
                'topics' => [
                    'Assurance of Salvation',
                    'Clearance of the Past',
                    'Morning Revival',
                    'The Mingled Spirit',
                    'Calling upon the Name of the Lord',
                    'Being Filled with the Spirit',
                    'The Word of Life',
                    'Pray-reading God’s Word',
                    'God’s Economy',
                    'Consecration',
                    'The Body of Christ',
                    'The Meeting Life',
                    'The Ministry of the New Covenant',
                    'God Ordained Way',
                    'The Judicial Redemption',
                    'The Organic Salvation',
                ],
            ],

            [
                'code' => 'TO',
                'title' => 'Trust and Obey',
                'description' =>
                    'Shepherding Materials Series 3',
                'sort_order' => 50,
                'topics' => [
                    'Telling Him',
                    'Casting Our Anxiety upon God',
                    'The End of Man Being the Beginning of God',
                    'Why Should Believers Suffer',
                    'God Using Environment to Work Together for Good to Believers',
                    'And Peter',
                    'Treasure in Earthen Vessels',
                    'He has Filled the Hungry with Good Things',
                    'Enjoying Christ',
                    'Withstanding the Devil',
                    'Fact, Faith, and Experience',
                    'Faith and Obedience',
                    'Appreciating the Lord Jesus',
                    'Not Loving the World',
                    'God’s Keeping Power',
                    'The Hope of the Christian Life',
                ],
            ],

            [
                'code' => 'SL',
                'title' => 'The Spirit and Life',
                'description' =>
                    'Shepherding Materials Series 4',
                'sort_order' => 70,
                'topics' => [
                    'Prayer',
                    'Reading the Bible',
                    'Spiritual Companions',
                    'Exercising the Spirit',
                    'Hymn Singing',
                    'Praising',
                    'The Sense of Life',
                    'The Fellowship of Life',
                    'Being One Spirit with the Lord',
                    'Walking according to the Spirit',
                    'Regeneration',
                    'Sanctification',
                    'Renewing',
                    'Transformation',
                    'Conformation',
                    'Glorification',
                ],
            ],

            [
                'code' => 'KT',
                'title' => 'Knowing the Truth',
                'description' =>
                    'Shepherding Materials Series 5',
                'sort_order' => 80,
                'topics' => [
                    'The Processed Triune God',
                    'The All-inclusive Christ',
                    'The Consummated Spirit',
                    'The Human Spirit',
                    'The Divine and Eternal Life',
                    'The Triune God as Life to Saturate the Tripartite Man',
                    'The Church',
                    'The Three Aspects of the Kingdom of the Heavens',
                    'The Coming again of Christ',
                    'The New Jerusalem',
                    'The Subjects of the Books of the Old Testament',
                    'The Subjects of the Books of the New Testament',
                    'The Recovery Version of the Bible',
                    'Knowing the Hymns',
                    'The Life-study',
                    'The Holy Word for Morning Revival',
                ],
            ],

            [
                'code' => 'CL',
                'title' => 'The Church Life',
                'description' =>
                    'Shepherding Materials Series 6',
                'sort_order' => 90,
                'topics' => [
                    'The Great Mystery Christ and The Church',
                    'The Two Aspects of the Church',
                    'The Lord’s Recovery',
                    'What are We?',
                    'Knowing the Sects',
                    'Serving the Lord',
                    'Living Uniquely for the Gospel',
                    'The Life Pulse in the Practice of the New Way—the Home',
                    'Shepherding the Lord’s Sheep',
                    'The Vital Group',
                    'The Group Meeting',
                    'The Lord’s Table Meeting',
                    'The Prophesying Meeting',
                    'The Offering of the Material Riches',
                    'The Blending of the Body of Christ',
                    'The Building Up of the Body of Christ',
                ],
            ],
        ];

        foreach ($series as $bookData) {
            $book =
                DB::table('ministry_books')
                    ->where(
                        'code',
                        $bookData['code']
                    )
                    ->first();

            if ($book === null) {
                $bookId =
                    DB::table('ministry_books')
                        ->insertGetId([
                            'code' =>
                                $bookData['code'],

                            'title' =>
                                $bookData['title'],

                            'short_title' =>
                                null,

                            'description' =>
                                $bookData[
                                    'description'
                                ],

                            'is_active' =>
                                true,

                            'sort_order' =>
                                $bookData[
                                    'sort_order'
                                ],

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);
            } else {
                $bookId =
                    (int) $book->id;

                DB::table('ministry_books')
                    ->where(
                        'id',
                        $bookId
                    )
                    ->update([
                        'title' =>
                            $bookData['title'],

                        'description' =>
                            $bookData[
                                'description'
                            ],

                        'is_active' =>
                            true,

                        'sort_order' =>
                            $bookData[
                                'sort_order'
                            ],

                        'updated_at' =>
                            now(),
                    ]);
            }

            foreach (
                $bookData['topics']
                as $index => $title
            ) {
                $number =
                    $index + 1;

                $lessonCode =
                    $bookData['code']
                    . str_pad(
                        (string) $number,
                        2,
                        '0',
                        STR_PAD_LEFT
                    );

                DB::table('ministry_lessons')
                    ->updateOrInsert(
                        [
                            'ministry_book_id' =>
                                $bookId,

                            'code' =>
                                $lessonCode,
                        ],
                        [
                            'title' =>
                                $title,

                            'description' =>
                                null,

                            'sort_order' =>
                                $number * 10,

                            'is_active' =>
                                true,

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]
                    );
            }
        }
    }

    public function down(): void
    {
        /*
         * Intentionally preserve the catalog.
         *
         * Ministry lessons may become referenced by
         * Shepherding Records after deployment. Removing
         * them during rollback could destroy meaningful
         * Shepherding history.
         */
    }
};
