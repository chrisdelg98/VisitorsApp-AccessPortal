{{-- Renders the visitor's full visit history as styled cards. Safe against missing relations. --}}
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        /** @var \App\Models\Visitor $visitor */
        $visitor = $getRecord();
        $visits  = $visitor?->visits()?->with('station.country')->orderByDesc('check_in')->get() ?? collect();
    @endphp

    @if($visits->isEmpty())
        <p style="font-size:.875rem; color:#9ca3af; font-style:italic; padding:1rem 0;">
            No visits recorded yet for this person.
        </p>
    @else
        <div style="display:flex; flex-direction:column; gap:14px;">
        @foreach($visits as $visit)
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
                            {{ $visit->check_in?->format('d/m/Y H:i') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Check out</div>
                        <div style="color:{{ $visit->check_out ? '#111827' : '#3b82f6' }}; font-weight:500;">
                            {{ $visit->check_out?->format('d/m/Y H:i') ?? 'Still active' }}
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
                    @if($visit->visit_reason_custom)
                    <div>
                        <div style="font-size:.7rem; color:#6b7280;">Custom reason</div>
                        <div style="color:#111827;">{{ $visit->visit_reason_custom }}</div>
                    </div>
                    @endif
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

                @if($visit->notes)
                <div style="margin-top:10px; padding-top:10px; border-top:1px solid #f3f4f6;">
                    <div style="font-size:.7rem; color:#6b7280;">Notes</div>
                    <div style="color:#111827; white-space:pre-wrap;">{{ $visit->notes }}</div>
                </div>
                @endif
            </div>
        @endforeach
        </div>
    @endif
</x-dynamic-component>
