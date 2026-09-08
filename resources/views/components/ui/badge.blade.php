@props(['color' => 'neutral']) {{-- neutral | green | amber | red | blue --}}

<span {{ $attributes->merge(['class' => 'badge badge--'.$color]) }}>{{ $slot }}</span>
