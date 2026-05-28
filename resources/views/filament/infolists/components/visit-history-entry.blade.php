{{-- Renders the visitor's LATEST visit + button to view full history page. --}}
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        /** @var \App\Models\Visitor $visitor */
        $visitor = $getRecord();
        $visit   = $visitor?->visits()?->with('station.country')->orderByDesc('check_in')->first();
        $historyUrl = \App\Filament\Resources\Visitors\VisitorResource::getUrl('history', ['record' => $visitor->id]);
    @endphp

    @if(!$visit)
        <p style="font-size:.875rem; color:#9ca3af; font-style:italic; padding:1rem 0;">
            No visits recorded yet for this person.
        </p>
    @else
        @php
            $statusColor = $visit->status === 'active' ? '#16a34a' : '#6b7280';
            $statusBg    = $visit->status === 'active' ? '#dcfce7' : '#f3f4f6';
        @endphp

        <div style="border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px;
                    background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.04);">

            {{-- Header row: station + dates + status --}}
            <div style="display:grid; grid-template-columns:2fr 1.4fr 1.4fr auto; gap:12px;
                        padding-bottom:10px; border-bottom:1px solid #f3f4f6; margin-bottom:10px;
                        align-items:start;">
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Station</div>
                    <div style="font-weight:600; color:#111827;">
                        {{ $visit->station?->name ?? '—' }}
                        @if($visit->station?->code)
                            <span style="font-size:.75rem; color:#6b7280; font-weight:400;">· {{ $visit->station->code }}</span>
                        @endif
                    </div>
                    @if($visit->station?->country)
                        <div style="font-size:.75rem; color:#6b7280;">{{ $visit->station->country->name }}</div>
                    @endif
                </div>

                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Check in</div>
                    <div style="font-weight:500; color:#111827;">
                        {{ \App\Support\TzFormatter::forCountry($visit->check_in, $visit->station?->country) ?? '—' }}
                    </div>
                </div>

                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Check out</div>
                    <div style="color:{{ $visit->check_out ? '#111827' : '#3b82f6' }}; font-weight:500;">
                        {{ \App\Support\TzFormatter::forCountry($visit->check_out, $visit->station?->country) ?? 'Still active' }}
                    </div>
                    @if($visit->duration_in_minutes !== null)
                        <div style="font-size:.75rem; color:#6b7280;">{{ $visit->duration_in_minutes }} min</div>
                    @endif
                </div>

                <div style="display:inline-flex; align-items:center; gap:6px;
                            background:{{ $statusBg }}; color:{{ $statusColor }};
                            padding:4px 10px; border-radius:9999px;
                            font-size:.75rem; font-weight:600; text-transform:capitalize;
                            white-space:nowrap;">
                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $statusColor }};"></span>
                    {{ $visit->status }}
                </div>
            </div>

            {{-- Visit details --}}
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px 16px;">
                <div>
                    <div style="font-size:.7rem; color:#6b7280;">Visitor type</div>
                    <div style="color:#111827;">{{ $visit->visitor_type ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280;">Reason</div>
                    <div style="color:#111827;">{{ $visit->visit_reason ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280;">Visiting</div>
                    <div style="color:#111827;">{{ $visit->visiting_person ?? '—' }}</div>
                </div>
                @if($visit->station?->device_model)
                <div>
                    <div style="font-size:.7rem; color:#6b7280;">Tablet model</div>
                    <div style="color:#111827;">{{ $visit->station->device_model }}</div>
                </div>
                @endif
                @if($visit->station?->location)
                <div>
                    <div style="font-size:.7rem; color:#6b7280;">Location</div>
                    <div style="color:#111827;">{{ $visit->station->location }}</div>
                </div>
                @endif
            </div>
        </div>
    @endif

    {{-- "View full history" button --}}
    @if($visitor)
    <div style="margin-top:14px; text-align:right;">
        <a href="{{ $historyUrl }}"
           style="display:inline-flex; align-items:center; gap:6px;
                  background:#2563eb; color:#fff; padding:8px 16px; border-radius:8px;
                  font-size:.875rem; font-weight:600; text-decoration:none;
                  transition:background .15s;"
           onmouseover="this.style.background='#1d4ed8'"
           onmouseout="this.style.background='#2563eb'">
            View full visit history
            <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
    @endif
</x-dynamic-component>
