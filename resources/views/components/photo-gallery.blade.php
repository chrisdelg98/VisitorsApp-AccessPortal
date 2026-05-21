{{--
    Reusable photo gallery with lightbox (inline styles — no Tailwind build required).
    Usage: <x-photo-gallery :images="['https://...', 'https://...']" />
--}}
@props(['images' => []])

@php $images = array_values(array_filter((array) $images)); @endphp

@if(count($images))
<div x-data="{
        open: false,
        current: null,
        show(url) { this.current = url; this.open = true; document.body.style.overflow = 'hidden'; },
        close()    { this.open = false; this.current = null; document.body.style.overflow = ''; }
     }"
     @keydown.escape.window="close()"
>
    {{-- Thumbnail grid --}}
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;">
        @foreach($images as $url)
        <button
            type="button"
            @click="show('{{ $url }}')"
            style="position:relative; aspect-ratio:1/1; overflow:hidden; border-radius:8px;
                   border:1px solid #e5e7eb; cursor:pointer; padding:0; background:none;
                   transition:border-color .2s;"
            onmouseover="this.style.borderColor='#3b82f6'"
            onmouseout="this.style.borderColor='#e5e7eb'"
        >
            <img
                src="{{ $url }}"
                alt=""
                loading="lazy"
                style="width:100%; height:100%; object-fit:cover; display:block;
                       transition:transform .3s;"
                onmouseover="this.style.transform='scale(1.06)'"
                onmouseout="this.style.transform='scale(1)'"
            />
        </button>
        @endforeach
    </div>

    {{-- Lightbox overlay --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity:0"
        x-transition:enter-end="opacity:1"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity:1"
        x-transition:leave-end="opacity:0"
        @click.self="close()"
        style="position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:99999;
               display:flex; align-items:center; justify-content:center;
               background:rgba(0,0,0,.82);
               -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px);"
        x-cloak
    >
        {{-- Close button --}}
        <button
            type="button"
            @click="close()"
            style="position:absolute; top:16px; right:16px; z-index:10;
                   background:rgba(30,30,30,.7); border:2px solid rgba(255,255,255,.55);
                   border-radius:50%; width:44px; height:44px; cursor:pointer;
                   color:#fff; font-size:18px; font-weight:700;
                   display:flex; align-items:center; justify-content:center;
                   box-shadow:0 2px 12px rgba(0,0,0,.5); transition:all .2s;"
            onmouseover="this.style.background='rgba(255,255,255,.25)'; this.style.borderColor='#fff'"
            onmouseout="this.style.background='rgba(30,30,30,.7)'; this.style.borderColor='rgba(255,255,255,.55)'"
            title="Close (Esc)"
        >✕</button>

        {{-- Full-size image — centered --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity:0; transform:scale(.9)"
            x-transition:enter-end="opacity:1; transform:scale(1)"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity:1; transform:scale(1)"
            x-transition:leave-end="opacity:0; transform:scale(.95)"
            style="display:flex; align-items:center; justify-content:center;
                   width:100%; height:100%; pointer-events:none;"
            x-cloak
        >
            <img
                :src="current"
                alt=""
                style="max-width:88vw; max-height:86vh; border-radius:10px;
                       box-shadow:0 25px 60px rgba(0,0,0,.6); object-fit:contain;
                       pointer-events:auto; margin:auto; display:block;"
            />
        </div>
    </div>
</div>
@else
    <p style="font-size:.875rem; color:#9ca3af; font-style:italic;">No photos</p>
@endif
