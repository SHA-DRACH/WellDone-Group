@extends('admin.layouts.app')

@section('title', $record->exists ? 'Edit document' : 'Upload document')
@section('page-subtitle', 'Files are kept on private storage and can only be opened by permitted HR staff.')

@section('page-actions')
    <a href="{{ route('admin.employee-documents.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
@endsection

@section('content')
    @include('admin.hr._tabs')

    <form method="POST" enctype="multipart/form-data" novalidate
          action="{{ $record->exists ? route('admin.employee-documents.update', $record) : route('admin.employee-documents.store') }}">
        @csrf
        @if($record->exists) @method('PUT') @endif
        @if(request('return') === 'employee' || request('employee'))
            <input type="hidden" name="return" value="employee">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-soft p-4">
                    <div class="row">
                        <div class="col-md-12">
                            <x-form.select name="employee_id" label="Employee" :options="$employees" :value="$record->employee_id"
                                           placeholder="Choose an employee…" :required="true" />
                        </div>
                        <div class="col-md-5">
                            <x-form.select name="type" label="Document type" :options="App\Models\EmployeeDocument::TYPES" :value="$record->type" :required="true" />
                        </div>
                        <div class="col-md-7">
                            <x-form.input name="title" label="Title" :value="$record->title" :required="true" placeholder="e.g. Fixed-term contract 2026–2027" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="document_number" label="Document number" :value="$record->document_number" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="issue_date" label="Issued on" type="date" :value="$record->issue_date?->toDateString()" />
                        </div>
                        <div class="col-md-4">
                            <x-form.input name="expiry_date" label="Expires on" type="date" :value="$record->expiry_date?->toDateString()"
                                          help="Leave blank if it does not expire." />
                        </div>
                        <div class="col-12">
                            <x-form.textarea name="notes" label="Notes" :value="$record->notes" :rows="3" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-soft p-4 mb-4">
                    <label class="form-label" for="field-file">
                        File @unless($record->file_path)<span class="required-mark">*</span>@endunless
                    </label>
                    <input type="file" id="field-file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="form-control @error('file') is-invalid @enderror">
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">PDF, Word or a photo/scan, up to 15 MB.</div>

                    @if($record->file_path)
                        <div class="alert alert-light border small mt-3 mb-0">
                            <i class="fa-solid fa-paperclip me-1"></i>{{ $record->original_name ?? 'Current file' }}
                            @if($record->file_size)<span class="text-muted">· {{ \App\Support\Format::fileSize($record->file_size) }}</span>@endif
                            <div class="mt-1"><a href="{{ route('admin.employee-documents.download', $record) }}">Download</a> · uploading a new file replaces it.</div>
                        </div>
                    @endif
                </div>

                <div class="card-soft p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i>{{ $record->exists ? 'Save changes' : 'Upload' }}
                    </button>
                    <a href="{{ route('admin.employee-documents.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
