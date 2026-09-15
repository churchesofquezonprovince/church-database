<?php

namespace App\Services;

use App\Models\HymnalNetEntry;
use App\Support\HymnalNetCollectionCatalog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HymnalNetCatalogDiscoveryService
{
    public function discover(
        string $section,
        int $delayMs = 250
    ): array {
        $config =
            HymnalNetCollectionCatalog
                ::section(
                    $section
                );

        $section =
            $config['code'];

        $indexCode =
            $config['index_code'];

        $queue = [
            $config['index_url'],
        ];

        $visited = [];
        $songs = [];

        /*
         * Safety boundary.
         *
         * Discovery follows only Hymnal.net index pages
         * belonging to the selected logical section.
         */
        while ($queue !== []) {
            if (count($visited) >= 100) {
                throw new RuntimeException(
                    'Hymnal.net discovery stopped after '
                    . '100 index pages for safety.'
                );
            }

            $url =
                array_shift(
                    $queue
                );

            if (
                isset(
                    $visited[$url]
                )
            ) {
                continue;
            }

            $visited[$url] =
                true;

            $response =
                Http::accept(
                    'text/html'
                )
                    ->withHeaders([
                        'User-Agent' =>
                            'CoQP-HymnalNet-Discovery/1.0',
                    ])
                    ->timeout(30)
                    ->retry(
                        2,
                        750
                    )
                    ->get(
                        $url
                    );

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Hymnal.net index request failed: '
                    . $url
                    . ' (HTTP '
                    . $response->status()
                    . ')'
                );
            }

            foreach (
                $this->extractHrefs(
                    $response->body()
                )
                as $href
            ) {
                $absolute =
                    $this->absoluteHymnalNetUrl(
                        $href
                    );

                if (! $absolute) {
                    continue;
                }

                $path =
                    parse_url(
                        $absolute,
                        PHP_URL_PATH
                    );

                if (! is_string($path)) {
                    continue;
                }

                $indexPrefix =
                    '/en/song-index/'
                    . $indexCode;

                /*
                 * Follow A/B/C/... or other index pages
                 * advertised by Hymnal.net itself.
                 */
                if (
                    $path === $indexPrefix
                    || $path === $indexPrefix . '/'
                    || str_starts_with(
                        $path,
                        $indexPrefix . '/'
                    )
                ) {
                    if (
                        ! isset(
                            $visited[$absolute]
                        )
                        && ! in_array(
                            $absolute,
                            $queue,
                            true
                        )
                    ) {
                        $queue[] =
                            $absolute;
                    }

                    continue;
                }

                /*
                 * Physical hymn route.
                 *
                 * This deliberately accepts routes not
                 * hard-coded in the collection registry.
                 */
                if (
                    ! preg_match(
                        '#^/en/hymn/'
                        . '([A-Za-z0-9_-]+)'
                        . '/'
                        . '([A-Za-z0-9._-]+)'
                        . '/?$#',
                        $path,
                        $matches
                    )
                ) {
                    continue;
                }

                $route =
                    strtolower(
                        $matches[1]
                    );

                $number =
                    $matches[2];

                $key =
                    $route
                    . ':'
                    . $number;

                $songs[$key] = [
                    'route' =>
                        $route,

                    'number' =>
                        $number,

                    'url' =>
                        HymnalNetCollectionCatalog
                            ::url(
                                $route,
                                $number
                            ),
                ];
            }

            if (
                $delayMs > 0
                && $queue !== []
            ) {
                usleep(
                    $delayMs * 1000
                );
            }
        }

        $created = 0;
        $existing = 0;

        foreach (
            $songs
            as $song
        ) {
            $entry =
                HymnalNetEntry::query()
                    ->firstOrNew([
                        'collection_code' =>
                            $song['route'],

                        'number' =>
                            $song['number'],
                    ]);

            if ($entry->exists) {
                $existing++;
            } else {
                $created++;
            }

            $entry->section_code =
                $section;

            $entry->source_url =
                $song['url'];

            $entry->save();
        }

        $routes =
            collect($songs)
                ->pluck('route')
                ->unique()
                ->sort()
                ->values()
                ->all();

        return [
            'section' =>
                $section,

            'label' =>
                $config['label'],

            'index_pages' =>
                count($visited),

            'discovered' =>
                count($songs),

            'created' =>
                $created,

            'existing' =>
                $existing,

            'routes' =>
                $routes,
        ];
    }

    private function extractHrefs(
        string $html
    ): array {
        preg_match_all(
            '/href\s*=\s*'
            . '(["\'])'
            . '(.*?)'
            . '\1/is',
            $html,
            $matches
        );

        return collect(
            $matches[2]
            ?? []
        )
            ->map(
                fn ($href) =>
                    html_entity_decode(
                        trim($href),
                        ENT_QUOTES
                        | ENT_HTML5,
                        'UTF-8'
                    )
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function absoluteHymnalNetUrl(
        string $href
    ): ?string {
        if (
            str_starts_with(
                $href,
                '//'
            )
        ) {
            $href =
                'https:'
                . $href;
        } elseif (
            str_starts_with(
                $href,
                '/'
            )
        ) {
            $href =
                'https://www.hymnal.net'
                . $href;
        } elseif (
            ! preg_match(
                '#^https?://#i',
                $href
            )
        ) {
            return null;
        }

        $host =
            strtolower(
                (string)
                    parse_url(
                        $href,
                        PHP_URL_HOST
                    )
            );

        if (
            ! in_array(
                $host,
                [
                    'hymnal.net',
                    'www.hymnal.net',
                ],
                true
            )
        ) {
            return null;
        }

        $path =
            parse_url(
                $href,
                PHP_URL_PATH
            );

        if (! is_string($path)) {
            return null;
        }

        return
            'https://www.hymnal.net'
            . $path;
    }
}
