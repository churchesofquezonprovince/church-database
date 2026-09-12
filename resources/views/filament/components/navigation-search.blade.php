<div
    x-data="{
        query: '',

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

            const query = this.normalize(this.query);

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
                const groupMatches =
                    query !== ''
                    && groupLabel.includes(query);

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

                    const itemMatches =
                        query === ''
                        || groupMatches
                        || label.includes(query);

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

                    item.style.display =
                        query === ''
                        || label.includes(query)
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
    class="mb-3 w-full px-2"
>
    <style>
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
                autocomplete="off"
            />
        </x-filament::input.wrapper>

    </div>
</div>
