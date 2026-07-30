<?php

namespace Database\Seeders;

use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every permission and role, then applies the role/permission matrix.
 *
 * Safe to re-run: permissions and roles are matched on name, and each role's
 * permission set is synced rather than appended.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        App::make(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Roles::all() as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (Roles::permissionMatrix() as $roleName => $permissions) {
            Role::findByName($roleName, 'web')->syncPermissions(array_unique($permissions));
        }

        // The super administrator is authorised through a Gate::before hook in
        // AuthServiceProvider, so it intentionally holds no explicit grants.
        Role::findByName(Roles::SUPER_ADMIN, 'web')->syncPermissions([]);

        App::make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Seeded '.count(Permissions::all()).' permissions across '.count(Roles::all()).' roles.');
    }
}
