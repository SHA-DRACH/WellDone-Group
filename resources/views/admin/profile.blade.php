@extends('admin.layouts.app')

@section('title', 'My Profile')
@section('page-subtitle', 'Your own account details and password.')

@section('content')

    <div class="row g-4">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" novalidate>
                @csrf @method('PUT')

                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Profile</h2>

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input name="name" label="Full name" :value="$record->name" required />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="email" type="email" label="Email address" :value="$record->email" required
                                          help="Changing this requires re-verifying the new address." />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="phone" label="Phone" :value="$record->phone" />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="position" label="Job title" :value="$record->position" />
                        </div>
                        <div class="col-12">
                            <x-form.textarea name="bio" label="Short biography" :value="$record->bio" :rows="3" />
                        </div>
                        <div class="col-12">
                            <x-form.image name="avatar" label="Profile photo" :current="$record->avatar_url" />
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save profile
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.profile.password') }}" novalidate>
                @csrf @method('PUT')

                <div class="card-soft p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Change password</h2>

                    <x-form.input name="current_password" type="password" label="Current password"
                                  required autocomplete="current-password" />

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input name="password" type="password" label="New password" required
                                          autocomplete="new-password"
                                          help="Min. 8 characters with upper and lower case, a number and a symbol." />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="password_confirmation" type="password" label="Confirm new password"
                                          required autocomplete="new-password" />
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-key me-2"></i>Change password
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card-soft p-4 text-center mb-4">
                <img src="{{ $record->avatar_url }}" alt="" class="rounded-circle mb-3"
                     width="110" height="110" style="object-fit: cover;">
                <h2 class="h5 mb-1">{{ $record->name }}</h2>
                <p class="text-muted small mb-2">{{ $record->position ?: 'Staff member' }}</p>

                <div class="mb-3">
                    @foreach($record->getRoleNames() as $role)
                        <span class="badge bg-primary-subtle text-primary">{{ $role }}</span>
                    @endforeach
                </div>

                @if($record->division)
                    <a href="{{ route('admin.division.dashboard', $record->division->slug) }}"
                       class="btn btn-sm btn-outline-primary w-100">
                        <i class="{{ $record->division->icon }} me-2"></i>{{ $record->division->name }} dashboard
                    </a>
                @endif
            </div>

            <div class="card-soft p-4">
                <h2 class="h6 mb-3">Account</h2>
                <dl class="row small mb-0">
                    <dt class="col-6 text-muted fw-normal">Email verified</dt>
                    <dd class="col-6 text-end">
                        @if($record->hasVerifiedEmail())
                            <span class="badge bg-success-subtle text-success">Yes</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Pending</span>
                        @endif
                    </dd>
                    <dt class="col-6 text-muted fw-normal">Division</dt>
                    <dd class="col-6 text-end">{{ $record->division?->name ?? 'Group-level' }}</dd>
                    <dt class="col-6 text-muted fw-normal">Last sign-in</dt>
                    <dd class="col-6 text-end">{{ $record->last_login_at?->diffForHumans() ?? 'This session' }}</dd>
                    <dt class="col-6 text-muted fw-normal">Member since</dt>
                    <dd class="col-6 text-end">{{ $record->created_at?->format('M Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

@endsection
