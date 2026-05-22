{{--
    Reusable photo gallery thumbnails.
    Requires window.eflOpen to be defined globally (loaded once via AdminPanelProvider renderHook).
    Usage: <x-photo-gallery :images="['https://...', 'https://...']" />
--}}
@props(['images' => []])

@php $images = array_values(array_filter((array) $images)); @endphp

@if(count($images))
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;">
    @foreach($images as $url)
    <button
        type="button"
        onclick="window.eflOpen && window.eflOpen('{{ $url }}')"
        style="position:relative;aspect-ratio:1/1;overflow:hidden;border-radius:8px;
               border:1px solid #e5e7eb;cursor:pointer;padding:0;background:none;
               transition:border-color .2s,box-shadow .2s;"
        onmouseover="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,.2)'"
        onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'"
    >
        <img
            src="{{ $url }}"
            alt=""
            loading="lazy"
            style="width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s ease;"
            onmouseover="this.style.transform='scale(1.06)'"
            onmouseout="this.style.transform='scale(1)'"
        />
    </button>
    @endforeach
</div>
@else
<p style="font-size:.875rem;color:#9ca3af;font-style:italic;">No photos</p>
@endif
