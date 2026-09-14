<?php

namespace App\Support;

use InvalidArgumentException;

class HymnalNetSource
{
    public static function normalizeUrl(
        string $url
    ): string {
        $url = trim($url);

        if ($url === '') {
            throw new InvalidArgumentException(
                'Enter a Hymnal.net URL.'
            );
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw new InvalidArgumentException(
                'The Hymnal.net URL is invalid.'
            );
        }

        $scheme =
            strtolower(
                (string) ($parts['scheme'] ?? '')
            );

        if (
            $scheme !== 'http'
            && $scheme !== 'https'
        ) {
            throw new InvalidArgumentException(
                'The Hymnal.net URL must use '
                . 'http:// or https://.'
            );
        }

        $host =
            strtolower(
                (string) ($parts['host'] ?? '')
            );

        $hostWithoutWww =
            preg_replace(
                '/^www\./',
                '',
                $host
            ) ?? $host;

        if (
            $hostWithoutWww !== 'hymnal.net'
            && ! str_ends_with(
                $hostWithoutWww,
                '.hymnal.net'
            )
        ) {
            throw new InvalidArgumentException(
                'Only Hymnal.net URLs are allowed.'
            );
        }

        $path =
            '/' . ltrim(
                (string) ($parts['path'] ?? ''),
                '/'
            );

        $path =
            preg_replace(
                '#/+#',
                '/',
                $path
            ) ?? $path;

        $path = rtrim($path, '/');

        if (
            $path === ''
            || $path === '/'
            || ! str_contains(
                strtolower($path),
                '/hymn/'
            )
        ) {
            throw new InvalidArgumentException(
                'Use a direct Hymnal.net hymn page URL.'
            );
        }

        return
            'https://www.hymnal.net'
            . $path;
    }

    public static function externalIdForUrl(
        string $url
    ): string {
        $normalized =
            self::normalizeUrl($url);

        $path =
            (string) parse_url(
                $normalized,
                PHP_URL_PATH
            );

        return trim($path, '/');
    }
}
