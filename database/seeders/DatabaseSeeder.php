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
            UserSeeder::class,
            SettingsSeeder::class,
            DivisionSeeder::class,
            SliderSeeder::class,
            PortfolioSeeder::class,
            EditorialSeeder::class,
            CompanySeeder::class,
            CareerSeeder::class,
        ]);
    }
}
