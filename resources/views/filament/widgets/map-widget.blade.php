@once
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
/>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLs="
    crossorigin=""
></script>
@endonce

<x-filament-widgets::widget>
    <x-filament::section heading="Station map">

        @if(count($stations) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                No stations with registered coordinates.
            </p>
        @else
            <div
                x-data="eflMap(@json($stations))"
                x-init="init()"
                wire:ignore
            >
                <div id="efl-station-map" style="height: 460px; width: 100%; border-radius: 0.5rem; z-index: 1;"></div>
            </div>

            <div class="flex gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#22c55e"></span>
                    Active with tablet
                </span>
                <span class="flex items-center gap-1">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f59e0b"></span>
                    Active without tablet
                </span>
                <span class="flex items-center gap-1">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ef4444"></span>
                    Inactive
                </span>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>

@once
<script>
function eflMap(stations) {
    return {
        map: null,

        init() {
            if (!stations || stations.length === 0) return;

            this.map = L.map('efl-station-map');

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18,
            }).addTo(this.map);

            const colors = {
                'active':    '#22c55e',
                'no-tablet': '#f59e0b',
                'inactive':  '#ef4444',
            };

            const statusLabels = {
                'active':    'Active with tablet',
                'no-tablet': 'Active without tablet',
                'inactive':  'Inactive',
            };

            const bounds = [];

            stations.forEach(s => {
                const color = colors[s.status] ?? '#6b7280';

                L.circleMarker([s.lat, s.lng], {
                    radius:      9,
                    color:       color,
                    fillColor:   color,
                    fillOpacity: 0.85,
                    weight:      2,
                })
                .bindPopup(
                    `<strong>${s.name}</strong><br>` +
                    `<code>${s.code}</code> · ${s.country}<br>` +
                    `<span style="color:${color}">${statusLabels[s.status] ?? s.status}</span>`
                )
                .addTo(this.map);

                bounds.push([s.lat, s.lng]);
            });

            if (bounds.length > 0) {
                this.map.fitBounds(bounds, { padding: [40, 40] });
            }
        }
    };
}
</script>
@endonce
