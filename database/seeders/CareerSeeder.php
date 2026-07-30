<?php

namespace Database\Seeders;

use App\Models\Career;
use App\Models\Division;
use Illuminate\Database\Seeder;

class CareerSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::query()->pluck('id', 'slug');

        $vacancies = [
            [
                'title' => 'Quarry Operations Supervisor',
                'division' => 'mining-quarry',
                'department' => 'Operations',
                'location' => 'Margibi County',
                'employment_type' => 'full_time',
                'experience_level' => 'senior',
                'salary_range' => 'Competitive, commensurate with experience',
                'positions_available' => 1,
                'closes_at' => now()->addDays(30)->toDateString(),
                'is_featured' => true,
                'description' => '<p>We are seeking an experienced Quarry Operations Supervisor to take day-to-day responsibility for production, safety and compliance at an active aggregate site.</p>',
                'responsibilities' => '<ul><li>Plan and supervise daily extraction, crushing and screening operations</li><li>Enforce the site safety management system and lead pre-task risk assessment</li><li>Coordinate blasting programmes with certified shot-firers</li><li>Manage stockpile levels against the despatch schedule</li><li>Maintain production, plant and compliance records</li></ul>',
                'requirements' => '<ul><li>Degree or diploma in mining engineering, geology or a related discipline</li><li>Five or more years in quarry or open-pit operations, including supervisory responsibility</li><li>Working knowledge of Liberian environmental and mining regulations</li><li>Demonstrable safety leadership</li><li>Valid driver\'s licence</li></ul>',
                'benefits' => '<ul><li>Site accommodation and transport</li><li>Medical cover</li><li>Structured training and certification support</li></ul>',
            ],
            [
                'title' => 'Biomedical Equipment Technician',
                'division' => 'medical-equipment',
                'department' => 'Technical Services',
                'location' => 'Monrovia (with nationwide travel)',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'salary_range' => 'Competitive',
                'positions_available' => 2,
                'closes_at' => now()->addDays(25)->toDateString(),
                'is_featured' => true,
                'description' => '<p>Join our biomedical team installing, commissioning and maintaining hospital and laboratory equipment across client facilities nationwide.</p>',
                'responsibilities' => '<ul><li>Install and commission medical and laboratory equipment</li><li>Carry out acceptance testing against manufacturer specification</li><li>Deliver preventive maintenance and calibration to schedule</li><li>Diagnose and repair faults, escalating to manufacturer support where needed</li><li>Train facility operators and complete handover documentation</li></ul>',
                'requirements' => '<ul><li>Diploma or degree in biomedical engineering, electronics or electrical engineering</li><li>Three or more years maintaining clinical or laboratory equipment</li><li>Ability to read technical schematics and service manuals</li><li>Willingness to travel to county facilities</li></ul>',
                'benefits' => '<ul><li>Manufacturer training on supported platforms</li><li>Medical cover</li><li>Travel allowance</li></ul>',
            ],
            [
                'title' => 'Full-Stack Software Developer (Laravel)',
                'division' => 'ict-services',
                'department' => 'Software Engineering',
                'location' => 'Monrovia',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'salary_range' => 'Competitive',
                'positions_available' => 2,
                'closes_at' => now()->addDays(35)->toDateString(),
                'is_featured' => true,
                'description' => '<p>Build and maintain the management information systems our clients run their operations on — school systems, hospital systems, ERP modules and public-facing platforms.</p>',
                'responsibilities' => '<ul><li>Develop and maintain Laravel applications from specification through deployment</li><li>Design normalised database schemas and write efficient queries</li><li>Build responsive interfaces with Blade and Bootstrap</li><li>Write tests and participate in code review</li><li>Support deployed systems and respond to production issues</li></ul>',
                'requirements' => '<ul><li>Three or more years building PHP applications, ideally with Laravel</li><li>Strong SQL and relational modelling</li><li>Competent with HTML, CSS, JavaScript and Git</li><li>Experience with REST APIs and third-party integration</li><li>Clear written communication</li></ul>',
                'benefits' => '<ul><li>Professional development budget</li><li>Medical cover</li><li>Flexible working arrangements</li></ul>',
            ],
            [
                'title' => 'Customs Clearance Officer',
                'division' => 'procurement-logistics',
                'department' => 'Clearance',
                'location' => 'Freeport of Monrovia',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'positions_available' => 1,
                'closes_at' => now()->addDays(20)->toDateString(),
                'description' => '<p>Manage import and export declarations and shepherd client consignments through clearance at the Freeport and Roberts International Airport.</p>',
                'responsibilities' => '<ul><li>Prepare and lodge import and export declarations</li><li>Classify goods and assess duty and tariff liability</li><li>Coordinate inspections and resolve queries with Customs</li><li>Process exemption and waiver applications</li><li>Keep clients updated on consignment status</li></ul>',
                'requirements' => '<ul><li>Three or more years in customs brokerage or freight forwarding in Liberia</li><li>Working knowledge of the harmonised tariff schedule</li><li>Established familiarity with Freeport clearance procedures</li><li>Meticulous documentation discipline</li></ul>',
            ],
            [
                'title' => 'Heavy Plant Operator',
                'division' => 'mining-quarry',
                'department' => 'Operations',
                'location' => 'Margibi & Bong Counties',
                'employment_type' => 'contract',
                'experience_level' => 'junior',
                'positions_available' => 4,
                'closes_at' => now()->addDays(15)->toDateString(),
                'description' => '<p>Operate excavators, wheel loaders and articulated haulers on active quarry sites.</p>',
                'responsibilities' => '<ul><li>Operate assigned plant safely and productively</li><li>Complete daily pre-start inspection checks</li><li>Report defects and maintenance needs promptly</li><li>Comply with all site safety procedures</li></ul>',
                'requirements' => '<ul><li>Certified plant operator with two or more years on excavators or loaders</li><li>Valid driver\'s licence</li><li>Sound understanding of site safety practice</li></ul>',
            ],
            [
                'title' => 'ICT Internship Programme',
                'division' => 'ict-services',
                'department' => 'ICT',
                'location' => 'Monrovia',
                'employment_type' => 'internship',
                'experience_level' => 'entry',
                'positions_available' => 6,
                'closes_at' => now()->addDays(45)->toDateString(),
                'description' => '<p>A six-month structured internship for recent graduates and final-year students in software development, networking or cybersecurity.</p>',
                'responsibilities' => '<ul><li>Work alongside engineers on live client projects</li><li>Complete a structured learning path with assigned mentorship</li><li>Contribute to documentation and testing</li></ul>',
                'requirements' => '<ul><li>Final-year student or recent graduate in computer science, IT or a related field</li><li>Demonstrable interest through coursework or personal projects</li><li>Willingness to learn and take direction</li></ul>',
                'benefits' => '<ul><li>Monthly stipend</li><li>Mentorship and structured training</li><li>Strong performers are considered for permanent roles</li></ul>',
            ],
            [
                'title' => 'Procurement Officer',
                'division' => 'procurement-logistics',
                'department' => 'Procurement',
                'location' => 'Monrovia',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'positions_available' => 1,
                'closes_at' => now()->addDays(28)->toDateString(),
                'description' => '<p>Run competitive sourcing processes for client requirements, from specification through supplier recommendation.</p>',
                'responsibilities' => '<ul><li>Develop written specifications from client requirements</li><li>Run competitive supplier tenders and evaluate responses</li><li>Conduct supplier due diligence</li><li>Prepare documented recommendations and purchase orders</li><li>Track orders through to delivery</li></ul>',
                'requirements' => '<ul><li>Degree in procurement, supply chain management, business or a related field</li><li>Three or more years in a procurement role</li><li>Strong negotiation and documentation skills</li><li>Proficiency with spreadsheets and procurement systems</li></ul>',
            ],
        ];

        foreach ($vacancies as $index => $data) {
            $divisionSlug = $data['division'];
            unset($data['division']);

            Career::query()->updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, [
                    'division_id' => $divisions[$divisionSlug] ?? null,
                    'opens_at' => now()->subDays(7)->toDateString(),
                    'is_active' => true,
                ])
            );
        }

        $this->command?->info('Seeded '.count($vacancies).' vacancies.');
    }
}
