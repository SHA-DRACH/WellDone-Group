@extends('admin.layouts.app')

@section('title', ($record->exists ? 'Edit ' : 'New ').$schema['singular'])

@section('page-title')
    {{ $record->exists ? 'Edit '.$record->reference : 'New '.$schema['singular'] }}
@endsection

@section('page-subtitle', $division->name.' · '.$schema['title'])

@section('page-actions')
    <a href="{{ route('admin.division.records.index', $division->slug) }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to the log
    </a>
@endsection

@section('content')
    <form method="POST" novalidate
          action="{{ $record->exists
              ? route('admin.division.records.update', [$division->slug, $record])
              : route('admin.division.records.store', $division->slug) }}">
        @csrf
        @if($record->exists) @method('PUT') @endif

        <div class="card-soft p-4">
            @include('admin.division-records._fields', ['schema' => $schema, 'record' => $record])

            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-2"></i>{{ $record->exists ? 'Save changes' : 'Save '.$schema['singular'] }}
                </button>
                <a href="{{ route('admin.division.records.index', $division->slug) }}" class="btn btn-outline-secondary">Cancel</a>

                @if($record->exists)
                    <span class="ms-auto small text-muted align-self-center">
                        Entered by {{ $record->creator?->name ?? 'unknown' }} on {{ $record->created_at->format('j M Y, H:i') }}
                    </span>
                @endif
            </div>
        </div>
    </form>
@endsection
