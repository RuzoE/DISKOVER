@props([
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'rows' => 4,
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

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => 'field__control'.($hasError ? ' field__control--invalid' : '')]) }}
    >{{ old($errorKey, $value) }}</textarea>

    @if ($hint && ! $hasError)
        <p class="field__hint">{{ $hint }}</p>
    @endif

    @error($errorKey)
        <p class="field__error" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
