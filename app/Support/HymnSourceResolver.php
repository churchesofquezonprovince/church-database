<?php

namespace App\Support;

use App\Models\HymnSource;

class HymnSourceResolver
{
    public static function providerForUrl(
        ?string $url
    ): string {
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
            return HymnSource::PROVIDER_SOUNDCLOUD;
        }

        if (
            $host === 'youtube.com'
            || str_ends_with(
                $host,
                '.youtube.com'
            )
            || $host === 'youtu.be'
        ) {
            return HymnSource::PROVIDER_YOUTUBE;
        }

        if (
            $host === 'hymnal.net'
            || str_ends_with(
                $host,
                '.hymnal.net'
            )
        ) {
            return HymnSource::PROVIDER_HYMNAL_NET;
        }

        return HymnSource::PROVIDER_OTHER;
    }

    public static function sourceTypeForProvider(
        string $provider
    ): string {
        return match ($provider) {
            HymnSource::PROVIDER_SONGBASE,
            HymnSource::PROVIDER_HYMNAL_NET =>
                HymnSource::TYPE_CATALOG,

            HymnSource::PROVIDER_SOUNDCLOUD =>
                HymnSource::TYPE_AUDIO,

            HymnSource::PROVIDER_YOUTUBE =>
                HymnSource::TYPE_VIDEO,

            default =>
                HymnSource::TYPE_REFERENCE,
        };
    }

    public static function labelForProvider(
        string $provider
    ): string {
        return match ($provider) {
            HymnSource::PROVIDER_SONGBASE =>
                'Songbase',

            HymnSource::PROVIDER_SOUNDCLOUD =>
                'SoundCloud',

            HymnSource::PROVIDER_YOUTUBE =>
                'YouTube',

            HymnSource::PROVIDER_HYMNAL_NET =>
                'Hymnal.net',

            default =>
                'Other Source',
        };
    }
}
