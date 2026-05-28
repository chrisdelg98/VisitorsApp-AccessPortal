@once
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <style>
        .efl-map-page .leaflet-container {
            background: #e0f2fe;
        }
        .efl-map-popup .efl-popup-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            margin-top: 2px;
        }
        .efl-map-popup .efl-popup-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 10px;
            background: #2563eb;
            color: #fff;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
        }
        .efl-map-popup .efl-popup-btn:hover {
            background: #1d4ed8;
        }
    </style>
@endonce

<x-filament-panels::page>
    @if(count($stations) === 0)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400 py-8 text-center">
                No stations with registered coordinates.
            </p>
        </x-filament::section>
    @else
        <div class="efl-map-page" wire:ignore>
            <div
                x-data="eflStationsMapPage(@js($stations))"
                x-init="init()"
            >
                <div id="efl-stations-map-page" style="height: 75vh; width: 100%; border-radius: 0.5rem;"></div>
            </div>

            <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
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
                <span class="ml-auto text-gray-400">{{ count($stations) }} station(s) on the map</span>
            </div>
        </div>
    @endif
</x-filament-panels::page>

@once
<script>
function eflStationsMapPage(stations) {
    return {
        map: null,

        init() {
            if (!stations || stations.length === 0) return;

            this.map = L.map('efl-stations-map-page', {
                worldCopyJump: true,
                minZoom: 2,
                maxZoom: 10,
            }).setView([15, -80], 4); // Centroamérica por default

            // Pane dedicado para los países, DEBAJO del overlayPane (z=400)
            // así los markers siempre quedan visibles encima del fondo.
            this.map.createPane('countriesPane');
            this.map.getPane('countriesPane').style.zIndex = 350;

            fetch('{{ asset('geo/world.geo.json') }}')
                .then(r => r.json())
                .then(geo => {
                    L.geoJSON(geo, {
                        pane: 'countriesPane',
                        style: {
                            color:       '#94a3b8',
                            weight:      0.6,
                            fillColor:   '#f1f5f9',
                            fillOpacity: 1,
                        },
                        interactive: false,
                    }).addTo(this.map);
                })
                .catch(err => console.error('No se pudo cargar el mapa base offline:', err));

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

                const popupHtml = `
                    <div class="efl-map-popup">
                        <div style="font-weight:600;font-size:14px;margin-bottom:2px">${s.name}</div>
                        <div style="font-size:12px;color:#64748b">
                            <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px">${s.code}</code>
                            · ${s.country}
                        </div>
                        <div style="margin-top:4px;color:${color};font-size:12px;font-weight:500">
                            ${statusLabels[s.status] ?? s.status}
                        </div>
                        <hr style="margin:8px 0;border:none;border-top:1px solid #e2e8f0">
                        <div class="efl-popup-row">
                            <span>Active visits now:</span>
                            <strong>${s.active_visits_count}</strong>
                        </div>
                        <div class="efl-popup-row">
                            <span>Last activity:</span>
                            <strong>${s.last_activity_at
                                ? `<span class="efl-tz" data-utc="${s.last_activity_utc}">${s.last_activity_at}</span>`
                                : '—'}</strong>
                        </div>
                        <a href="${s.visits_url}" class="efl-popup-btn">View visits →</a>
                    </div>
                `;

                L.circleMarker([s.lat, s.lng], {
                    radius:      9,
                    color:       color,
                    fillColor:   color,
                    fillOpacity: 0.85,
                    weight:      2,
                })
                .bindPopup(popupHtml)
                .addTo(this.map);

                bounds.push([s.lat, s.lng]);
            });

            if (bounds.length > 0) {
                this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 7 });
            }
        }
    };
}
</script>
@endonce
