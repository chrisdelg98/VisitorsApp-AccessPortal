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

        /* ── Map legend (chips en línea, separados con divisor sutil) ── */
        .efl-map-legend {
            margin-top: 16px;
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            row-gap: 8px;
            column-gap: 18px;
        }
        .efl-map-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .efl-map-legend-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .efl-map-legend-label {
            font-size: 13px;
            color: #374151;
        }
        .efl-map-legend-count {
            font-size: 13px;
            font-weight: 700;
        }
        .efl-map-legend-sep {
            display: inline-block;
            width: 1px;
            height: 16px;
            background: #e5e7eb;
        }
        .efl-map-legend-total {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #6b7280;
            white-space: nowrap;
        }
        /* Dark mode */
        .dark .efl-map-legend {
            border-color: rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
        }
        .dark .efl-map-legend-label { color: #d1d5db; }
        .dark .efl-map-legend-sep { background: rgba(255, 255, 255, 0.10); }
        .dark .efl-map-legend-total { color: #9ca3af; }
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

            <div class="efl-map-legend">
                <div class="efl-map-legend-item">
                    <span class="efl-map-legend-dot" style="background:#22c55e;"></span>
                    <span class="efl-map-legend-label">Active with tablet</span>
                    <span class="efl-map-legend-count" style="color:#15803d;">{{ $counts['active'] }}</span>
                </div>

                <span class="efl-map-legend-sep"></span>

                <div class="efl-map-legend-item">
                    <span class="efl-map-legend-dot" style="background:#f59e0b;"></span>
                    <span class="efl-map-legend-label">Active without tablet</span>
                    <span class="efl-map-legend-count" style="color:#b45309;">{{ $counts['no-tablet'] }}</span>
                </div>

                <span class="efl-map-legend-sep"></span>

                <div class="efl-map-legend-item">
                    <span class="efl-map-legend-dot" style="background:#ef4444;"></span>
                    <span class="efl-map-legend-label">Inactive</span>
                    <span class="efl-map-legend-count" style="color:#b91c1c;">{{ $counts['inactive'] }}</span>
                </div>

                <div class="efl-map-legend-total">
                    <svg style="width:14px;height:14px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <span>{{ $counts['total'] }} station{{ $counts['total'] === 1 ? '' : 's' }} on the map</span>
                </div>
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

            // Si Livewire re-monta el componente (navegación SPA), el contenedor
            // queda con marca de Leaflet de la visita anterior. Liberamos antes
            // de inicializar para evitar el error "Map container is already initialized".
            const el = L.DomUtil.get('efl-stations-map-page');
            if (el && el._leaflet_id) {
                el._leaflet_id = null;
                el.innerHTML = '';
            }

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
