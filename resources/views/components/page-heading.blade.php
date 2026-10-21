@props(['eyebrow' => null, 'title', 'description' => null])
<header {{ $attributes->class(['page-heading']) }}>
    <div class="min-w-0">
        @if ($eyebrow)<p class="workspace-kicker">{{ $eyebrow }}</p>@endif
        <h1>{{ $title }}</h1>
        @if ($description)<p class="page-description">{{ $description }}</p>@endif
    </div>
    @if ($slot->isNotEmpty())<div class="page-heading-actions">{{ $slot }}</div>@endif
</header>
