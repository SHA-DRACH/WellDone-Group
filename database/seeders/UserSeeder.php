<?php

namespace Database\Seeders;

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

        foreach ($accounts as $account) {
            $role = $account['role'];
            unset($account['role']);

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                array_merge($account, [
                    'password' => Hash::make('WellDone@2026'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ])
            );

            $user->syncRoles([$role]);
        }

        $this->command?->info('Seeded '.count($accounts).' staff accounts (password: WellDone@2026).');
    }
}
