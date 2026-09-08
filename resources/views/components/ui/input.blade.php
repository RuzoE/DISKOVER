@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
@endphp

<div class="field">
    @if ($label)
        <x-ui.label :for="$id" :required="$required">{{ $label }}</x-ui.label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($type !== 'password') value="{{ old($errorKey, $value) }}" @endif
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => 'field__control'.($hasError ? ' field__control--invalid' : '')]) }}
    >

    @if ($hint && ! $hasError)
        <p class="field__hint">{{ $hint }}</p>
    @endif

    @error($errorKey)
        <p class="field__error" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
