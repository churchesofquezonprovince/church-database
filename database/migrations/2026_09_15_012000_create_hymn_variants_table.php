<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'hymn_variants',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('hymn_id')
                    ->constrained('hymns')
                    ->cascadeOnDelete();

                $table
                    ->string('source', 64)
                    ->nullable()
                    ->index();

                $table
                    ->string('source_id', 191)
                    ->nullable()
                    ->index();

                $table
                    ->string('variant_type', 32)
                    ->default('tune')
                    ->index();

                $table
                    ->unsignedSmallInteger(
                        'variant_index'
                    )
                    ->nullable();

                $table->string('label');

                $table
                    ->string('title_override')
                    ->nullable();

                $table
                    ->longText('lyrics')
                    ->nullable();

                $table
                    ->longText('lyrics_search')
                    ->nullable();

                $table
                    ->json('metadata')
                    ->nullable();

                $table
                    ->unsignedSmallInteger('sort_order')
                    ->default(0);

                $table
                    ->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->timestamps();

                $table->index([
                    'hymn_id',
                    'variant_type',
                    'is_active',
                ]);

                $table->unique(
                    [
                        'hymn_id',
                        'source',
                        'source_id',
                        'variant_type',
                        'variant_index',
                    ],
                    'hymn_variant_source_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'hymn_variants'
        );
    }
};
