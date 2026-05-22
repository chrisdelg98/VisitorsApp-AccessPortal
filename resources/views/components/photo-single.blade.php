{{--
    Single large photo with click-to-enlarge lightbox.
    Requires window.eflOpen (loaded globally by AdminPanelProvider).
    Usage: <x-photo-single :url="$url" :height="240" />
--}}
@props(['url' => null, 'height' => 240])

@if($url)
<button
    type="button"
    onclick="window.eflOpen('{{ $url }}')"
    style="border:1px solid #e5e7eb; border-radius:12px; padding:0; background:none;
           cursor:pointer; overflow:hidden; display:block; line-height:0;
           transition:border-color .2s, box-shadow .2s;"
    onmouseover="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,.2)'"
    onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'"
>
    <img
        src="{{ $url }}"
        alt=""
        loading="lazy"
        style="height:{{ $height }}px; width:auto; max-width:100%; object-fit:cover; display:block;
               transition:transform .3s ease;"
        onmouseover="this.style.transform='scale(1.03)'"
        onmouseout="this.style.transform='scale(1)'"
    />
</button>
@else
<div style="height:{{ $height }}px; display:flex; align-items:center; justify-content:center;
            background:#f3f4f6; border:1px dashed #d1d5db; border-radius:12px;
            color:#9ca3af; font-size:.875rem; font-style:italic;">
    No photo available
</div>
@endif
