<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CsrInitiative;
use App\Models\Division;
use App\Models\Download;
use App\Models\Faq;
use App\Models\Milestone;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;

/**
 * About Us supporting content: leadership, milestones, accreditations,
 * CSR initiatives, FAQs and downloadable documents.
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::query()->pluck('id', 'slug');

        $leadership = [
            [
                'name' => 'J. Wesley Kollie',
                'position' => 'Group Chief Executive Officer',
                'department' => 'Executive',
                'short_bio' => 'Founder of WellDone Group, with two decades across construction supply, logistics and public infrastructure delivery in Liberia.',
                'bio' => '<p>Wesley founded WellDone Group and leads group strategy and governance. Their background spans construction materials supply, freight and clearance operations, and delivery of public infrastructure contracts across Liberia.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Rebecca T. Nyanti',
                'position' => 'Group Chief Operating Officer',
                'department' => 'Executive',
                'short_bio' => 'Responsible for operational performance across all four divisions, with a background in supply chain and programme management.',
                'bio' => '<p>Rebecca oversees operational delivery across the group, holding each division to common standards for safety, compliance and client reporting.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Emmanuel S. Doe',
                'position' => 'Director, ICT Services',
                'department' => 'ICT',
                'division' => 'ict-services',
                'short_bio' => 'Leads software delivery, infrastructure and cybersecurity practice for public and private sector clients.',
                'bio' => '<p>Emmanuel heads the ICT division, covering software engineering, network and security infrastructure, and managed services.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Dr. Fatu B. Massaquoi',
                'position' => 'Director, Medical Equipment & Supplies',
                'department' => 'Medical',
                'division' => 'medical-equipment',
                'short_bio' => 'Biomedical engineer leading equipment sourcing, commissioning and maintenance across the installed base.',
                'bio' => '<p>Fatu leads the medical division, with responsibility for manufacturer selection, regulatory compliance, commissioning standards and the maintenance programme.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Prince G. Tarr',
                'position' => 'Director, Mining & Quarry',
                'department' => 'Mining',
                'division' => 'mining-quarry',
                'short_bio' => 'Mining engineer responsible for extraction planning, production and environmental compliance.',
                'bio' => '<p>Prince directs quarry and mineral operations, covering extraction planning, plant productivity, blasting compliance and site rehabilitation.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Miatta G. Freeman',
                'position' => 'Director, Procurement & Logistics',
                'department' => 'Logistics',
                'division' => 'procurement-logistics',
                'short_bio' => 'Leads sourcing, freight, customs brokerage and distribution operations.',
                'bio' => '<p>Miatta directs the procurement and logistics division, including supplier management, freight coordination, customs compliance and warehousing.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Musu K. Kollie',
                'position' => 'Head of Human Resources',
                'department' => 'Human Resources',
                'short_bio' => 'Responsible for recruitment, learning and development, and workplace policy across the group.',
                'is_leadership' => false,
            ],
            [
                'name' => 'Alfred N. Gbeh',
                'position' => 'Group Health, Safety & Environment Manager',
                'department' => 'HSE',
                'short_bio' => 'Owns the group safety management system and environmental compliance programme.',
                'is_leadership' => false,
            ],
        ];

        foreach ($leadership as $index => $member) {
            $divisionSlug = $member['division'] ?? null;
            unset($member['division']);

            TeamMember::query()->updateOrCreate(
                ['name' => $member['name']],
                array_merge($member, [
                    'division_id' => $divisionSlug ? ($divisions[$divisionSlug] ?? null) : null,
                    'sort_order' => $index,
                    'is_active' => true,
                ])
            );
        }

        $milestones = [
            ['year' => '2014', 'title' => 'WellDone Group incorporated', 'icon' => 'fa-solid fa-flag', 'description' => 'The company is registered in Monrovia, beginning as a construction materials supply and haulage operation.'],
            ['year' => '2016', 'title' => 'Procurement & Logistics division formed', 'icon' => 'fa-solid fa-truck-fast', 'description' => 'Freight forwarding and customs brokerage capability is established to support client import requirements end to end.'],
            ['year' => '2018', 'title' => 'First licensed quarry site commissioned', 'icon' => 'fa-solid fa-mountain', 'description' => 'Aggregate production begins under a full environmental management plan, supplying road and building contractors.'],
            ['year' => '2020', 'title' => 'Medical Equipment division launched', 'icon' => 'fa-solid fa-stethoscope', 'description' => 'The group begins supplying and commissioning hospital and laboratory equipment, with in-house biomedical technicians.'],
            ['year' => '2022', 'title' => 'ICT Services division launched', 'icon' => 'fa-solid fa-microchip', 'description' => 'Software development, network and security engineering capability is brought in-house for public and private clients.'],
            ['year' => '2024', 'title' => 'Bonded warehouse capacity commissioned', 'icon' => 'fa-solid fa-warehouse', 'description' => 'Expanded bonded and temperature-controlled storage shortens clearance-to-delivery times for client consignments.'],
            ['year' => '2026', 'title' => 'Four divisions, nationwide delivery', 'icon' => 'fa-solid fa-globe', 'description' => 'The group now delivers across all fifteen counties with over 310 people employed.'],
        ];

        foreach ($milestones as $index => $milestone) {
            Milestone::query()->updateOrCreate(
                ['year' => $milestone['year'], 'title' => $milestone['title']],
                array_merge($milestone, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $certificates = [
            ['title' => 'Certificate of Incorporation', 'issuer' => 'Liberia Business Registry', 'description' => 'Registration of WellDone Group Incorporated as a Liberian corporation.'],
            ['title' => 'Business Registration Certificate', 'issuer' => 'Liberia Business Registry', 'description' => 'Annual business registration and good standing.'],
            ['title' => 'Tax Clearance Certificate', 'issuer' => 'Liberia Revenue Authority', 'description' => 'Current tax compliance certification.'],
            ['title' => 'Environmental Permit — Quarry Operations', 'issuer' => 'Environmental Protection Agency of Liberia', 'description' => 'Permit covering extraction and aggregate production under an approved environmental management plan.'],
            ['title' => 'Customs Brokerage Licence', 'issuer' => 'Liberia Revenue Authority — Customs', 'description' => 'Licence to act as a customs broker for import and export declarations.'],
            ['title' => 'Medical Device Import Authorisation', 'issuer' => 'Liberia Medicines & Health Products Regulatory Authority', 'description' => 'Authorisation to import and distribute medical devices and equipment.'],
        ];

        foreach ($certificates as $index => $certificate) {
            Certificate::query()->updateOrCreate(
                ['title' => $certificate['title']],
                array_merge($certificate, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $csr = [
            [
                'title' => 'Host Community Scholarship Programme',
                'focus_area' => 'Education',
                'year' => 2025,
                'location' => 'Margibi, Bong & Grand Cape Mount Counties',
                'summary' => 'Secondary and vocational scholarships for students in communities adjacent to our operations.',
                'description' => '<p>The programme funds fees, materials and examination costs for secondary students, alongside vocational places in welding, plant operation, electrical installation and biomedical technician skills — trades our own divisions recruit for.</p><p>Awards are made by a committee including community representatives, on published criteria of academic performance and financial need.</p>',
            ],
            [
                'title' => 'Rural Health Facility Equipment Donations',
                'focus_area' => 'Health',
                'year' => 2025,
                'location' => 'Bong & Nimba Counties',
                'summary' => 'Donation and commissioning of essential equipment to under-resourced rural clinics.',
                'description' => '<p>Working with county health teams, the group donates, installs and commissions essential clinical equipment at rural facilities, including operator training and inclusion in our maintenance rounds.</p>',
            ],
            [
                'title' => 'Quarry Site Reforestation',
                'focus_area' => 'Environment',
                'year' => 2024,
                'location' => 'Grand Cape Mount County',
                'summary' => 'Progressive revegetation of worked-out quarry areas with indigenous species.',
                'description' => '<p>Worked-out areas are reprofiled, topsoiled and replanted with indigenous species as extraction advances, rather than waiting for site closure. Survival rates are monitored across three growing seasons.</p>',
            ],
            [
                'title' => 'Local Employment & Skills Transfer',
                'focus_area' => 'Livelihoods',
                'year' => 2026,
                'location' => 'Nationwide',
                'summary' => 'Priority recruitment from host communities, with structured on-the-job skills transfer.',
                'description' => '<p>Operational roles are advertised in host communities first, and paired with structured on-the-job training so that recruitment translates into transferable qualification rather than temporary work.</p>',
            ],
            [
                'title' => 'Community Water Point Rehabilitation',
                'focus_area' => 'Infrastructure',
                'year' => 2024,
                'location' => 'Margibi County',
                'summary' => 'Repair and protection of hand pumps and water points in communities near quarry operations.',
                'description' => '<p>Hand pumps and water points in host communities are surveyed, repaired and protected, with local caretakers trained and equipped for routine maintenance.</p>',
            ],
        ];

        foreach ($csr as $index => $initiative) {
            CsrInitiative::query()->updateOrCreate(
                ['title' => $initiative['title']],
                array_merge($initiative, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $faqs = [
            ['question' => 'Which counties does WellDone Group serve?', 'answer' => '<p>We deliver nationwide across all fifteen counties. Our head office and primary warehousing are in Monrovia, with quarry operations in Margibi, Bong and Grand Cape Mount.</p>', 'category' => 'general'],
            ['question' => 'How do I request a quotation?', 'answer' => '<p>Use the <a href="/request-a-quote">Request a Quote</a> form. Select the services you need, describe the scope, and attach any specifications or drawings. You will receive a reference number by email immediately, and a written response from the relevant division.</p>', 'category' => 'general'],
            ['question' => 'How quickly will I receive a response?', 'answer' => '<p>Quotation requests are acknowledged immediately and reviewed within one working day. A written quotation typically follows within three to five working days, depending on whether international sourcing is required.</p>', 'category' => 'general'],
            ['question' => 'Is your aggregate tested and certified?', 'answer' => '<p>Yes. Each consignment is released against grading verification, and test data is issued with delivery documentation.</p>', 'category' => 'mining', 'division' => 'mining-quarry'],
            ['question' => 'Can you operate a quarry on our concession?', 'answer' => '<p>Yes. We take on full operational responsibility for captive quarry sites, from extraction planning through crushing, stockpile management and progressive rehabilitation, against agreed production and compliance targets.</p>', 'category' => 'mining', 'division' => 'mining-quarry'],
            ['question' => 'Do you handle customs clearance directly?', 'answer' => '<p>Yes. We hold a customs brokerage licence and manage declaration, classification, duty assessment, inspection coordination and release at the Freeport of Monrovia and Roberts International Airport.</p>', 'category' => 'logistics', 'division' => 'procurement-logistics'],
            ['question' => 'Can you store temperature-sensitive consignments?', 'answer' => '<p>Yes. Our Monrovia facility includes temperature-controlled capacity suitable for pharmaceutical, reagent and veterinary consignments, with data-logger verification available end to end.</p>', 'category' => 'logistics', 'division' => 'procurement-logistics'],
            ['question' => 'Does equipment supply include installation and training?', 'answer' => '<p>Yes, as standard. Every system we supply is installed, subjected to acceptance testing against manufacturer specification, and handed over only after operator training is complete.</p>', 'category' => 'medical', 'division' => 'medical-equipment'],
            ['question' => 'Do you offer maintenance contracts?', 'answer' => '<p>Yes. Preventive maintenance and calibration contracts are available on equipment we supply, and in many cases on existing equipment after an initial condition assessment.</p>', 'category' => 'medical', 'division' => 'medical-equipment'],
            ['question' => 'Do we own the source code for software you build?', 'answer' => '<p>Yes. For bespoke development, source code and documentation are handed over on completion, and ownership terms are set out in the contract before work begins.</p>', 'category' => 'ict', 'division' => 'ict-services'],
            ['question' => 'Can systems work without reliable internet?', 'answer' => '<p>Where the operating environment requires it, we build offline-first: data is captured locally and synchronised when connectivity returns. This is standard practice for our field and school deployments.</p>', 'category' => 'ict', 'division' => 'ict-services'],
        ];

        foreach ($faqs as $index => $faq) {
            $divisionSlug = $faq['division'] ?? null;
            unset($faq['division']);

            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, [
                    'division_id' => $divisionSlug ? ($divisions[$divisionSlug] ?? null) : null,
                    'sort_order' => $index,
                    'is_active' => true,
                ])
            );
        }

        // Download placeholders — an administrator uploads the real files from
        // the CMS; rows with no file are hidden from the public list.
        $downloads = [
            ['title' => 'WellDone Group Company Profile', 'category' => 'profile', 'description' => 'Full corporate profile covering all four divisions, capabilities and track record.'],
            ['title' => 'Mining & Quarry Capability Statement', 'category' => 'capability', 'division' => 'mining-quarry', 'description' => 'Aggregate products, plant, licensing and environmental management.'],
            ['title' => 'Procurement & Logistics Capability Statement', 'category' => 'capability', 'division' => 'procurement-logistics', 'description' => 'Sourcing, freight, clearance, warehousing and distribution services.'],
            ['title' => 'Medical Equipment Catalogue', 'category' => 'catalogue', 'division' => 'medical-equipment', 'description' => 'Hospital, laboratory and diagnostic equipment lines with supported manufacturers.'],
            ['title' => 'ICT Services Portfolio', 'category' => 'capability', 'division' => 'ict-services', 'description' => 'Software, infrastructure, security and managed service offerings.'],
        ];

        foreach ($downloads as $index => $download) {
            $divisionSlug = $download['division'] ?? null;
            unset($download['division']);

            Download::query()->updateOrCreate(
                ['title' => $download['title']],
                array_merge($download, [
                    'division_id' => $divisionSlug ? ($divisions[$divisionSlug] ?? null) : null,
                    'file_path' => '',
                    'file_name' => '',
                    'sort_order' => $index,
                    // Hidden until a real document is uploaded from the CMS.
                    'is_active' => false,
                ])
            );
        }

        $this->command?->info('Seeded company profile content: team, milestones, certificates, CSR, FAQs, downloads.');
    }
}
