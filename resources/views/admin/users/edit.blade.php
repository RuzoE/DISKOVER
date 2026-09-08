@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Editar usuario</h1>
            <p class="page-header__subtitle">{{ $user->email }}</p>
        </div>
        <x-ui.button :href="route('admin.users.show', $user)" variant="ghost">Ver detalle</x-ui.button>
    </div>

    <x-ui.card>
        <x-admin.user-form
            :action="route('admin.users.update', $user)"
            method="PUT"
            :user="$user"
            :roles="$roles"
            :statuses="$statuses"
        />
    </x-ui.card>
@endsection
