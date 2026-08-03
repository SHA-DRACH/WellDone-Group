<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\EmploymentType;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * The organisational structure: departments, positions and employment types.
 *
 * Departments that correspond to a public business division are linked to it,
 * so ICT Services exists once as a division on the website and once as the
 * department its people report into.
 */
class HrStructureSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::query()->pluck('id', 'slug');

        foreach ($this->departments() as $index => $data) {
            $divisionSlug = $data['division'] ?? null;
            unset($data['division']);

            Department::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'division_id' => $divisionSlug ? ($divisions[$divisionSlug] ?? null) : null,
                    'sort_order' => $index,
                    'is_active' => true,
                ])
            );
        }

        $departments = Department::query()->pluck('id', 'slug');

        foreach ($this->positions() as $data) {
            $departmentSlug = $data['department'] ?? null;
            unset($data['department']);

            Position::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'department_id' => $departmentSlug ? ($departments[$departmentSlug] ?? null) : null,
                    'is_active' => true,
                ])
            );
        }

        foreach ($this->employmentTypes() as $index => $data) {
            EmploymentType::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $this->command?->info(sprintf(
            'Seeded %d departments, %d positions and %d employment types.',
            Department::count(),
            Position::count(),
            EmploymentType::count()
        ));
    }

    /** @return array<int, array<string, mixed>> */
    protected function departments(): array
    {
        return [
            ['name' => 'Executive Office', 'slug' => 'executive-office', 'code' => 'EXE',
                'description' => 'Group leadership, strategy and governance.'],

            ['name' => 'Human Resources', 'slug' => 'human-resources', 'code' => 'HR',
                'description' => 'Recruitment, employee relations, learning and development, and workplace policy across the group.'],

            ['name' => 'Finance', 'slug' => 'finance', 'code' => 'FIN',
                'description' => 'Accounting, treasury, budgeting, payroll and statutory reporting.'],

            ['name' => 'Administration', 'slug' => 'administration', 'code' => 'ADM',
                'description' => 'Office management, facilities, records and general administrative support.'],

            ['name' => 'Marketing', 'slug' => 'marketing', 'code' => 'MKT',
                'description' => 'Brand, communications, business development and the corporate website.'],

            // Departments that mirror a public business division.
            ['name' => 'ICT Services', 'slug' => 'ict-services-dept', 'code' => 'ICT',
                'division' => 'ict-services',
                'description' => 'Software engineering, infrastructure, cybersecurity and technical support.'],

            ['name' => 'Procurement & Logistics', 'slug' => 'procurement-logistics-dept', 'code' => 'PLG',
                'division' => 'procurement-logistics',
                'description' => 'Sourcing, freight, customs clearance, warehousing and distribution.'],

            ['name' => 'Mining & Quarry', 'slug' => 'mining-quarry-dept', 'code' => 'MNQ',
                'division' => 'mining-quarry',
                'description' => 'Extraction, aggregate production, plant operations and site rehabilitation.'],

            ['name' => 'Medical Equipment & Supplies', 'slug' => 'medical-equipment-dept', 'code' => 'MED',
                'division' => 'medical-equipment',
                'description' => 'Equipment sourcing, installation, commissioning and biomedical maintenance.'],
        ];
    }

    /**
     * Positions, with `grade` as the seniority band — 1 is most senior, so an
     * ascending sort produces a sensible org chart.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function positions(): array
    {
        return [
            ['title' => 'Chief Executive Officer', 'slug' => 'chief-executive-officer', 'grade' => 1, 'department' => 'executive-office'],
            ['title' => 'Chief Operating Officer', 'slug' => 'chief-operating-officer', 'grade' => 1, 'department' => 'executive-office'],
            ['title' => 'Chief Financial Officer', 'slug' => 'chief-financial-officer', 'grade' => 1, 'department' => 'finance'],

            ['title' => 'Director', 'slug' => 'director', 'grade' => 2],
            ['title' => 'HR Director', 'slug' => 'hr-director', 'grade' => 2, 'department' => 'human-resources'],

            ['title' => 'Manager', 'slug' => 'manager', 'grade' => 3],
            ['title' => 'HR Manager', 'slug' => 'hr-manager', 'grade' => 3, 'department' => 'human-resources'],
            ['title' => 'Operations Manager', 'slug' => 'operations-manager', 'grade' => 3],
            ['title' => 'ICT Systems Manager', 'slug' => 'ict-systems-manager', 'grade' => 3, 'department' => 'ict-services-dept'],

            ['title' => 'Supervisor', 'slug' => 'supervisor', 'grade' => 4],
            ['title' => 'Quarry Operations Supervisor', 'slug' => 'quarry-operations-supervisor', 'grade' => 4, 'department' => 'mining-quarry-dept'],

            ['title' => 'HR Officer', 'slug' => 'hr-officer', 'grade' => 5, 'department' => 'human-resources'],
            ['title' => 'Accountant', 'slug' => 'accountant', 'grade' => 5, 'department' => 'finance'],
            ['title' => 'Procurement Officer', 'slug' => 'procurement-officer', 'grade' => 5, 'department' => 'procurement-logistics-dept'],
            ['title' => 'Customs Clearance Officer', 'slug' => 'customs-clearance-officer', 'grade' => 5, 'department' => 'procurement-logistics-dept'],
            ['title' => 'Sales Executive', 'slug' => 'sales-executive', 'grade' => 5, 'department' => 'marketing'],
            ['title' => 'Administrative Officer', 'slug' => 'administrative-officer', 'grade' => 5, 'department' => 'administration'],

            ['title' => 'Software Engineer', 'slug' => 'software-engineer', 'grade' => 5, 'department' => 'ict-services-dept'],
            ['title' => 'Network Engineer', 'slug' => 'network-engineer', 'grade' => 5, 'department' => 'ict-services-dept'],
            ['title' => 'Biomedical Equipment Technician', 'slug' => 'biomedical-equipment-technician', 'grade' => 5, 'department' => 'medical-equipment-dept'],

            ['title' => 'Technician', 'slug' => 'technician', 'grade' => 6],
            ['title' => 'Heavy Plant Operator', 'slug' => 'heavy-plant-operator', 'grade' => 6, 'department' => 'mining-quarry-dept'],
            ['title' => 'Driver', 'slug' => 'driver', 'grade' => 6, 'department' => 'procurement-logistics-dept'],
            ['title' => 'Intern', 'slug' => 'intern', 'grade' => 6],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function employmentTypes(): array
    {
        return [
            ['name' => 'Full Time', 'slug' => 'full-time', 'is_permanent' => true, 'accrues_leave' => true, 'probation_months' => 3,
                'description' => 'Permanent salaried staff on the standard working calendar.'],
            ['name' => 'Part Time', 'slug' => 'part-time', 'is_permanent' => true, 'accrues_leave' => true, 'probation_months' => 3,
                'description' => 'Permanent staff on reduced hours; leave accrues pro rata.'],
            ['name' => 'Contract', 'slug' => 'contract', 'is_permanent' => false, 'accrues_leave' => true, 'probation_months' => 1,
                'description' => 'Fixed-term engagement with a defined end date.'],
            ['name' => 'Temporary', 'slug' => 'temporary', 'is_permanent' => false, 'accrues_leave' => false, 'probation_months' => 0,
                'description' => 'Short-term cover or seasonal work.'],
            ['name' => 'Internship', 'slug' => 'internship', 'is_permanent' => false, 'accrues_leave' => false, 'probation_months' => 0,
                'description' => 'Structured placement for students and recent graduates.'],
            ['name' => 'Consultant', 'slug' => 'consultant', 'is_permanent' => false, 'accrues_leave' => false, 'probation_months' => 0,
                'description' => 'Engaged for a defined scope of work rather than a post.'],
        ];
    }
}
