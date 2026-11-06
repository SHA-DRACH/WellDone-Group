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

        // Leadership team as listed in the Company Profile (October 2026).
        // Photos ship in public/images/team; uploading a new one from the CMS
        // replaces the path.
        $leadership = [
            [
                'name' => 'Gbogbah Matthew Tarr',
                'position' => 'Founder & Chief Executive Officer',
                'department' => 'Executive',
                'photo' => '/images/team/gbogbah-matthew-tarr.jpg',
                'short_bio' => 'Founder and CEO, with over 29 years of progressive leadership in finance, database development, ICT, logistics, procurement and programme management across Africa, Asia and Australia.',
                'bio' => '<p><em>Building a Trusted African Enterprise Through Integrity, Strategic Leadership, and Sustainable Growth.</em></p>'
                    .'<p>Gbogbah Matthew Tarr is the Founder and Chief Executive Officer of WellDone Group Inc., a diversified business corporation established in 2025 in Liberia.</p>'
                    .'<p>He brings over 29 years of progressive leadership experience in finance, database development, ICT, logistics, procurement, partnership development, operations, administration, and programme management across Africa and Asia, complemented by professional experience in Australia in both private and public sectors. His expertise spans financial stewardship, database development, ICT, strategic resource allocation, operational oversight, risk management, and organizational leadership, including management of programmes with a large volume of budgets.</p>'
                    .'<p>Gbogbah holds a Master of Commerce in Professional Accounting, a Master of Public Policy and Management, and a Bachelor of Commerce in Accounting from Australian universities, as well as a Diploma in Business &amp; Management and a Diploma in Bookkeeping &amp; Accounts from Cambridge International College, UK. He also holds a certificate in Computer Literacy from the then IRC Vocational School Systems, N\'Zerekore, Guinea. He is a member of the Institute of Public Accountants of Australia.</p>'
                    .'<p>His career includes senior management and leadership roles with Malteser International, the International Rescue Committee, the Lutheran World Federation and Bayswater ECHO with responsibility for country-level financial management, compliance, human resources, procurement, ICT, database development and programme delivery.</p>'
                    .'<p>As Founder and CEO, Gbogbah is leading WellDone Group to become a professionally managed, diversified African enterprise focused on creating commercial value, expanding market opportunities, generating employment, and contributing to sustainable economic development. The Group\'s focus sectors include information technology, logistics, mining, healthcare, and trade, with a vision to establish a strong presence in Liberia and expand across West Africa and the wider continent.</p>'
                    .'<p>His leadership is defined by financial discipline, accountability, transparent decision-making, and responsible risk management. He believes investor confidence is built through sound governance, clear commitments, reliable delivery, and responsible management of capital.</p>'
                    .'<p>Gbogbah\'s entrepreneurial vision is rooted in his personal journey — from the hardships of poverty and displacement as a refugee at age 18 in Conakry, Guinea, during Liberia\'s civil war to professional leadership, supported by humanitarian organizations including the International Rescue Committee. That experience forged his commitment to building businesses that create opportunities for others through sustainable enterprise.</p>'
                    .'<p>He welcomes partnerships with investors, financial institutions, suppliers, development partners, and strategic collaborators who share his ambition to build competitive African businesses that deliver sustainable commercial returns while creating jobs and lasting social value.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Garjay Jestin Tarr',
                'position' => 'Co-Founder & Managing Director',
                'department' => 'Executive',
                'photo' => '/images/team/garjay-jestin-tarr.jpg',
                'short_bio' => 'More than 16 years in public-sector administration, program support and project management with the U.S. Department of State and USAID.',
                'bio' => '<p>Mr. Garjay Jestin Tarr is the Managing Director of WellDone Group Inc., bringing more than 16 years of experience in public-sector administration, program support, and project management. He spent eight years with the U.S. Department of State before joining the U.S. Agency for International Development (USAID), where he moved into executive support and project management roles.</p>'
                    .'<p>At USAID Guinea and Sierra Leone, he supported senior leadership across the Front Office and the Democracy, Rights and Governance and Economic Growth offices, provided technical oversight of partner-implemented programs, including field monitoring in five regions of Guinea, and supported the Disaster Assistance Response Team during the Ebola outbreak. His expertise spans project coordination, compliance and performance monitoring, procurement and financial processes, logistics, stakeholder engagement, and reporting.</p>'
                    .'<p>Mr. Tarr holds a Bachelor\'s Degree in Economic Management from the University International College, Republic of Guinea, Conakry. He has also completed several professional training programs in project management, administration, leadership, and contract oversight, further strengthening his technical and managerial competencies.</p>'
                    .'<p>Mr. Tarr is bilingual in English and French and has worked effectively in multicultural, high-pressure environments, building strong relationships with clients, partners, and communities. Known for his reliability, sound judgment, and ability to manage multiple priorities, he understands how projects are planned, funded, monitored, and delivered to a high standard. At WellDone Group Inc., he leads with integrity, accountability, and a strong commitment to achieving measurable results.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Stephen V. Kollie',
                'position' => 'Logistics, Procurement & General Trading Director',
                'department' => 'Logistics',
                'division' => 'procurement-logistics',
                'photo' => '/images/team/stephen-v-kollie.jpg',
                'short_bio' => 'Supply chain specialist leading procurement, logistics and bidding, with a Master\'s in Procurement and Supply Chain Management.',
                'bio' => '<p>Mr. Stephen V. Kollie is the Procurement and Logistics Lead at WellDone Group Inc., where he oversees the end-to-end procurement lifecycle and ensures the efficient distribution of goods and services across the organization. A specialist in supply chain optimization, strategic sourcing, and operational efficiency, he leads the company\'s bidding efforts for goods and services and safeguards the timely continuity of business operations. He is recognized for building strong vendor relationships and negotiating high-value contracts that reduce operational costs while upholding the highest standards of quality and compliance. Believing a resilient supply chain is the backbone of any successful enterprise, he consistently works to mitigate risk and streamline logistics.</p>'
                    .'<p>Before joining WellDone Group, Mr. Kollie held leadership roles in both the public and private sectors, building deep expertise in supply chain and logistics management, finance, and administration. He holds a Master\'s degree in Procurement and Supply Chain Management from the International University of East Africa (IUEA) in Kampala, Uganda, and a Bachelor of Business Administration in Accounting from the United Methodist University (UMU) in Monrovia, Liberia. Passionate about data-driven decision-making, he applies analytical insights to drive continuous improvement across procurement and logistics operations.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Shadrach Jimice Jr.',
                'position' => 'Senior Software Engineer & Director of ICT Services',
                'department' => 'ICT',
                'division' => 'ict-services',
                'photo' => '/images/team/shadrach-jimice-jr.jpg',
                'short_bio' => 'Software engineer providing technology leadership across software development, digital transformation and IT systems.',
                'bio' => '<p>Mr. Shadrach Jimice Jr. is a Software Engineer and ICT professional with a BSc (Hons) in Software Engineering from UNILAK, Rwanda, and is currently a Master of Information Technology (MIT) Candidate at Starz University, Liberia.</p>'
                    .'<p>As a Senior Software Engineer and Director of ICT Services at WellDone Group Inc., he provides technology leadership across software development, digital transformation, IT systems, and business technology solutions. He brings experience in web and mobile application development, database management, IT support, and ICT training, with a strong focus on using technology to improve organizational efficiency and growth.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'Philip Saywray Gongar',
                'position' => 'Director of Strategy, Business Development, Innovation & Research',
                'department' => 'Strategy',
                'photo' => '/images/team/philip-saywray-gongar.jpg',
                'short_bio' => 'Management professional with 10 years in human resource management, administration, project coordination and executive support.',
                'bio' => '<p>Mr. Philip Saywray Gongar is an accomplished management professional with 10 years of experience in human resource management, administration, project coordination, and executive management support. He serves as the Director of Strategy, Business Development, Innovation &amp; Research at WellDone Group Inc., where he contributes to the company\'s strategic direction, business expansion, innovation initiatives, and research-driven growth.</p>'
                    .'<p>Prior to joining WellDone Group Inc., Mr. Gongar spent 10 years with CICO, serving as an HR Administrator and Management Assistant across various infrastructure development projects in Liberia. During his tenure, he played a key role in supporting several road construction projects, gaining valuable experience in workforce management, project administration, operational coordination, and organizational support. His contributions strengthened administrative processes, facilitated effective human resource operations, and supported the successful execution of project activities.</p>'
                    .'<p>Mr. Gongar holds a Bachelor of Management Studies (BMS) and a Diploma in Human Resource Management. He also possesses a Certificate in Project Management and a Certificate of License in Christian Ministry. His academic and professional qualifications provide a solid foundation in management principles, organizational leadership, human resource administration, and project coordination.</p>'
                    .'<p>His core competencies include human resource management, project administration, organizational development, policy formulation, strategic planning, and operational management. He combines practical experience in managing administrative and workforce functions with a strong interest in business development, innovation, institutional strengthening, and sustainable organizational performance.</p>'
                    .'<p>At WellDone Group Inc., a diversified Liberian-owned corporation established in 2025, Mr. Gongar supports the company\'s vision of delivering innovative, reliable, and sustainable business solutions across multiple sectors. He is committed to identifying emerging business opportunities, fostering strategic partnerships, promoting research-informed decision-making, and advancing initiatives that strengthen organizational competitiveness, create employment opportunities, and contribute to sustainable economic growth in Liberia and the wider West African region.</p>',
                'is_leadership' => true,
            ],
            [
                'name' => 'William Lee Davis',
                'position' => 'Director of Medical Supplies & Healthcare Services',
                'department' => 'Medical',
                'division' => 'medical-equipment',
                'photo' => '/images/team/william-lee-davis.jpg',
                'short_bio' => 'Chemical engineering and pharmaceutical research specialist with an MSc in Pharmaceutical Engineering from China Pharmaceutical University.',
                'bio' => '<p>Chemical Engineering and Pharmaceutical Research Specialist with over five years of experience in biochemistry, process optimization, and research and development (R&amp;D). Demonstrated expertise in developing innovative biochemical processes, scaling up operations from laboratory research to industrial production, and conducting impactful scientific research.</p>'
                    .'<p>Holds a Master of Science (MSc) in Pharmaceutical Engineering from China Pharmaceutical University, with specialized research in nitric oxide (NO)-releasing hydrogels and nanoparticle-based drug delivery systems for the treatment of peptic ulcers. Proven ability to lead cross-functional teams, oversee quality control systems, and advance sustainable green chemistry initiatives. Skilled at bridging academic research and industrial applications while working effectively in complex, multicultural, and international environments.</p>',
                'is_leadership' => true,
            ],
        ];

        // Hide placeholder team members who are not in the Company Profile.
        TeamMember::query()
            ->whereNotIn('name', array_column($leadership, 'name'))
            ->update(['is_active' => false]);

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
            ['year' => '2025', 'title' => 'WellDone Group Inc. established', 'icon' => 'fa-solid fa-flag', 'description' => 'WellDone Group Inc. is founded as a diversified business corporation under the laws of the Republic of Liberia, licensed for nineteen fields of business.'],
            ['year' => '2026', 'title' => 'Five business divisions in operation', 'icon' => 'fa-solid fa-layer-group', 'description' => 'The Group implements five divisions: ICT Services, Logistics & Procurement, Mining Services, Medical Supply Services, and Trading & Investment.'],
        ];

        // Hide placeholder milestones that predate the 2025 founding.
        Milestone::query()
            ->whereNotIn('title', array_column($milestones, 'title'))
            ->update(['is_active' => false]);

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
            ['title' => 'WellDone Group Company Profile', 'category' => 'profile', 'description' => 'Full corporate profile covering all five divisions, leadership, vision, mission and core values.'],
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
