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

        // One officer per business division, so each division dashboard has a
        // signed-in owner out of the box. In normal operation an
        // administrator creates these from Admin → Users, which also emails
        // the new user a link to set their own password.
        foreach ($this->divisionOfficers() as $officer) {
            $accounts[] = $officer;
        }

        foreach ($accounts as $account) {
            $role = $account['role'];
            $divisionSlug = $account['division'] ?? null;
            unset($account['role'], $account['division']);

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                array_merge($account, [
                    'password' => Hash::make('WellDone@2026'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'division_id' => $divisionSlug
                        ? Division::query()->where('slug', $divisionSlug)->value('id')
                        : null,
                ])
            );

            $user->syncRoles([$role]);
        }

        $this->command?->info('Seeded '.count($accounts).' staff accounts (password: WellDone@2026).');
    }

    /**
     * Division-scoped accounts. These users hold no group-level role, so
     * App\Support\Navigation routes them straight to their own division
     * dashboard on sign-in and denies them any other division.
     *
     * @return array<int, array<string, string>>
     */
    protected function divisionOfficers(): array
    {
        return [
            [
                'name' => 'Prince Tarr',
                'email' => 'mining@welldonegroup.com',
                'position' => 'Mining & Quarry Operations Officer',
                'department' => 'Mining',
                'role' => Roles::SALES_OFFICER,
                'division' => 'mining-quarry',
            ],
            [
                'name' => 'Miatta Freeman',
                'email' => 'logistics@welldonegroup.com',
                'position' => 'Procurement & Logistics Officer',
                'department' => 'Logistics',
                'role' => Roles::SALES_OFFICER,
                'division' => 'procurement-logistics',
            ],
            [
                'name' => 'Yassah Toe',
                'email' => 'medical@welldonegroup.com',
                'position' => 'Medical Equipment Officer',
                'department' => 'Medical',
                'role' => Roles::SALES_OFFICER,
                'division' => 'medical-equipment',
            ],
            [
                'name' => 'Nathan Gbeh',
                'email' => 'ictdesk@welldonegroup.com',
                'position' => 'ICT Services Officer',
                'department' => 'ICT',
                'role' => Roles::SALES_OFFICER,
                'division' => 'ict-services',
            ],
        ];
    }
}
