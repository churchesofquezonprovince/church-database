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
            'hymn_sources',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('hymn_id')
                    ->constrained('hymns')
                    ->cascadeOnDelete();

                $table->string(
                    'provider',
                    50
                )->index();

                $table->string(
                    'source_type',
                    30
                )
                    ->default('reference')
                    ->index();

                $table->string(
                    'external_id',
                    191
                )->nullable();

                $table->string(
                    'source_url',
                    2048
                )->nullable();

                $table->string(
                    'label',
                    150
                )->nullable();

                $table->json(
                    'metadata'
                )->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'hymn_id',
                        'provider',
                    ],
                    'hymn_sources_hymn_provider_idx'
                );

                $table->index(
                    [
                        'provider',
                        'source_type',
                    ],
                    'hymn_sources_provider_type_idx'
                );

                $table->unique(
                    [
                        'hymn_id',
                        'provider',
                        'external_id',
                    ],
                    'hymn_sources_hymn_provider_external_unique'
                );
            }
        );

        $now = now();

        DB::table('hymns')
            ->select([
                'id',
                'source',
                'source_id',
                'source_url',
            ])
            ->where(
                function ($query): void {
                    $query
                        ->whereNotNull(
                            'source_url'
                        )
                        ->orWhere(
                            'source',
                            'songbase'
                        );
                }
            )
            ->orderBy('id')
            ->chunkById(
                500,
                function ($hymns) use (
                    $now
                ): void {
                    $rows = [];

                    foreach ($hymns as $hymn) {
                        $provider =
                            $this->providerFor(
                                $hymn->source,
                                $hymn->source_url
                            );

                        $rows[] = [
                            'hymn_id' =>
                                $hymn->id,

                            'provider' =>
                                $provider,

                            'source_type' =>
                                $this->sourceTypeFor(
                                    $provider
                                ),

                            'external_id' =>
                                $hymn->source === 'songbase'
                                    ? $hymn->source_id
                                    : null,

                            'source_url' =>
                                $hymn->source_url,

                            'label' =>
                                $this->labelFor(
                                    $provider
                                ),

                            'metadata' =>
                                json_encode(
                                    [
                                        'legacy_source' =>
                                            $hymn->source,

                                        'legacy_source_id' =>
                                            $hymn->source_id,
                                    ],
                                    JSON_THROW_ON_ERROR
                                ),

                            'created_at' =>
                                $now,

                            'updated_at' =>
                                $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table(
                            'hymn_sources'
                        )->insert($rows);
                    }
                }
            );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'hymn_sources'
        );
    }

    private function providerFor(
        ?string $legacySource,
        ?string $url
    ): string {
        if ($legacySource === 'songbase') {
            return 'songbase';
        }

        $host =
            strtolower(
                (string) parse_url(
                    (string) $url,
                    PHP_URL_HOST
                )
            );

        $host =
            preg_replace(
                '/^www\./',
                '',
                $host
            ) ?? $host;

        if (
            $host === 'soundcloud.com'
            || str_ends_with(
                $host,
                '.soundcloud.com'
            )
        ) {
            return 'soundcloud';
        }

        if (
            $host === 'youtube.com'
            || str_ends_with(
                $host,
                '.youtube.com'
            )
            || $host === 'youtu.be'
        ) {
            return 'youtube';
        }

        if (
            $host === 'hymnal.net'
            || str_ends_with(
                $host,
                '.hymnal.net'
            )
        ) {
            return 'hymnal_net';
        }

        return 'other';
    }

    private function sourceTypeFor(
        string $provider
    ): string {
        return match ($provider) {
            'songbase',
            'hymnal_net' =>
                'catalog',

            'soundcloud' =>
                'audio',

            'youtube' =>
                'video',

            default =>
                'reference',
        };
    }

    private function labelFor(
        string $provider
    ): string {
        return match ($provider) {
            'songbase' =>
                'Songbase',

            'soundcloud' =>
                'SoundCloud',

            'youtube' =>
                'YouTube',

            'hymnal_net' =>
                'Hymnal.net',

            default =>
                'Other Source',
        };
    }
};
