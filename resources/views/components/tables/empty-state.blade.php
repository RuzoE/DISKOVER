@props(['message' => 'No hay registros para mostrar.'])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <p>{{ $message }}</p>
    @isset($action)
        <div class="empty-state__action">{{ $action }}</div>
    @endisset
</div>
