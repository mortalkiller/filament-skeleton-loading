{{-- In panels with the plugin, replaces Filament's grey pulsing box (lazy widgets and sections) with a generic skeleton. --}}
@props([
    'columnSpan' => [],
    'columnStart' => [],
    'height' => null,
    'loadingPlaceholder' => null,
    'loadingLabel' => null,
])

@php
    use MortalKiller\FilamentSkeletonLoading\SkeletonLoadingPlugin;
    use MortalKiller\FilamentSkeletonLoading\SkeletonLoadingServiceProvider;

    // Choose the view without Blade conditionals so Livewire's morph markers never precede the root.
    $loadingSectionView = SkeletonLoadingPlugin::current() && ($loadingPlaceholder === null)
        ? 'filament-skeleton-loading::loading-section'
        : SkeletonLoadingServiceProvider::FILAMENT_VIEWS.'::components.loading-section';
@endphp

@include($loadingSectionView)
