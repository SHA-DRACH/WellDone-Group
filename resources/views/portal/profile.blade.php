@extends('portal.layout')

@section('portal-content')

    @php($emergency = $employee->emergencyContacts->firstWhere('is_primary', true) ?? $employee->emergencyContacts->first())

    {{-- Read-only employment record --}}
    <div class="card-soft p-4 mb-4">
        <h2 class="h6 mb-1">Employment record</h2>
        <p class="small text-muted mb-3">
            Held by Human Resources. If anything here is wrong, contact HR — it
            cannot be changed from the portal.
        </p>

        <dl class="row small mb-0">
            <dt class="col-sm-4 text-muted fw-normal">Employee number</dt>
            <dd class="col-sm-8">{{ $employee->employee_number }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Full name</dt>
            <dd class="col-sm-8">{{ $employee->full_name }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Position</dt>
            <dd class="col-sm-8">{{ $employee->position?->title ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Department</dt>
            <dd class="col-sm-8">{{ $employee->department?->name ?? '—' }}</dd>

            @if($employee->division)
                <dt class="col-sm-4 text-muted fw-normal">Division</dt>
                <dd class="col-sm-8">{{ $employee->division->name }}</dd>
            @endif

            <dt class="col-sm-4 text-muted fw-normal">Employment type</dt>
            <dd class="col-sm-8">{{ $employee->employmentType?->name ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Reports to</dt>
            <dd class="col-sm-8">{{ $employee->manager?->full_name ?? '—' }}</dd>

            <dt class="col-sm-4 text-muted fw-normal">Date hired</dt>
            <dd class="col-sm-8">{{ $employee->date_hired?->format('j F Y') ?? '—' }}</dd>

            @if($employee->confirmation_date)
                <dt class="col-sm-4 text-muted fw-normal">Confirmed</dt>
                <dd class="col-sm-8">{{ $employee->confirmation_date->format('j F Y') }}</dd>
            @endif

            @if($employee->contract_expiry)
                <dt class="col-sm-4 text-muted fw-normal">Contract expires</dt>
                <dd class="col-sm-8">{{ $employee->contract_expiry->format('j F Y') }}</dd>
            @endif

            <dt class="col-sm-4 text-muted fw-normal">Status</dt>
            <dd class="col-sm-8">
                <span class="badge bg-{{ $employee->status_color }}-subtle text-{{ $employee->status_color }}">
                    {{ $employee->status_label }}
                </span>
            </dd>
        </dl>
    </div>

    {{-- Editable contact details --}}
    <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" novalidate>
        @csrf @method('PUT')

        <div class="card-soft p-4 mb-4">
            <h2 class="h6 mb-1">How to reach me</h2>
            <p class="small text-muted mb-3">Keep these current so HR and your manager can contact you.</p>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="personal_email">Personal email</label>
                    <input type="email" class="form-control @error('personal_email') is-invalid @enderror"
                           id="personal_email" name="personal_email"
                           value="{{ old('personal_email', $employee->personal_email) }}" maxlength="190">
                    @error('personal_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Your work address is {{ $employee->email ?: 'not set' }}.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           value="{{ old('phone', $employee->phone) }}" maxlength="40">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="alternative_phone">Alternative phone</label>
                    <input type="tel" class="form-control" id="alternative_phone" name="alternative_phone"
                           value="{{ old('alternative_phone', $employee->alternative_phone) }}" maxlength="40">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="city">City</label>
                    <input type="text" class="form-control" id="city" name="city"
                           value="{{ old('city', $employee->city) }}" maxlength="80">
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label" for="residential_address">Residential address</label>
                    <textarea class="form-control" id="residential_address" name="residential_address"
                              rows="2" maxlength="500">{{ old('residential_address', $employee->residential_address) }}</textarea>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label" for="photo">Photograph</label>
                    <div class="d-flex align-items-start gap-3">
                        <img src="{{ $employee->photo_url }}" alt="" class="rounded-circle"
                             width="64" height="64" style="object-fit: cover;">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control @error('photo') is-invalid @enderror"
                                   id="photo" name="photo" accept="image/*">
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-soft p-4 mb-4">
            <h2 class="h6 mb-1">Emergency contact</h2>
            <p class="small text-muted mb-3">Who we should call if something happens at work.</p>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="emergency_name">Name</label>
                    <input type="text" class="form-control" id="emergency_name" name="emergency_name"
                           value="{{ old('emergency_name', $emergency?->name) }}" maxlength="150">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="emergency_relationship">Relationship</label>
                    <input type="text" class="form-control" id="emergency_relationship" name="emergency_relationship"
                           value="{{ old('emergency_relationship', $emergency?->relationship) }}" maxlength="80"
                           placeholder="Spouse, parent, sibling…">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="emergency_phone">Phone</label>
                    <input type="tel" class="form-control" id="emergency_phone" name="emergency_phone"
                           value="{{ old('emergency_phone', $emergency?->phone) }}" maxlength="40">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="emergency_alternative_phone">Alternative phone</label>
                    <input type="tel" class="form-control" id="emergency_alternative_phone"
                           name="emergency_alternative_phone"
                           value="{{ old('emergency_alternative_phone', $emergency?->alternative_phone) }}" maxlength="40">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-2"></i>Save my details
            </button>
        </div>
    </form>

    {{-- Password --}}
    <div class="card-soft p-4">
        <h2 class="h6 mb-3">Change password</h2>

        <form method="POST" action="{{ route('portal.profile.password') }}" novalidate>
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label" for="current_password">Current password</label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                       id="current_password" name="current_password" required autocomplete="current-password">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" required autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">At least 8 characters, with upper and lower case and a number.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input type="password" class="form-control" id="password_confirmation"
                           name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-key me-2"></i>Change password
            </button>
        </form>
    </div>

@endsection
