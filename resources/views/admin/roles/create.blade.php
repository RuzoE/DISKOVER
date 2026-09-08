@extends('layouts.app')

@section('title', 'Nuevo rol')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nuevo rol</h1>
    </div>

    <x-ui.card>
        <x-admin.role-form
            :action="route('admin.roles.store')"
            method="POST"
            :permission-groups="$permissionGroups"
        />
    </x-ui.card>
@endsection
