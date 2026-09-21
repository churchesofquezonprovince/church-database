@php
    $navigationSearchAliases =
        \App\Models\NavigationSearchAlias::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'phrase',
                'target_type',
                'target_group',
                'target_label',
            ])
            ->map(fn ($alias): array => [
                'phrase' => $alias->phrase,
                'target_type' => $alias->target_type,
                'target_group' => $alias->target_group,
                'target_label' => $alias->target_label,
            ])
            ->values()
            ->all();
@endphp

<div
    x-data="{
        query: '',
        aliases: [],

        normalize(value) {
            return String(value ?? '')
                .toLocaleLowerCase()
                .trim();
        },

        sidebar() {
            return this.$el.closest('.fi-sidebar-nav')
                ?? document.querySelector('.fi-sidebar-nav');
        },

        applySearch() {
            const sidebar = this.sidebar();

            if (! sidebar) {
                return;
            }

            if (! this.$store.sidebar.isOpen) {
                this.query = '';
            }

            const query = this.normalize(this.query);

            /*
             * Database aliases are additive to the normal
             * navigation-label search.
             *
             * Examples:
             * ltm   -> Check Attendance
             * gow   -> whole Shepherding group
             * setup -> Ministry Books + Users
             */
            const matchingAliases =
                query === ''
                    ? []
                    : this.aliases.filter(
                        (alias) => {
                            const phrase =
                                this.normalize(
                                    alias.phrase
                                );

                            if (! phrase) {
                                return false;
                            }

                            return (
                                phrase.includes(query)
                                ||
                                query.includes(phrase)
                            );
                        }
                    );

            const aliasGroupTargets =
                new Set(
                    matchingAliases
                        .filter(
                            (alias) =>
                                alias.target_type
                                === 'group'
                        )
                        .map(
                            (alias) =>
                                this.normalize(
                                    alias.target_label
                                )
                        )
                );

            const aliasItemTargets =
                matchingAliases.filter(
                    (alias) =>
                        alias.target_type
                        === 'item'
                );

            if (query) {
                sidebar.setAttribute(
                    'data-navigation-filtering',
                    'true'
                );
            } else {
                sidebar.removeAttribute(
                    'data-navigation-filtering'
                );
            }

            const groups = Array.from(
                sidebar.querySelectorAll(
                    '.fi-sidebar-group'
                )
            );

            const groupedItems = new Set();

            groups.forEach((group) => {
                const groupLabelElement =
                    group.querySelector(
                        '.fi-sidebar-group-label'
                    );

                const groupLabel = this.normalize(
                    groupLabelElement?.textContent
                );

                /*
                 * If the search matches the group name,
                 * show every navigation item in that group.
                 *
                 * Example:
                 * Attendance
                 * -> show the entire Attendance group.
                 */
                const groupAliasMatches =
                    aliasGroupTargets.has(
                        groupLabel
                    );

                const groupMatches =
                    query !== ''
                    && (
                        groupLabel.includes(query)
                        || groupAliasMatches
                    );

                const items = Array.from(
                    group.querySelectorAll(
                        '.fi-sidebar-item'
                    )
                );

                let visibleItems = 0;

                items.forEach((item) => {
                    groupedItems.add(item);

                    const labelElement =
                        item.querySelector(
                            '.fi-sidebar-item-label'
                        );

                    const label = this.normalize(
                        labelElement?.textContent
                        ?? item.textContent
                    );

                    const aliasItemMatches =
                        aliasItemTargets.some(
                            (alias) => {
                                const targetLabel =
                                    this.normalize(
                                        alias.target_label
                                    );

                                const targetGroup =
                                    this.normalize(
                                        alias.target_group
                                    );

                                return (
                                    targetLabel
                                    === label
                                    &&
                                    (
                                        targetGroup === ''
                                        ||
                                        targetGroup
                                        === groupLabel
                                    )
                                );
                            }
                        );

                    const itemMatches =
                        query === ''
                        || groupMatches
                        || label.includes(query)
                        || aliasItemMatches;

                    item.style.display =
                        itemMatches
                            ? ''
                            : 'none';

                    if (itemMatches) {
                        visibleItems++;
                    }
                });

                /*
                 * Hide the complete navigation group when
                 * none of its items match.
                 */
                group.style.display =
                    query === ''
                    || groupMatches
                    || visibleItems > 0
                        ? ''
                        : 'none';
            });

            /*
             * Handle any navigation items that aren't inside
             * a Filament NavigationGroup.
             */
            sidebar
                .querySelectorAll(
                    '.fi-sidebar-item'
                )
                .forEach((item) => {
                    if (groupedItems.has(item)) {
                        return;
                    }

                    const labelElement =
                        item.querySelector(
                            '.fi-sidebar-item-label'
                        );

                    const label = this.normalize(
                        labelElement?.textContent
                        ?? item.textContent
                    );

                    const aliasItemMatches =
                        aliasItemTargets.some(
                            (alias) =>
                                this.normalize(
                                    alias.target_label
                                ) === label
                        );

                    item.style.display =
                        query === ''
                        || label.includes(query)
                        || aliasItemMatches
                            ? ''
                            : 'none';
                });
        },

        clearSearch() {
            this.query = '';
            this.applySearch();
            this.$refs.search.focus();
        },

        init() {
            // coqp-navigation-search-collapse-v1
            this.$watch('$store.sidebar.isOpen', (isOpen) => {
                if (! isOpen) {
                    this.query = '';
                    this.$nextTick(() => this.applySearch());
                }
            });
            try {
                this.aliases = JSON.parse(
                    this.$el.dataset
                        .navigationSearchAliases
                    || '[]'
                );
            } catch (error) {
                console.error(
                    'Navigation search aliases '
                    + 'could not be loaded.',
                    error
                );

                this.aliases = [];
            }

            this.$nextTick(() => {
                this.applySearch();
            });

            document.addEventListener(
                'livewire:navigated',
                () => {
                    this.$nextTick(() => {
                        this.applySearch();
                    });
                }
            );
        },
    }"
    data-navigation-search-aliases="{{ json_encode(
        $navigationSearchAliases,
        JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    ) }}"
    x-show="$store.sidebar.isOpen"
    class="coqp-navigation-search mb-3 w-full px-2"
>
    <style>
        .fi-sidebar:not(.fi-sidebar-open) .coqp-navigation-search {
            display: none !important;
        }

        /*
         * While filtering, make matching collapsed groups
         * visible without permanently changing their normal
         * collapsed/expanded state.
         */
        .fi-sidebar-nav[data-navigation-filtering="true"]
            .fi-sidebar-group-items {
            display: flex !important;
        }
    </style>

    <div class="relative">
        <x-filament::input.wrapper
            prefix-icon="heroicon-o-magnifying-glass"
        >
            <x-filament::input
                x-ref="search"
                x-model="query"
                x-on:input.debounce.50ms="applySearch()"
                x-on:keydown.escape.prevent="clearSearch()"
                type="search"
                placeholder="Search navigation..."
                aria-label="Search navigation"
                autocomplete="off"
            />
        </x-filament::input.wrapper>

    </div>
</div>
