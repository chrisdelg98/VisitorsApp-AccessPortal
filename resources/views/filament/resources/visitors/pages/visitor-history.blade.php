<x-filament-panels::page>
    @php
        /** @var \App\Models\Visitor $v */
        $v = $this->getRecord();
        $latest = $v->latestVisit()->with('station')->first();
        $photoUrl = $v->face_photo_url;
    @endphp

    {{-- Visitor identity card --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px;
                padding:20px; margin-bottom:20px; box-shadow:0 1px 2px rgba(0,0,0,.04);
                display:grid; grid-template-columns:auto 1fr; gap:24px; align-items:start;">

        {{-- Photo --}}
        <div>
            <x-photo-single :url="$photoUrl" :height="200" />
        </div>

        {{-- Info grid --}}
        <div>
            <div style="font-size:.75rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Visitor</div>
            <h2 style="font-size:1.5rem; font-weight:700; color:#111827; margin:2px 0 16px;">
                {{ $v->full_name }}
            </h2>

            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px 24px;">
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Document</div>
                    <div style="color:#111827; font-weight:500;">{{ $v->document_number ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Doc. type</div>
                    <div style="color:#111827; font-weight:500;">{{ $v->document_type ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Total visits</div>
                    <div style="color:#111827; font-weight:500;">{{ $v->visits()->count() }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Email</div>
                    <div style="color:#111827;">{{ $v->email ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Phone</div>
                    <div style="color:#111827;">{{ $v->phone ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Company</div>
                    <div style="color:#111827;">{{ $v->company ?? '—' }}</div>
                </div>

                {{-- Data from the latest visit --}}
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Latest type</div>
                    <div style="color:#111827;">{{ $latest?->visitor_type ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Latest station</div>
                    <div style="color:#111827;">{{ $latest?->station?->name ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em;">Latest visit</div>
                    <div style="color:#111827;">{{ $latest?->check_in?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Visits table --}}
    {{ $this->table }}
</x-filament-panels::page>
