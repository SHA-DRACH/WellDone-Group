@extends('account.layout')

@section('account-content')

    <div class="card-soft p-4 mb-4">
        <h2 class="h6 mb-3">My details</h2>
        <p class="small text-muted mb-4">
            These details are used to pre-fill the quotation and application forms,
            so keeping them current saves you typing.
        </p>

        <form method="POST" action="{{ route('account.profile.update') }}" novalidate>
            @csrf @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="name">Full name <span class="required-mark">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="150">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="email">Email address <span class="required-mark">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Changing this requires confirming the new address.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                           id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="40">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="position">Organisation</label>
                    <input type="text" class="form-control @error('position') is-invalid @enderror"
                           id="position" name="position" value="{{ old('position', $user->position) }}" maxlength="150">
                    @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-2"></i>Save details
            </button>
        </form>
    </div>

    <div class="card-soft p-4">
        <h2 class="h6 mb-3">Change password</h2>

        <form method="POST" action="{{ route('account.profile.password') }}" novalidate>
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label" for="current_password">Current password <span class="required-mark">*</span></label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                       id="current_password" name="current_password" required autocomplete="current-password">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password">New password <span class="required-mark">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" required autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">At least 8 characters, with upper and lower case and a number.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="password_confirmation">Confirm new password <span class="required-mark">*</span></label>
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
