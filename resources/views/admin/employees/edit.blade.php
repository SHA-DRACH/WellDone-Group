@extends('admin.layouts.app')

@section('title', 'Edit employee')
@section('page-title', $record->full_name)
@section('page-subtitle', $record->employee_number.' · '.($record->position?->title ?? 'Position not set'))

@section('page-actions')
    <a href="{{ route('admin.employees.show', $record) }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to profile
    </a>
@endsection

@section('content')
    @include('admin.employees._form')
@endsection
