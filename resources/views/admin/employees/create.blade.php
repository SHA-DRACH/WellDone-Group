@extends('admin.layouts.app')

@section('title', 'Add employee')
@section('page-subtitle', 'The employee number is generated automatically on save.')

@section('page-actions')
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>All employees
    </a>
@endsection

@section('content')
    @include('admin.employees._form')
@endsection
