<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <x-photo-single :url="$getState()" :height="240" />
</x-dynamic-component>
