@extends('admin.layouts.app')

@section('title', 'Edit role')
@section('page-title', $record->name)
@section('page-subtitle', 'Changes take effect immediately for everyone holding this role.')

@section('page-actions')
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to roles
    </a>
@endsection

@section('content')
    @include('admin.roles._form')
@endsection
