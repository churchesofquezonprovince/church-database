<x-filament-panels::page>
    @php
        $mapPoints = $this->mapPoints();
        $needsMapLocation = $this->needsMapLocation();
        $selectedLocality = $this->selectedLocality();
        $selectedStatus = $this->selectedStatus();
        $selectedCategory = $this->selectedCategory();
        $selectedShepherdingGroup = $this->selectedShepherdingGroup();
        $selectedNeedsOnly = $this->selectedNeedsOnly();
        $defaultLat = 13.9414;
        $defaultLng = 121.6236;
    @endphp

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />

    <div class="space-y-6">
        <div class="rounded-2xl border border-primary-200 bg-primary-50 p-6 shadow-sm dark:border-primary-900 dark:bg-primary-950">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Shepherding
            </p>

            <h2 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                Shepherding Map
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Household pins use the household head's coordinates first. If the head has no coordinates, the map uses the first household member with coordinates. People without a household appear as individual pins.
            </p>
        </div>


        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Map Filters
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Filter household and individual pins by locality, church status, category, and shepherding group.
                    </p>
                </div>

                @if ($this->hasActiveFilters())
                    <a
                        href="{{ $this->clearFiltersUrl() }}"
                        class="inline-flex rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900"
                    >
                        Clear Filters
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ \App\Filament\Pages\ShepherdingMap::getUrl() }}" class="mt-5 grid gap-4 lg:grid-cols-5">
                <div>
                    <label for="map_locality" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Locality
                    </label>

                    <select
                        id="map_locality"
                        name="locality"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">All Localities</option>

                        @foreach ($this->localityOptions() as $value => $label)
                            <option value="{{ $value }}" @selected($selectedLocality === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="map_status" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Status
                    </label>

                    <select
                        id="map_status"
                        name="status"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">All Statuses</option>

                        @foreach ($this->statusOptions() as $value => $label)
                            @php $optionValue = is_int($value) ? $label : $value; @endphp
                            <option value="{{ $optionValue }}" @selected($selectedStatus === $optionValue)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="map_category" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Category
                    </label>

                    <select
                        id="map_category"
                        name="category"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">All Categories</option>

                        @foreach ($this->categoryOptions() as $value => $label)
                            @php $optionValue = is_int($value) ? $label : $value; @endphp
                            <option value="{{ $optionValue }}" @selected($selectedCategory === $optionValue)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="map_shepherding_group" class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Shepherding Group
                    </label>

                    <select
                        id="map_shepherding_group"
                        name="shepherding_group"
                        class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    >
                        <option value="">All Groups</option>

                        @foreach ($this->shepherdingGroupOptions() as $value => $label)
                            @php $optionValue = is_int($value) ? $label : $value; @endphp
                            <option value="{{ $optionValue }}" @selected($selectedShepherdingGroup === $optionValue)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col justify-end gap-3">
                    <label class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                        <input
                            type="checkbox"
                            name="needs_only"
                            value="1"
                            @checked($selectedNeedsOnly)
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                        >
                        Needs location only
                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
                    >
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Map Pins</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $mapPoints->count() }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Household Pins</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $mapPoints->where('type', 'household')->count() }}
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Needs Map Location</p>
                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $needsMapLocation->count() }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            @if ($mapPoints->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    {{ $selectedNeedsOnly ? 'Needs Map Location only is enabled. Use the list below to update records without coordinates.' : 'No map pins yet. Add geocoordinates to people first using this format: 14.0642, 121.5540' }}
                </div>
            @else
                <div
                    id="shepherding-map"
                    class="h-[620px] w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700"
                ></div>
            @endif
        </div>



        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Map Pin Directory
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        List of all household and individual pins currently shown on the map.
                    </p>
                </div>
            </div>

            @if ($mapPoints->isEmpty())
                <div class="mt-5 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No map pins found for the selected filters.
                </div>
            @else
                <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-[980px] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Members</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Pin Source</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Coordinates</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($mapPoints as $point)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-bold',
                                            'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200' => $point['type'] === 'household',
                                            'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200' => $point['type'] === 'person',
                                        ])>
                                            {{ $point['type'] === 'household' ? 'Household' : 'Person' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $point['title'] }}
                                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                            {{ $point['subtitle'] }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ $point['locality'] }}
                                    </td>

                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                        {{ $point['members_count'] }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ $point['coordinate_source'] ?: 'Not recorded' }}
                                    </td>

                                    <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                        <button
                                            type="button"
                                            onclick="navigator.clipboard.writeText('{{ $point['coordinates'] }}')"
                                            class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 font-mono text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-800"
                                            title="Copy coordinates"
                                        >
                                            {{ $point['coordinates'] }}
                                        </button>
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a
                                                href="{{ $point['url'] }}"
                                                class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white hover:bg-primary-500"
                                            >
                                                Open
                                            </a>

                                            <a
                                                href="{{ $point['osm_url'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-500"
                                            >
                                                OSM
                                            </a>

                                            <a
                                                href="{{ $point['google_maps_url'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="rounded-full bg-sky-600 px-3 py-1 text-xs font-bold text-white hover:bg-sky-500"
                                            >
                                                Google
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Needs Map Location
                    </h3>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        These records are not shown on the map because they have no usable coordinates.
                    </p>
                </div>
            </div>

            @if ($needsMapLocation->isEmpty())
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                    All current household and individual records have usable map locations.
                </div>
            @else
                <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-[760px] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-950">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Type</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Reason</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Address Search</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($needsMapLocation as $row)
                                <tr>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row['type'] }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row['locality'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row['reason'] }}</td>

                                    <td class="px-4 py-3">
                                        @if (! empty($row['search_query']) && ! empty($row['search_url']))
                                            <div class="max-w-sm text-xs text-gray-500 dark:text-gray-400">
                                                {{ $row['search_query'] }}
                                            </div>

                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <a
                                                    href="{{ $row['search_url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-500"
                                                >
                                                    Search OSM
                                                </a>

                                                @if (! empty($row['google_maps_search_url']))
                                                    <a
                                                        href="{{ $row['google_maps_search_url'] }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="rounded-full bg-sky-600 px-3 py-1 text-xs font-bold text-white hover:bg-sky-500"
                                                    >
                                                        Search Google
                                                    </a>
                                                @endif

                                                <button
                                                    type="button"
                                                    onclick="navigator.clipboard.writeText(@js($row['search_query']))"
                                                    class="rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900"
                                                >
                                                    Copy Text
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                                No address available
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <a
                                            href="{{ $row['url'] }}"
                                            class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white hover:bg-primary-500"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($mapPoints->isNotEmpty())
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const points = @json($mapPoints->values());
                const map = L.map('shepherding-map').setView([{{ $defaultLat }}, {{ $defaultLng }}], 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                const markers = [];

                points.forEach((point) => {
                    const title = escapeHtml(point.title || 'Map pin');
                    const subtitle = escapeHtml(point.subtitle || '');
                    const locality = escapeHtml(point.locality || 'No locality');
                    const head = point.head ? escapeHtml(point.head) : null;
                    const source = point.coordinate_source ? escapeHtml(point.coordinate_source) : null;
                    const url = point.url || '#';

                    const popup = `
                        <div style="min-width: 220px">
                            <strong>${title}</strong>
                            <div style="margin-top: 4px; color: #64748b">${subtitle}</div>
                            <div style="margin-top: 8px">Locality: <strong>${locality}</strong></div>
                            ${head ? `<div>Head: <strong>${head}</strong></div>` : ''}
                            <div>Members: <strong>${point.members_count}</strong></div>
                            ${source ? `<div>Pin source: <strong>${source}</strong></div>` : ''}
                            <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="${url}">Open record</a>
                                <a href="${point.osm_url}" target="_blank" rel="noopener noreferrer">Open in OpenStreetMap</a>
                                <a href="${point.google_maps_url}" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
                            </div>
                        </div>
                    `;

                    const marker = L.marker([point.lat, point.lng])
                        .addTo(map)
                        .bindPopup(popup);

                    markers.push(marker);
                });

                if (markers.length > 0) {
                    const group = L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.2));
                }

                function escapeHtml(value) {
                    return String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');
                }
            });
        </script>
    @endif
</x-filament-panels::page>
