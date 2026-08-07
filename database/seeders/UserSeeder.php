<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates one signed-in-ready account per role.
 *
 * These are development credentials. Change every password before the site
 * is deployed — see the README's "First run" section.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'System Administrator',
                'email' => 'superadmin@welldonegroup.com',
                'position' => 'Group ICT Director',
                'department' => 'Executive',
                'role' => Roles::SUPER_ADMIN,
            ],
            [
                'name' => 'Grace Konneh',
                'email' => 'admin@welldonegroup.com',
                'position' => 'Group Administrator',
                'department' => 'Administration',
                'role' => Roles::ADMIN,
            ],
            [
                'name' => 'Samuel Tarpeh',
                'email' => 'content@welldonegroup.com',
                'position' => 'Corporate Communications Lead',
                'department' => 'Communications',
                'role' => Roles::CONTENT_MANAGER,
            ],
            [
                'name' => 'Musu Kollie',
                'email' => 'hr@welldonegroup.com',
                'position' => 'Head of Human Resources',
                'department' => 'Human Resources',
                'role' => Roles::HR_MANAGER,
            ],
            [
                'name' => 'Rebecca Nyanti',
                'email' => 'hrdirector@welldonegroup.com',
                'position' => 'HR Director',
                'department' => 'Human Resources',
                'role' => Roles::HR_DIRECTOR,
            ],
            [
                'name' => 'Korto Wesseh',
                'email' => 'hrofficer@welldonegroup.com',
                'position' => 'HR Officer',
                'department' => 'Human Resources',
                'role' => Roles::HR_OFFICER,
            ],
            [
                'name' => 'J. Wesley Kollie',
                'email' => 'ceo@welldonegroup.com',
                'position' => 'Group Chief Executive Officer',
                'department' => 'Executive',
                'role' => Roles::CEO,
            ],
            [
                'name' => 'Joseph Weah',
                'email' => 'marketing@welldonegroup.com',
                'position' => 'Marketing Officer',
                'department' => 'Marketing',
                'role' => Roles::MARKETING_OFFICER,
            ],
            [
                'name' => 'Fatu Sirleaf',
                'email' => 'sales@welldonegroup.com',
                'position' => 'Business Development Officer',
                'department' => 'Sales',
                'role' => Roles::SALES_OFFICER,
            ],
            [
                'name' => 'Emmanuel Doe',
                'email' => 'ict@welldonegroup.com',
                'position' => 'ICT Systems Manager',
                'department' => 'ICT',
                'role' => Roles::ICT_MANAGER,
            ],
        ];

        // A head for each business division, so every division dashboard has
        // a signed-in owner out of the box. In normal operation an
        // administrator creates these from Admin → Users, which also emails
        // the new user a link to set their own password.
        foreach ($this->divisionHeads() as $head) {
            $accounts[] = $head;
        }

        foreach ($accounts as $account) {
            $role = $account['role'];
            $divisionSlug = $account['division'] ?? null;
            unset($account['role'], $account['division']);

            /*
             * withTrashed matters: accounts are retired rather than removed,
             * so a soft-deleted row keeps its address in the unique index. A
             * plain updateOrCreate cannot see it and collides with the index
             * instead of updating the row.
             */
            $user = User::withTrashed()->firstOrNew(['email' => $account['email']]);

            if ($user->trashed()) {
                $user->restore();
            }

            $user->forceFill(array_merge($account, [
                'password' => Hash::make('WellDone@2026'),
                'email_verified_at' => now(),
                'is_active' => true,
                'division_id' => $divisionSlug
                    ? Division::query()->where('slug', $divisionSlug)->value('id')
                    : null,
            ]))->save();

            $user->syncRoles([$role]);
        }

        $this->command?->info('Seeded '.count($accounts).' staff accounts (password: WellDone@2026).');
    }

    /**
     * The head of each business division.
     *
     * They hold the Division Manager role and carry a division_id, which is
     * what confines them: App\Support\DivisionScope narrows every admin query
     * to that division, and App\Support\Navigation sends them to its
     * dashboard on sign-in. Neither depends on the navigation hiding
     * anything — typing another division's URL is refused with a 403.
     *
     * @return array<int, array<string, string>>
     */
    protected function divisionHeads(): array
    {
        return [
            [
                'name' => 'Prince Tarr',
                'email' => 'mining@welldonegroup.com',
                'position' => 'Head of Mining & Quarry',
                'department' => 'Mining & Quarry',
                'role' => Roles::DIVISION_MANAGER,
                'division' => 'mining-quarry',
            ],
            [
                'name' => 'Miatta Freeman',
                'email' => 'logistics@welldonegroup.com',
                'position' => 'Head of Procurement & Logistics',
                'department' => 'Procurement & Logistics',
                'role' => Roles::DIVISION_MANAGER,
                'division' => 'procurement-logistics',
            ],
            [
                'name' => 'Yassah Toe',
                'email' => 'medical@welldonegroup.com',
                'position' => 'Head of Medical Equipment & Supplies',
                'department' => 'Medical Equipment',
                'role' => Roles::DIVISION_MANAGER,
                'division' => 'medical-equipment',
            ],
            [
                'name' => 'Nathan Gbeh',
                'email' => 'ictdesk@welldonegroup.com',
                'position' => 'Head of ICT Services',
                'department' => 'ICT Services',
                'role' => Roles::DIVISION_MANAGER,
                'division' => 'ict-services',
            ],
        ];
    }
}
