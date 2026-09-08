@extends('layouts.app')

@section('title', 'Nuevo usuario')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nuevo usuario</h1>
    </div>

    <x-ui.card>
        <x-admin.user-form
            :action="route('admin.users.store')"
            method="POST"
            :roles="$roles"
            :statuses="$statuses"
        />
    </x-ui.card>
@endsection
