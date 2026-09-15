<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'hymnal_net_entries',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string(
                        'collection_code',
                        32
                    )
                    ->index();

                $table->string(
                    'number',
                    64
                );

                $table
                    ->string('title')
                    ->nullable();

                $table->text(
                    'source_url'
                );

                $table
                    ->string(
                        'fetch_status',
                        32
                    )
                    ->default('pending')
                    ->index();

                $table
                    ->unsignedSmallInteger(
                        'http_status'
                    )
                    ->nullable();

                $table
                    ->string(
                        'validation_status',
                        32
                    )
                    ->default('pending')
                    ->index();

                $table
                    ->text(
                        'validation_note'
                    )
                    ->nullable();

                $table
                    ->foreignId(
                        'matched_hymn_id'
                    )
                    ->nullable()
                    ->constrained('hymns')
                    ->nullOnDelete();

                $table
                    ->string(
                        'match_status',
                        32
                    )
                    ->default('pending')
                    ->index();

                $table
                    ->string(
                        'match_method',
                        64
                    )
                    ->nullable();

                $table
                    ->unsignedTinyInteger(
                        'match_score'
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'last_fetched_at'
                    )
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'collection_code',
                        'number',
                    ],
                    'hymnal_net_collection_number_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'hymnal_net_entries'
        );
    }
};
