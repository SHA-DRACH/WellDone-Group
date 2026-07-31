<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role and permission management.
 *
 * Only a super administrator holds `roles.*`, so in practice this module is
 * theirs alone. The built-in roles cannot be renamed or deleted, and the
 * super administrator role's grants are fixed — it is authorised through a
 * Gate::before hook rather than a permission list.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', [
            'records' => Role::query()
                ->withCount(['users', 'permissions'])
                ->orderBy('name')
                ->paginate(20),
            'protected' => Roles::all(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', [
            'record' => new Role,
            'grouped' => Permissions::grouped(),
            'assigned' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $request->validate($this->rules(null));

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($this->allowedPermissions($request));

        $this->flushPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.roles.edit', [
            'record' => $role,
            'grouped' => Permissions::grouped(),
            'assigned' => $role->permissions->pluck('name')->all(),
            'isProtected' => in_array($role->name, Roles::all(), true),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $request->validate($this->rules($role));

        // Built-in roles keep their names — code and seeders reference them.
        if (! in_array($role->name, Roles::all(), true)) {
            $role->update(['name' => $data['name']]);
        }

        $role->syncPermissions($this->allowedPermissions($request));

        $this->flushPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        $this->flushPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    // ------------------------------------------------------------------

    protected function rules(?Role $role): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permissions::all())],
        ];
    }

    /**
     * Never grant a permission that is not in the application's catalogue,
     * whatever the form posts.
     */
    protected function allowedPermissions(Request $request): array
    {
        $requested = (array) $request->input('permissions', []);

        $valid = array_values(array_intersect($requested, Permissions::all()));

        // Make sure every granted name exists as a Permission row.
        foreach ($valid as $name) {
            Permission::findOrCreate($name, 'web');
        }

        return $valid;
    }

    protected function flushPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
