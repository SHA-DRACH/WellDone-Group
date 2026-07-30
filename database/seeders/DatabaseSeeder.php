<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Full install seed.
 *
 * Order matters: access control and settings first, then the corporate
 * structure, then the content that references divisions.
 *
 * Every seeder is idempotent, so this may be re-run on an existing database
 * without duplicating rows or overwriting edited content.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            // Divisions before users: the division officers are assigned to
            // a division by slug as they are created.
            DivisionSeeder::class,
            UserSeeder::class,
            SliderSeeder::class,
            PortfolioSeeder::class,
            EditorialSeeder::class,
            CompanySeeder::class,
            CareerSeeder::class,
        ]);
    }
}
