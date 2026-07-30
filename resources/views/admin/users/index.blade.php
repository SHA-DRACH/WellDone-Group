@extends('admin.layouts.app')

@section('title', 'Users')
@section('page-subtitle', 'Staff accounts, roles and division assignment.')

@section('page-actions')
    @can('create', App\Models\User::class)
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-user-plus me-2"></i>New account
        </a>
    @endcan
@endsection

@section('content')

    <div class="table-card mb-3">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Name, email or position">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="division" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All divisions</option>
                        @foreach($divisions as $id => $name)
                            <option value="{{ $id }}" @selected(request('division') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" data-auto-submit>
                        <option value="">Any status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Deactivated</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Roles</th>
                        <th>Division</th>
                        <th>Last sign-in</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $user->avatar_url }}" alt="" class="thumb-sm rounded-circle" width="38" height="38">
                                    <div>
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <div class="small text-muted">{{ $user->email }}</div>
                                        @unless($user->hasVerifiedEmail())
                                            <span class="badge bg-warning-subtle text-warning">Email unverified</span>
                                        @endunless
                                    </div>
                                </div>
                            </td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary-subtle text-primary">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td class="small">{{ $user->division?->name ?? '—' }}</td>
                            <td class="small text-muted">
                                {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Deactivated</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @can('update', $user)
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-secondary"
                                           data-bs-toggle="tooltip" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}"
                                              data-confirm="{{ $user->is_active ? 'Deactivate' : 'Reactivate' }} this account?">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-outline-warning" type="submit"
                                                    data-bs-toggle="tooltip" title="{{ $user->is_active ? 'Deactivate' : 'Reactivate' }}">
                                                <i class="fa-solid fa-{{ $user->is_active ? 'user-slash' : 'user-check' }}"></i>
                                            </button>
                                        </form>
                                    @endcan

                                    @can('delete', $user)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              data-confirm="Archive this account? The user will no longer be able to sign in.">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" type="submit"
                                                    data-bs-toggle="tooltip" title="Archive">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fa-solid fa-users"></i>
                                    <p class="mb-0">No accounts match your filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $records->links() }}

@endsection
