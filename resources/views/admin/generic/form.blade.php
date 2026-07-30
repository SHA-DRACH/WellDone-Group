{{--
    Generic create/edit screen, driven by $fields from the controller.

    Each field: ['type' => input|textarea|editor|select|checkbox|image|file|lines,
                 'label' => …, 'col' => 6, …]
--}}
@extends('admin.layouts.app')

@section('title', ($record->exists ? 'Edit ' : 'New ').strtolower($moduleSingular))

@section('page-title')
    {{ $record->exists ? 'Edit '.strtolower($moduleSingular) : 'New '.strtolower($moduleSingular) }}
@endsection

@section('page-subtitle')
    {{ $record->exists ? ($record->name ?? $record->title ?? '') : ($moduleSubtitle ?? '') }}
@endsection

@section('page-actions')
    <a href="{{ route("admin.$routeName.index") }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to list
    </a>
@endsection

@section('content')

    <form method="POST"
          action="{{ $record->exists ? route("admin.$routeName.update", $record) : route("admin.$routeName.store") }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if($record->exists) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-soft p-4">
                    <div class="row">
                        @foreach($fields as $name => $field)
                            @continue(($field['panel'] ?? 'main') !== 'main')
                            <div class="col-md-{{ $field['col'] ?? 12 }}">
                                @include('admin.generic.field', ['name' => $name, 'field' => $field, 'record' => $record])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Options</h2>

                    <div class="row">
                        @foreach($fields as $name => $field)
                            @continue(($field['panel'] ?? 'main') !== 'side')
                            <div class="col-md-{{ $field['col'] ?? 12 }}">
                                @include('admin.generic.field', ['name' => $name, 'field' => $field, 'record' => $record])
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-soft p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i>{{ $record->exists ? 'Save changes' : 'Create' }}
                    </button>
                    <a href="{{ route("admin.$routeName.index") }}" class="btn btn-outline-secondary w-100">Cancel</a>

                    @if($record->exists)
                        <hr>
                        <dl class="row small mb-0 text-muted">
                            <dt class="col-5 fw-normal">Created</dt>
                            <dd class="col-7 text-end">{{ $record->created_at?->format('j M Y') }}</dd>
                            <dt class="col-5 fw-normal">Updated</dt>
                            <dd class="col-7 text-end">{{ $record->updated_at?->diffForHumans() }}</dd>
                        </dl>
                    @endif
                </div>
            </div>
        </div>
    </form>

@endsection
