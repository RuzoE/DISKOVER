@props([
    'variant' => 'primary', // primary | secondary | danger | ghost
    'type' => 'button',
    'href' => null,
    'as' => null,
])

@php
    $classes = 'btn btn--'.$variant;
    $tag = $href ? 'a' : ($as ?? 'button');
@endphp

@if ($tag === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <{{ $tag }} type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </{{ $tag }}>
@endif
