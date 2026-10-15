@extends('admin.layouts.app')

@section('title', $record->exists ? 'Edit training' : 'Add training')
@section('page-subtitle', 'A course, its outcome, and any certificate that has to be renewed.')

@section('page-actions')
    <a href="{{ route('admin.training.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
@endsection

@section('content')
    @include('admin.hr._tabs')

    <form method="POST" enctype="multipart/form-data" novalidate
          action="{{ $record->exists ? route('admin.training.update', $record) : route('admin.training.store') }}">
        @csrf
        @if($record->exists) @method('PUT') @endif
        @if(request('return') === 'employee' || request('employee'))
            <input type="hidden" name="return" value="employee">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-soft p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Course</h2>
                    <div class="row">
                        <div class="col-md-12">
                            <x-form.select name="employee_id" label="Employee" :options="$employees" :value="$record->employee_id"
                                           placeholder="Choose an employee…" :required="true" />
                        </div>
                        <div class="col-md-8">
                            <x-form.input name="title" label="Course / programme" :value="$record->title" :required="true"
                                          placeholder="e.g. First Aid at Work" />
                        </div>
                        <div class="col-md-4">
                            <x-form.select name="category" label="Category" :options="App\Models\EmployeeTraining::CATEGORIES" :value="$record->category" :required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="provider" label="Provider" :value="$record->provider" placeholder="In-house, Red Cross, Cisco Academy…" />
                        </div>
                        <div class="col-md-3">
                            <x-form.input name="start_date" label="Start date" type="date" :value="$record->start_date?->toDateString()" />
                        </div>
                        <div class="col-md-3">
                            <x-form.input name="end_date" label="End date" type="date" :value="$record->end_date?->toDateString()" />
                        </div>
                        <div class="col-md-3">
                            <x-form.input name="hours" label="Hours" type="number" :value="$record->hours" :extra="['step' => '0.5', 'min' => 0]" />
                        </div>
                        <div class="col-md-3">
                            <x-form.input name="cost" label="Cost (USD)" type="number" :value="$record->cost" :extra="['step' => '0.01', 'min' => 0]" />
                        </div>
                        <div class="col-md-3">
                            <x-form.select name="status" label="Status" :options="App\Models\EmployeeTraining::STATUSES" :value="$record->status" :required="true" />
                        </div>
                        <div class="col-md-3">
                            <x-form.input name="result" label="Result" :value="$record->result" placeholder="Passed, 87%…" />
                        </div>
                        <div class="col-12">
                            <x-form.textarea name="notes" label="Notes" :value="$record->notes" :rows="3" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Certificate</h2>
                    <x-form.input name="certificate_number" label="Certificate number" :value="$record->certificate_number" />
                    <x-form.input name="certificate_expiry" label="Expires on" type="date" :value="$record->certificate_expiry?->toDateString()"
                                  help="HR is alerted 60 days before this date." />

                    <div class="mb-3">
                        <label class="form-label" for="field-certificate">Certificate file</label>
                        <input type="file" id="field-certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png"
                               class="form-control @error('certificate') is-invalid @enderror">
                        @error('certificate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">PDF or image, up to 10 MB. Stored privately.</div>
                        @if($record->certificate_path)
                            <div class="form-text">
                                <a href="{{ route('admin.training.certificate', $record) }}"><i class="fa-solid fa-download me-1"></i>Current certificate</a>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="remove_certificate" value="1" id="remove-certificate">
                                    <label class="form-check-label small text-danger" for="remove-certificate">Remove it</label>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-soft p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i>{{ $record->exists ? 'Save changes' : 'Add training' }}
                    </button>
                    <a href="{{ route('admin.training.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </div>
        </div>
    </form>
@endsection
