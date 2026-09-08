@props(['for' => null, 'required' => false])

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'field__label']) }}>
    {{ $slot }}
    @if ($required)
        <span class="field__required" aria-hidden="true">*</span>
    @endif
</label>
