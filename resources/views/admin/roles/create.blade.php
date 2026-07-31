@extends('admin.layouts.app')

@section('title', 'New role')
@section('page-subtitle', 'Pick exactly the capabilities this role needs — nothing more.')

@section('page-actions')
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to roles
    </a>
@endsection

@section('content')
    @include('admin.roles._form')
@endsection
