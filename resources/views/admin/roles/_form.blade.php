@php($isNew = ! $record->exists)
@php($isProtected = $isProtected ?? false)
@php($isSuperAdmin = $record->name === App\Support\Roles::SUPER_ADMIN)

<form method="POST"
      action="{{ $isNew ? route('admin.roles.store') : route('admin.roles.update', $record) }}"
      novalidate>
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-soft p-4 mb-4">
                <x-form.input name="name" label="Role name" :value="$record->name" required
                              :disabled="$isProtected"
                              :help="$isProtected
                                  ? 'Built-in role names are fixed — the application references them by name.'
                                  : 'Shown when assigning roles to users.'" />
                @if($isProtected)
                    <input type="hidden" name="name" value="{{ $record->name }}">
                @endif
            </div>

            @if($isSuperAdmin)
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    This role is authorised for everything by a code-level rule, so it holds no
                    explicit permissions and there is nothing to configure here.
                </div>
            @else
                <div class="card-soft p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h6 mb-0">Permissions</h2>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-select-all-permissions>
                                Select all
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-clear-all-permissions>
                                Clear all
                            </button>
                        </div>
                    </div>

                    @error('permissions')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

                    <div class="row g-3">
                        @foreach($grouped as $module => $permissions)
                            <div class="col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong class="small">{{ App\Support\Permissions::moduleLabel($module) }}</strong>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                                                data-toggle-module="{{ $module }}">Toggle</button>
                                    </div>

                                    @foreach($permissions as $permission)
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox"
                                                   data-module="{{ $module }}"
                                                   type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission }}"
                                                   id="perm-{{ \Illuminate\Support\Str::slug($permission) }}"
                                                   @checked(in_array($permission, old('permissions', $assigned), true))>
                                            <label class="form-check-label small"
                                                   for="perm-{{ \Illuminate\Support\Str::slug($permission) }}">
                                                {{ App\Support\Permissions::label($permission) }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card-soft p-4">
                <button type="submit" class="btn btn-primary w-100 mb-2">
                    <i class="fa-solid fa-floppy-disk me-2"></i>{{ $isNew ? 'Create role' : 'Save changes' }}
                </button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>

                @unless($isNew)
                    <hr>
                    <dl class="row small mb-0 text-muted">
                        <dt class="col-7 fw-normal">Members</dt>
                        <dd class="col-5 text-end">{{ $record->users()->count() }}</dd>
                        <dt class="col-7 fw-normal">Permissions</dt>
                        <dd class="col-5 text-end">
                            {{ $isSuperAdmin ? 'All' : $record->permissions()->count() }}
                        </dd>
                    </dl>
                @endunless
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    // Bulk selection helpers for the permission matrix.
    document.querySelectorAll('[data-select-all-permissions]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.permission-checkbox').forEach((box) => { box.checked = true; });
        });
    });

    document.querySelectorAll('[data-clear-all-permissions]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.permission-checkbox').forEach((box) => { box.checked = false; });
        });
    });

    document.querySelectorAll('[data-toggle-module]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const boxes = document.querySelectorAll(`.permission-checkbox[data-module="${btn.dataset.toggleModule}"]`);
            const allChecked = Array.from(boxes).every((box) => box.checked);
            boxes.forEach((box) => { box.checked = ! allChecked; });
        });
    });
</script>
@endpush
