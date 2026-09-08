@props([
    'type' => 'info', // info | success | warning | danger
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'alert alert--'.$type]) }} role="alert">
    @if ($title)
        <p class="alert__title">{{ $title }}</p>
    @endif
    <div class="alert__body">{{ $slot }}</div>
</div>
