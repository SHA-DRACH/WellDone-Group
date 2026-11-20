<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use App\Notifications\StaffAccountCreated;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Staff sign-in accounts for the leadership team in the Company Profile.
 *
 * On a live install (any environment other than local/testing) each new
 * account is emailed a "Choose your password" link as it is created — no
 * password is ever sent by email. See docs/DEPLOYMENT.md.
 */
class UserSeeder extends Seeder
{
    /**
     * Placeholder accounts shipped by earlier versions of this seeder. They
     * are removed wherever they still exist.
     */
    public const RETIRED_PLACEHOLDERS = [
        'superadmin@welldonegroup.com', 'admin@welldonegroup.com', 'content@welldonegroup.com',
        'hr@welldonegroup.com', 'hrdirector@welldonegroup.com', 'hrofficer@welldonegroup.com',
        'ceo@welldonegroup.com', 'marketing@welldonegroup.com', 'sales@welldonegroup.com',
        'ict@welldonegroup.com', 'mining@welldonegroup.com', 'logistics@welldonegroup.com',
        'medical@welldonegroup.com', 'ictdesk@welldonegroup.com',
    ];

    public function run(): void
    {
        // Remove the placeholder accounts earlier versions of this seeder
        // created. Soft-deleted, so anything they authored keeps its history.
        User::query()->whereIn('email', self::RETIRED_PLACEHOLDERS)->get()->each->delete();

        $accounts = $this->leaders();

        /*
         * Local and testing installs share one known password so developers
         * can sign in straight away. Anywhere else — staging, production —
         * that password is public (it is in this file), so each account gets
         * SEED_STAFF_PASSWORD if set, otherwise its own random password, and
         * must choose a new one at first sign-in.
         */
        $developmentInstall = app()->environment('local', 'testing');
        $created = [];

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

            $attributes = array_merge($account, [
                'is_active' => true,
                'division_id' => $divisionSlug
                    ? Division::query()->where('slug', $divisionSlug)->value('id')
                    : null,
            ]);

            // Passwords are only set when the account is first created, so
            // re-running the seeder never resets a password someone has
            // already changed.
            if (! $user->exists) {
                $password = $developmentInstall
                    ? 'WellDone@2026'
                    : (env('SEED_STAFF_PASSWORD') ?: Str::password(16));

                $attributes += [
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'must_change_password' => ! $developmentInstall,
                ];

                $created[$account['email']] = $password;
            }

            $isNew = ! $user->exists;

            $user->forceFill($attributes)->save();

            $user->syncRoles([$role]);

            // On a live install, tell each new person their account is ready.
            // The email carries a "Choose your password" link, never a
            // password. Queued: a queue worker must be running to send it.
            if ($isNew && ! $developmentInstall) {
                $user->notify(new StaffAccountCreated($user->load('division')));
            }
        }

        if ($developmentInstall) {
            $this->command?->info('Seeded '.count($accounts).' staff accounts (development password: WellDone@2026).');

            return;
        }

        $this->command?->info('Seeded '.count($accounts).' staff accounts; '.count($created).' new.');

        if ($created !== [] && ! env('SEED_STAFF_PASSWORD')) {
            // Shown once, here only. Each user must change it at first sign-in.
            $this->command?->table(['Email', 'Temporary password'],
                collect($created)->map(fn ($p, $e) => [$e, $p])->values()->all());
        }
    }

    /**
     * The leadership team from the Company Profile, as sign-in accounts.
     *
     * Roles: Super Administrator, Administrator and CEO see every division
     * even when an account names one. A Division Manager is confined to their
     * division — App\Support\DivisionScope narrows every admin query to it and
     * App\Support\Navigation sends them to its dashboard on sign-in.
     *
     * EMAIL ADDRESSES: the profile does not list personal addresses, so these
     * use the company website's domain. Replace each with the person's real
     * address before deploying — the welcome email goes there.
     *
     * @return array<int, array<string, string>>
     */
    protected function leaders(): array
    {
        return [
            [
                'name' => 'Gbogbah Matthew Tarr',
                'email' => 'gbogbah.tarr@welldonegroupinc.com',
                'position' => 'Founder & Chief Executive Officer',
                'department' => 'Executive',
                'avatar' => '/images/team/gbogbah-matthew-tarr.jpg',
                'bio' => 'Founder and CEO of WellDone Group Inc., with over 29 years of leadership in finance, database development, ICT, logistics, procurement and programme management.',
                'role' => Roles::CEO,
            ],
            [
                'name' => 'Garjay Jestin Tarr',
                'email' => 'garjay.tarr@welldonegroupinc.com',
                'position' => 'Co-Founder & Managing Director',
                'department' => 'Executive',
                'avatar' => '/images/team/garjay-jestin-tarr.jpg',
                'bio' => 'Managing Director with more than 16 years in public-sector administration, program support and project management with the U.S. Department of State and USAID.',
                'role' => Roles::ADMIN,
            ],
            [
                'name' => 'Shadrach Jimice Jr.',
                'email' => 'shadrach.jimice@welldonegroupinc.com',
                'position' => 'Senior Software Engineer & Director of ICT Services',
                'department' => 'ICT',
                'avatar' => '/images/team/shadrach-jimice-jr.jpg',
                'bio' => 'Software engineer providing technology leadership across software development, digital transformation and IT systems.',
                'role' => Roles::SUPER_ADMIN,
                'division' => 'ict-services',
            ],
            [
                'name' => 'Philip Saywray Gongar',
                'email' => 'philip.gongar@welldonegroupinc.com',
                'position' => 'Director of Strategy, Business Development, Innovation & Research',
                'department' => 'Strategy',
                'avatar' => '/images/team/philip-saywray-gongar.jpg',
                'bio' => 'Management professional with 10 years in human resource management, administration, project coordination and executive support.',
                'role' => Roles::ADMIN,
            ],
            [
                'name' => 'Stephen V. Kollie',
                'email' => 'stephen.kollie@welldonegroupinc.com',
                'position' => 'Logistics, Procurement & General Trading Director',
                'department' => 'Logistics & Procurement',
                'avatar' => '/images/team/stephen-v-kollie.jpg',
                'bio' => 'Supply chain specialist leading procurement, logistics and bidding.',
                'role' => Roles::DIVISION_MANAGER,
                'division' => 'procurement-logistics',
            ],
            [
                'name' => 'William Lee Davis',
                'email' => 'william.davis@welldonegroupinc.com',
                'position' => 'Director of Medical Supplies & Healthcare Services',
                'department' => 'Medical Supply',
                'avatar' => '/images/team/william-lee-davis.jpg',
                'bio' => 'Chemical engineering and pharmaceutical research specialist with an MSc in Pharmaceutical Engineering.',
                'role' => Roles::DIVISION_MANAGER,
                'division' => 'medical-equipment',
            ],
        ];
    }
}
