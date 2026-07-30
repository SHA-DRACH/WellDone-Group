@extends('admin.layouts.app')

@section('title', 'Edit account')
@section('page-title', $record->name)
@section('page-subtitle', $record->position ?: $record->email)

@section('page-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to users
    </a>
@endsection

@section('content')
    @include('admin.users._form')
@endsection
