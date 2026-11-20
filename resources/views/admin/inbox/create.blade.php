@extends('admin.layouts.app')

@section('title', 'New message')

@section('page-title')
    <span class="d-inline-flex align-items-center gap-2">
        <i class="fa-solid fa-pen-to-square text-primary"></i>New message
    </span>
@endsection

@section('page-subtitle', 'Write to a member of staff, with a document if needed.')

@section('page-actions')
    <a href="{{ route('admin.inbox.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Inbox
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <div class="card-soft p-4">
                @include('admin.inbox._compose')
            </div>
        </div>
    </div>
@endsection
