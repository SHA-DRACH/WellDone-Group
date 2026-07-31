@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')
@section('page-subtitle', 'What each role may do. Built-in roles cannot be renamed or deleted.')

@section('page-actions')
    @can('create', Spatie\Permission\Models\Role::class)
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>New role
        </a>
    @endcan
@endsection

@section('content')

    <div class="alert alert-info small d-flex gap-2">
        <i class="fa-solid fa-circle-info mt-1"></i>
        <div>
            The <strong>Super Administrator</strong> role is authorised for everything through a
            code-level rule, not a permission list — so new permissions reach it automatically and
            its grants cannot be edited here.
        </div>
    </div>

    <div class="table-card mb-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th>Members</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $role)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $role->name }}</div>
                                @if(in_array($role->name, $protected, true))
                                    <span class="badge bg-secondary-subtle text-secondary">Built-in</span>
                                @endif
                            </td>
                            <td>
                                @if($role->name === App\Support\Roles::SUPER_ADMIN)
                                    <span class="badge bg-primary-subtle text-primary">All permissions</span>
                                @else
                                    {{ $role->permissions_count }}
                                @endif
                            </td>
                            <td>{{ $role->users_count }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @can('update', $role)
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-outline-secondary"
                                           data-bs-toggle="tooltip" title="Edit permissions">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan

                                    @can('delete', $role)
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                              data-confirm="Delete the {{ $role->name }} role?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" type="submit"
                                                    data-bs-toggle="tooltip" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{ $records->links() }}

@endsection
