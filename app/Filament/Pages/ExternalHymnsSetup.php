<?php

namespace App\Filament\Pages;

use App\Models\HymnAdditionRequest;
use App\Models\HymnSource;
use App\Support\HymnSourceResolver;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ExternalHymnsSetup extends Page
{
    protected string $view =
        'filament.pages.external-hymns-setup';

    protected static ?string $slug =
        'external-hymns-setup';

    /*
     * Dedicated search for linked external sources only.
     *
     * This does not search Songbase or Hymnal.net
     * provider records.
     */
    public string $externalSourceSearchInput = '';

    public string $externalSourceSearch = '';

    public ?int $editingExternalSourceId = null;

    public array $externalSourceForm = [
        'collection_name' => '',
        'track_number' => '',
        'source_url' => '',
    ];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );
    }

    public function getTitle(): string
    {
        return 'External Hymns Setup';
    }

    public static function getNavigationLabel(): string
    {
        return 'External Hymns Setup';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-play-circle';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin()
            ?? false;
    }

    public function summary(): array
    {
        return [
            'pending_external' =>
                HymnAdditionRequest::query()
                    ->where(
                        'status',
                        HymnAdditionRequest::STATUS_PENDING
                    )
                    ->whereNotNull(
                        'source_url'
                    )
                    ->where(
                        'source_url',
                        '!=',
                        ''
                    )
                    ->count(),

            'youtube' =>
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource::PROVIDER_YOUTUBE
                    )
                    ->count(),

            'soundcloud' =>
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource::PROVIDER_SOUNDCLOUD
                    )
                    ->count(),

            'other' =>
                HymnSource::query()
                    ->where(
                        'provider',
                        HymnSource::PROVIDER_OTHER
                    )
                    ->count(),
        ];
    }

    public function linkedExternalSources(): Collection
    {
        $search =
            trim(
                $this->externalSourceSearch
            );

        return HymnSource::query()
            ->with([
                'hymn',
            ])
            ->whereIn(
                'provider',
                [
                    HymnSource::PROVIDER_YOUTUBE,
                    HymnSource::PROVIDER_SOUNDCLOUD,
                    HymnSource::PROVIDER_OTHER,
                ]
            )
            ->when(
                $search !== '',
                function ($query) use (
                    $search
                ): void {
                    $like =
                        '%' . $search . '%';

                    $query->where(
                        function ($query) use (
                            $like
                        ): void {
                            $query
                                ->where(
                                    'provider',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'source_url',
                                    'like',
                                    $like
                                )
                                ->orWhereRaw(
                                    'CAST(hymn_sources.id AS CHAR) LIKE ?',
                                    [
                                        $like,
                                    ]
                                )
                                ->orWhereHas(
                                    'hymn',
                                    fn ($hymnQuery) =>
                                        $hymnQuery
                                            ->where(
                                                'title',
                                                'like',
                                                $like
                                            )
                                )
                                ->orWhereRaw(
                                    "JSON_UNQUOTE("
                                    . "JSON_EXTRACT("
                                    . "metadata, "
                                    . "'$.collection_name'"
                                    . ")) LIKE ?",
                                    [
                                        $like,
                                    ]
                                )
                                ->orWhereRaw(
                                    "JSON_UNQUOTE("
                                    . "JSON_EXTRACT("
                                    . "metadata, "
                                    . "'$.track_number'"
                                    . ")) LIKE ?",
                                    [
                                        $like,
                                    ]
                                );
                        }
                    );
                }
            )
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    public function searchExternalSources(): void
    {
        $this->externalSourceSearch =
            trim(
                $this->externalSourceSearchInput
            );

        $this->cancelExternalSourceEdit();
    }

    public function clearExternalSourceSearch(): void
    {
        $this->externalSourceSearchInput = '';
        $this->externalSourceSearch = '';

        $this->cancelExternalSourceEdit();
    }

    public function editExternalSource(
        int $sourceId
    ): void {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $source =
            HymnSource::query()
                ->whereIn(
                    'provider',
                    [
                        HymnSource::PROVIDER_YOUTUBE,
                        HymnSource::PROVIDER_SOUNDCLOUD,
                        HymnSource::PROVIDER_OTHER,
                    ]
                )
                ->find($sourceId);

        if (! $source) {
            Notification::make()
                ->title(
                    'External source not found'
                )
                ->warning()
                ->send();

            return;
        }

        $metadata =
            is_array($source->metadata)
                ? $source->metadata
                : [];

        $this->editingExternalSourceId =
            (int) $source->id;

        $this->externalSourceForm = [
            'collection_name' =>
                (string) (
                    $metadata[
                        'collection_name'
                    ]
                    ?? ''
                ),

            'track_number' =>
                (string) (
                    $metadata[
                        'track_number'
                    ]
                    ?? ''
                ),

            'source_url' =>
                (string) (
                    $source->source_url
                    ?? ''
                ),
        ];
    }

    public function cancelExternalSourceEdit(): void
    {
        $this->editingExternalSourceId =
            null;

        $this->externalSourceForm = [
            'collection_name' => '',
            'track_number' => '',
            'source_url' => '',
        ];
    }

    public function saveExternalSource(): void
    {
        abort_unless(
            auth()->user()?->isAdmin(),
            403
        );

        $sourceId =
            (int) (
                $this->editingExternalSourceId
                ?? 0
            );

        if ($sourceId <= 0) {
            return;
        }

        $this->validate([
            'externalSourceForm.collection_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'externalSourceForm.track_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'externalSourceForm.source_url' => [
                'required',
                'url',
                'max:2048',
            ],
        ]);

        $source =
            HymnSource::query()
                ->whereIn(
                    'provider',
                    [
                        HymnSource::PROVIDER_YOUTUBE,
                        HymnSource::PROVIDER_SOUNDCLOUD,
                        HymnSource::PROVIDER_OTHER,
                    ]
                )
                ->find($sourceId);

        if (! $source) {
            Notification::make()
                ->title(
                    'External source not found'
                )
                ->warning()
                ->send();

            return;
        }

        $url =
            trim(
                (string)
                $this->externalSourceForm[
                    'source_url'
                ]
            );

        $provider =
            HymnSourceResolver::providerForUrl(
                $url
            );

        if (
            ! in_array(
                $provider,
                [
                    HymnSource::PROVIDER_YOUTUBE,
                    HymnSource::PROVIDER_SOUNDCLOUD,
                    HymnSource::PROVIDER_OTHER,
                ],
                true
            )
        ) {
            Notification::make()
                ->title(
                    'Use the matching provider setup'
                )
                ->body(
                    'Songbase and Hymnal.net links '
                    . 'are managed in their own setup pages.'
                )
                ->warning()
                ->send();

            return;
        }

        $collection =
            trim(
                (string) (
                    $this->externalSourceForm[
                        'collection_name'
                    ]
                    ?? ''
                )
            );

        $trackNumber =
            trim(
                (string) (
                    $this->externalSourceForm[
                        'track_number'
                    ]
                    ?? ''
                )
            );

        $metadata =
            is_array($source->metadata)
                ? $source->metadata
                : [];

        if ($collection !== '') {
            $metadata[
                'collection_name'
            ] = $collection;
        } else {
            unset(
                $metadata[
                    'collection_name'
                ]
            );
        }

        if ($trackNumber !== '') {
            $metadata[
                'track_number'
            ] = $trackNumber;
        } else {
            unset(
                $metadata[
                    'track_number'
                ]
            );
        }

        $source->forceFill([
            'source_url' =>
                $url,

            'provider' =>
                $provider,

            'source_type' =>
                HymnSourceResolver
                    ::sourceTypeForProvider(
                        $provider
                    ),

            'label' =>
                HymnSourceResolver
                    ::labelForProvider(
                        $provider
                    ),

            'metadata' =>
                $metadata,
        ])->save();

        $this->cancelExternalSourceEdit();

        Notification::make()
            ->title(
                'External source updated'
            )
            ->success()
            ->send();
    }

    public function sourceLabel(
        HymnSource $source
    ): string {
        return HymnSourceResolver
            ::labelForProvider(
                $source->provider
            );
    }
}
