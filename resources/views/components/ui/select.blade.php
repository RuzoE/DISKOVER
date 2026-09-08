@props([
    'name',
    'label' => null,
    'options' => [],      // [value => label]
    'selected' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id', $name);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
    $current = old($errorKey, $selected);
@endphp

<div class="field">
    @if ($label)
        <x-ui.label :for="$id" :required="$required">{{ $label }}</x-ui.label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->merge(['class' => 'field__control'.($hasError ? ' field__control--invalid' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $current === (string) $optValue)>
                {{ $optLabel }}
            </option>
        @endforeach
    </select>

    @error($errorKey)
        <p class="field__error" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
