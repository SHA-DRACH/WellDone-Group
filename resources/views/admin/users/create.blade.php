@extends('admin.layouts.app')

@section('title', 'New account')
@section('page-subtitle', 'The new user receives a verification email and a link to set their own password — no password is ever emailed.')

@section('page-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to users
    </a>
@endsection

@section('content')
    @include('admin.users._form')
@endsection
