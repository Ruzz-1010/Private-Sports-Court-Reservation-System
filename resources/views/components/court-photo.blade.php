@props(['court', 'class' => 'h-40'])

@php
    $sizes = trim($class.' '.$attributes->get('class'));
    $featured = asset('images/courts/featured-pickleball.png');
    // Earlier sample records pointed at sport-specific placeholders. Use the
    // pickleball venue visual until an administrator uploads a court photo.
    $isLegacyDiagram = Str::contains((string) $court->image_path, [
        'badminton-court',
        'basketball-court',
        'volleyball-court',
        'tennis-court',
    ]);
    $photo = $court->imageUrl() && ! $isLegacyDiagram ? $court->imageUrl() : $featured;
@endphp

<div class="{{ $sizes }} overflow-hidden bg-slate-200">
    <img src="{{ $photo }}" alt="{{ $court->name }} pickleball court"
         class="h-full w-full rounded-[inherit] object-cover transition duration-700 ease-out group-hover:scale-110">
</div>
