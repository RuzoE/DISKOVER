@extends('layouts.app')

@section('title', 'Editar rol')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Editar rol: {{ $role->name }}</h1>
    </div>

    <x-ui.card>
        <x-admin.role-form
            :action="route('admin.roles.update', $role)"
            method="PUT"
            :role="$role"
            :permission-groups="$permissionGroups"
        />
    </x-ui.card>
@endsection
