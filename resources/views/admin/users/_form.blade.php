@php($isNew = ! $record->exists)

<form method="POST"
      action="{{ $isNew ? route('admin.users.store') : route('admin.users.update', $record) }}"
      enctype="multipart/form-data" novalidate>
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-soft p-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Account details</h2>

                <div class="row">
                    <div class="col-md-6">
                        <x-form.input name="name" label="Full name" :value="$record->name" required />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="email" type="email" label="Email address" :value="$record->email" required
                                      help="Sign-in address. Changing it requires re-verification." />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="phone" label="Phone" :value="$record->phone" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="position" label="Job title" :value="$record->position" />
                    </div>
                    <div class="col-md-6">
                        <x-form.input name="department" label="Department" :value="$record->department" />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="division_id" label="Division"
                                       :value="$record->division_id"
                                       :options="$divisions->all()"
                                       placeholder="Group-level (no single division)"
                                       help="Division staff land on that division's dashboard when they sign in." />
                    </div>
                    <div class="col-12">
                        <x-form.textarea name="bio" label="Short biography" :value="$record->bio" :rows="3" />
                    </div>
                </div>
            </div>

            @unless($isNew)
                <div class="card-soft p-4 mt-4">
                    <h2 class="h6 text-uppercase text-muted mb-1" style="letter-spacing:.1em;">Reset password</h2>
                    <p class="small text-muted">
                        Leave both fields blank to keep the current password. Setting one here does not
                        notify the user — normally they should use "Forgotten?" on the sign-in page instead.
                    </p>

                    <div class="row">
                        <div class="col-md-6">
                            <x-form.input name="password" type="password" label="New password"
                                          autocomplete="new-password"
                                          help="Min. 8 characters with upper and lower case, a number and a symbol." />
                        </div>
                        <div class="col-md-6">
                            <x-form.input name="password_confirmation" type="password" label="Confirm new password"
                                          autocomplete="new-password" />
                        </div>
                    </div>
                </div>
            @endunless
        </div>

        <div class="col-lg-4">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Roles</h2>

                @error('roles')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

                @foreach($roles as $roleName)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="roles[]"
                               value="{{ $roleName }}" id="role-{{ \Illuminate\Support\Str::slug($roleName) }}"
                               @checked(in_array($roleName, old('roles', $currentRoles), true))>
                        <label class="form-check-label small" for="role-{{ \Illuminate\Support\Str::slug($roleName) }}">
                            {{ $roleName }}
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="card-soft p-4 mb-4">
                <x-form.image name="avatar" label="Profile photo" :current="$record->exists ? $record->avatar_url : null" />

                <x-form.checkbox name="is_active" label="Account is active"
                                 :value="$record->exists ? $record->is_active : true"
                                 help="Deactivating ends the user's session on their next request." />

                @if($isNew)
                    <x-form.checkbox name="send_welcome" label="Send welcome email" :value="true"
                                     help="Emails a link for the user to set their own password. A separate verification email is always sent." />
                @endif
            </div>

            <div class="card-soft p-4">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fa-solid fa-floppy-disk me-2"></i>{{ $isNew ? 'Create account' : 'Save changes' }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>

                @unless($isNew)
                    <hr>
                    <dl class="row small mb-0 text-muted">
                        <dt class="col-6 fw-normal">Email verified</dt>
                        <dd class="col-6 text-end">{{ $record->hasVerifiedEmail() ? 'Yes' : 'No' }}</dd>
                        <dt class="col-6 fw-normal">Last sign-in</dt>
                        <dd class="col-6 text-end">{{ $record->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                        <dt class="col-6 fw-normal">Created</dt>
                        <dd class="col-6 text-end">{{ $record->created_at?->format('j M Y') }}</dd>
                    </dl>
                @endunless
            </div>
        </div>
    </div>
</form>
