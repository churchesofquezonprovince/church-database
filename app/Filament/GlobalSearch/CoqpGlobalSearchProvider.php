<?php

namespace App\Filament\GlobalSearch;

use App\Models\NavigationSearchAlias;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Collection;

class CoqpGlobalSearchProvider implements GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        /*
         * Preserve Filament's existing global search.
         *
         * This keeps People search, and any other globally
         * searchable Resources added in the future.
         */
        $results = app(DefaultGlobalSearchProvider::class)
            ->getResults($query)
            ?? GlobalSearchResults::make();

        $navigationResults = $this->getNavigationResults($query);

        if ($navigationResults->isNotEmpty()) {
            $results->category(
                'Navigation',
                $navigationResults,
            );
        }

        return $results;
    }

    protected function getNavigationResults(
        string $query
    ): Collection {
        $query = $this->normalize($query);

        if ($query === '') {
            return collect();
        }

        /*
         * Reuse the same database aliases as the sidebar
         * Navigation Search.
         */
        $aliases = NavigationSearchAlias::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'phrase',
                'target_type',
                'target_group',
                'target_label',
            ]);

        $matchingAliases = $aliases->filter(
            function (
                NavigationSearchAlias $alias
            ) use ($query): bool {
                $phrase = $this->normalize(
                    $alias->phrase
                );

                if ($phrase === '') {
                    return false;
                }

                return str_contains($phrase, $query)
                    || str_contains($query, $phrase);
            }
        );

        $aliasGroupTargets = $matchingAliases
            ->where('target_type', 'group')
            ->map(
                fn (
                    NavigationSearchAlias $alias
                ): string => $this->normalize(
                    $alias->target_label
                )
            )
            ->filter()
            ->values();

        $aliasItemTargets = $matchingAliases
            ->where('target_type', 'item')
            ->values();

        $matches = collect();

        /*
         * Search the actual navigation tree from the current
         * Filament panel so result URLs stay in sync with
         * the sidebar.
         */
        foreach (Filament::getNavigation() as $group) {
            $groupLabel = trim(
                (string) ($group->getLabel() ?? '')
            );

            $normalizedGroup = $this->normalize(
                $groupLabel
            );

            $groupMatches =
                (
                    $normalizedGroup !== ''
                    && str_contains(
                        $normalizedGroup,
                        $query
                    )
                )
                || $aliasGroupTargets->contains(
                    $normalizedGroup
                );

            foreach (
                collect($group->getItems())
                as $item
            ) {
                if (! $item instanceof NavigationItem) {
                    continue;
                }

                $this->collectNavigationMatches(
                    matches: $matches,
                    item: $item,
                    groupLabel: $groupLabel,
                    normalizedGroup: $normalizedGroup,
                    query: $query,
                    groupMatches: $groupMatches,
                    aliasItemTargets: $aliasItemTargets,
                );
            }
        }

        return $matches
            ->unique(
                fn (
                    GlobalSearchResult $result
                ): string => $result->url
                    . '|'
                    . (string) $result->title
            )
            ->take(15)
            ->values();
    }

    protected function collectNavigationMatches(
        Collection $matches,
        NavigationItem $item,
        string $groupLabel,
        string $normalizedGroup,
        string $query,
        bool $groupMatches,
        Collection $aliasItemTargets,
        ?string $parentLabel = null,
    ): void {
        if (! $item->isVisible()) {
            return;
        }

        $label = trim($item->getLabel());

        $normalizedLabel = $this->normalize(
            $label
        );

        $aliasMatches = $aliasItemTargets->contains(
            function (
                NavigationSearchAlias $alias
            ) use (
                $normalizedLabel,
                $normalizedGroup
            ): bool {
                $targetLabel = $this->normalize(
                    $alias->target_label
                );

                $targetGroup = $this->normalize(
                    $alias->target_group
                );

                return $targetLabel === $normalizedLabel
                    && (
                        $targetGroup === ''
                        || $targetGroup
                            === $normalizedGroup
                    );
            }
        );

        $itemMatches =
            $groupMatches
            || str_contains(
                $normalizedLabel,
                $query
            )
            || $aliasMatches;

        $url = $item->getUrl();

        if ($itemMatches && filled($url)) {
            $details = [];

            if ($groupLabel !== '') {
                $details['Group'] = $groupLabel;
            }

            if (filled($parentLabel)) {
                $details['Parent'] = $parentLabel;
            }

            $matches->push(
                new GlobalSearchResult(
                    title: $label,
                    url: $url,
                    details: $details,
                )
            );
        }

        foreach (
            collect($item->getChildItems())
            as $childItem
        ) {
            if (! $childItem instanceof NavigationItem) {
                continue;
            }

            $this->collectNavigationMatches(
                matches: $matches,
                item: $childItem,
                groupLabel: $groupLabel,
                normalizedGroup: $normalizedGroup,
                query: $query,
                groupMatches: $groupMatches,
                aliasItemTargets: $aliasItemTargets,
                parentLabel: $label,
            );
        }
    }

    protected function normalize(
        mixed $value
    ): string {
        return mb_strtolower(
            trim((string) ($value ?? ''))
        );
    }
}
