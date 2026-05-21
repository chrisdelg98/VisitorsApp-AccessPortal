{{-- Passes the full array of proxy URLs to the reusable gallery component --}}
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $state  = $getState();
        $images = is_array($state) ? array_filter($state) : [];
    @endphp
    <x-photo-gallery :images="$images" />
</x-dynamic-component>
