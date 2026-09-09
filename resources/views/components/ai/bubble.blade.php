@props(['message'])

@php
    $isUser = $message->role === App\Enums\AiMessageRole::User;
    $html = \Illuminate\Support\Str::of(e($message->content))
        ->replaceMatches('/\*\*(.+?)\*\*/s', '<strong>$1</strong>')
        ->replaceMatches('/(^|\n)- (.+)/', '$1• $2')
        ->replace("\n", '<br>');
@endphp

<div class="chat-msg chat-msg--{{ $isUser ? 'user' : 'assistant' }} {{ $message->failed ? 'chat-msg--failed' : '' }}">
    <span class="chat-msg__role">{{ $message->role->label() }}</span>
    <div class="chat-msg__body">{!! $html !!}</div>
    <span class="chat-msg__time">{{ $message->created_at?->format('H:i') }}</span>
</div>
