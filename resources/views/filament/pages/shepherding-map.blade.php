<x-filament-panels::page>
    @php
        $mapPoints = $this->mapPoints();
        $needsMapLocation = $this->needsMapLocation();
        $defaultLat = 13.9414;
        $defaultLng = 121.6236;
    @endphp

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIINfQHWTxM5wB4rGgXgX6C5K4K4Ck8Q7qg="
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
                    No map pins yet. Add geocoordinates to people first using this format: <strong>14.0642, 121.5540</strong>
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
                            <div style="margin-top: 10px">
                                <a href="${url}">Open record</a>
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
