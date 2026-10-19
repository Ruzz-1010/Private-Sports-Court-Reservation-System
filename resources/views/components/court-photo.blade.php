@props(['court', 'class' => 'h-40'])

@php
    $sizes = trim($class.' '.$attributes->get('class'));
    $name = Str::lower($court->name);
    $featured = Str::contains($name, ['basketball', 'hoops'])
        ? asset('images/courts/featured-basketball.png')
        : asset('images/courts/featured-badminton.png');
    // Existing seeded diagram images are replaced by real venue visuals. Any
    // later photo uploaded by an administrator continues to take priority.
    $isLegacyDiagram = Str::contains((string) $court->image_path, ['badminton-court', 'basketball-court', 'volleyball-court']);
    $photo = $court->imageUrl() && ! $isLegacyDiagram ? $court->imageUrl() : $featured;
@endphp

<div class="{{ $sizes }} overflow-hidden bg-slate-200">
    <img src="{{ $photo }}" alt="{{ $court->name }} court"
         class="h-full w-full rounded-[inherit] object-cover transition duration-700 ease-out group-hover:scale-110">
</div>
