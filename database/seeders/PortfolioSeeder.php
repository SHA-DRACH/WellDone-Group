<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Division;
use App\Models\Gallery;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Representative clients, projects, testimonials, partner logos and galleries.
 *
 * This is illustrative content for a fresh install — replace it with real
 * project records before the site goes live.
 */
class PortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = Division::query()->pluck('id', 'slug');

        $clients = [
            ['name' => 'Ministry of Public Works', 'industry' => 'Government', 'country' => 'Liberia', 'is_featured' => true],
            ['name' => 'Liberia National Road Authority', 'industry' => 'Infrastructure', 'country' => 'Liberia', 'is_featured' => true],
            ['name' => 'Redemption Referral Hospital', 'industry' => 'Healthcare', 'country' => 'Liberia', 'is_featured' => true],
            ['name' => 'Bong County Health Team', 'industry' => 'Healthcare', 'country' => 'Liberia'],
            ['name' => 'Western Cluster Concession', 'industry' => 'Mining', 'country' => 'Liberia', 'is_public' => false],
            ['name' => 'Monrovia Consolidated School System', 'industry' => 'Education', 'country' => 'Liberia', 'is_featured' => true],
            ['name' => 'Atlantic Commercial Bank', 'industry' => 'Financial Services', 'country' => 'Liberia'],
            ['name' => 'Cavalla Agricultural Estate', 'industry' => 'Agriculture', 'country' => 'Liberia'],
        ];

        foreach ($clients as $index => $client) {
            Client::query()->updateOrCreate(
                ['name' => $client['name']],
                array_merge($client, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $clientIds = Client::query()->pluck('id', 'name');

        $projects = [
            [
                'title' => 'Kakata–Bong Mines Road Aggregate Supply',
                'division' => 'mining-quarry',
                'client' => 'Liberia National Road Authority',
                'status' => 'completed',
                'location' => 'Margibi & Bong Counties',
                'start_date' => '2024-02-01',
                'end_date' => '2025-01-31',
                'summary' => 'Supply of 180,000 m³ of graded base course and crushed aggregate for a 46 km trunk road rehabilitation.',
                'description' => '<p>WellDone Group supplied graded base course, sub-base and 3/4" chippings across a twelve-month programme supporting the rehabilitation of 46 km of trunk road between Kakata and the Bong Mines corridor.</p><p>Production was scheduled against the contractor\'s laying programme, with each consignment released only after grading verification. Despite two extended rain periods, monthly delivery targets were met in eleven of twelve months.</p>',
                'scope_of_work' => '<ul><li>Quarry production scheduling against the laying programme</li><li>Crushing and screening to specified gradings</li><li>Per-consignment grading verification and test certification</li><li>Haulage to seventeen stockpile points along the corridor</li></ul>',
                'key_facts' => ['Material supplied' => '180,000 m³', 'Corridor length' => '46 km', 'Stockpile points' => '17', 'Programme' => '12 months'],
                'is_featured' => true,
                'completion_percentage' => 100,
            ],
            [
                'title' => 'County Hospital Biomedical Equipping Programme',
                'division' => 'medical-equipment',
                'client' => 'Bong County Health Team',
                'status' => 'completed',
                'location' => 'Gbarnga, Bong County',
                'start_date' => '2024-06-01',
                'end_date' => '2025-03-15',
                'summary' => 'Supply, installation and commissioning of theatre, maternity and laboratory equipment across four county health facilities.',
                'description' => '<p>A four-facility equipping programme covering operating theatre, maternity, laboratory and sterilisation departments. WellDone Group handled specification review, international sourcing, freight and clearance, site preparation, installation, acceptance testing and operator training.</p><p>All four facilities were handed over with completed acceptance test records, and a twenty-four month preventive maintenance contract now covers the installed base.</p>',
                'scope_of_work' => '<ul><li>Specification review and manufacturer sourcing</li><li>Freight, clearance and cold-chain handling for reagents</li><li>Site preparation and installation across four facilities</li><li>Acceptance testing, operator training and handover documentation</li><li>24-month preventive maintenance contract</li></ul>',
                'key_facts' => ['Facilities equipped' => '4', 'Equipment items' => '310+', 'Staff trained' => '68', 'Maintenance cover' => '24 months'],
                'is_featured' => true,
                'completion_percentage' => 100,
            ],
            [
                'title' => 'School Management System Rollout',
                'division' => 'ict-services',
                'client' => 'Monrovia Consolidated School System',
                'status' => 'ongoing',
                'location' => 'Montserrado County',
                'start_date' => '2025-09-01',
                'end_date' => null,
                'summary' => 'Phased deployment of a unified enrolment, attendance, grading and fee management platform across 22 schools.',
                'description' => '<p>A phased rollout of a single school administration platform covering student records, enrolment, attendance, assessment and grading, and fee billing — replacing paper registers and disconnected spreadsheets across twenty-two schools.</p><p>Phase one (nine schools) is live; phases two and three continue through the current academic year. The platform is designed to operate offline at school level and synchronise when connectivity is available.</p>',
                'scope_of_work' => '<ul><li>Requirements gathering across school administrations</li><li>Platform configuration and data migration</li><li>Offline-first synchronisation for low-connectivity sites</li><li>Administrator and teacher training</li><li>Phased go-live and support</li></ul>',
                'key_facts' => ['Schools in scope' => '22', 'Phase one live' => '9 schools', 'Student records migrated' => '14,500+', 'Status' => 'Phase two in progress'],
                'is_featured' => true,
                'completion_percentage' => 45,
            ],
            [
                'title' => 'Freeport Consolidation & Clearance Contract',
                'division' => 'procurement-logistics',
                'client' => 'Ministry of Public Works',
                'status' => 'ongoing',
                'location' => 'Freeport of Monrovia',
                'start_date' => '2025-01-15',
                'end_date' => null,
                'summary' => 'Ongoing consolidation, customs clearance and nationwide distribution of imported plant, spares and materials.',
                'description' => '<p>WellDone Group manages consolidation at origin, sea freight, customs declaration and clearance at the Freeport of Monrovia, bonded storage, and onward distribution of plant, spare parts and construction materials to project sites in eleven counties.</p>',
                'scope_of_work' => '<ul><li>Origin consolidation and freight booking</li><li>Customs declaration, classification and clearance</li><li>Bonded and general warehousing</li><li>County-level distribution with proof-of-delivery capture</li></ul>',
                'key_facts' => ['Counties served' => '11', 'Consignments cleared' => '190+', 'Contract type' => 'Framework', 'Status' => 'Active'],
                'completion_percentage' => 60,
            ],
            [
                'title' => 'Concession Site Quarry Development',
                'division' => 'mining-quarry',
                'client' => 'Western Cluster Concession',
                'status' => 'completed',
                'location' => 'Grand Cape Mount County',
                'start_date' => '2023-04-01',
                'end_date' => '2024-05-30',
                'summary' => 'Development and operation of a captive quarry supplying haul-road and platform material to a mining concession.',
                'description' => '<p>Development of a captive quarry from initial survey through to sustained production, supplying haul-road construction and platform material. The engagement covered extraction planning, plant mobilisation, drilling and blasting, crushing and stockpile management, alongside a progressive rehabilitation programme agreed with the regulator.</p>',
                'scope_of_work' => '<ul><li>Site survey and extraction planning</li><li>Plant mobilisation and commissioning</li><li>Licensed drilling and blasting with vibration monitoring</li><li>Crushing, screening and stockpile management</li><li>Progressive rehabilitation programme</li></ul>',
                'key_facts' => ['Programme' => '14 months', 'Peak output' => '900 m³/day', 'Blast events' => '120+', 'Rehabilitation' => 'Progressive'],
                'show_client' => false,
                'client_display_name' => 'Confidential — mining concession operator',
                'completion_percentage' => 100,
            ],
            [
                'title' => 'Branch Network Security & Surveillance Upgrade',
                'division' => 'ict-services',
                'client' => 'Atlantic Commercial Bank',
                'status' => 'completed',
                'location' => 'Monrovia & Buchanan',
                'start_date' => '2025-02-01',
                'end_date' => '2025-06-30',
                'summary' => 'IP surveillance, access control and network hardening across nine branches and a head office.',
                'description' => '<p>Replacement of legacy analogue surveillance with IP camera systems, centralised recording with defined retention, integrated access control on secure areas, and a network hardening programme across nine branches and the head office.</p>',
                'scope_of_work' => '<ul><li>IP camera and NVR deployment with 90-day retention</li><li>Access control on vaults and server rooms</li><li>Structured cabling certification</li><li>Firewall, segmentation and endpoint hardening</li><li>Operator training and documentation handover</li></ul>',
                'key_facts' => ['Sites' => '10', 'Cameras deployed' => '184', 'Retention' => '90 days', 'Programme' => '5 months'],
                'completion_percentage' => 100,
            ],
            [
                'title' => 'Agricultural Estate Cold-Chain Supply',
                'division' => 'procurement-logistics',
                'client' => 'Cavalla Agricultural Estate',
                'status' => 'completed',
                'location' => 'Maryland County',
                'start_date' => '2024-08-01',
                'end_date' => '2024-12-20',
                'summary' => 'Sourcing, import and cold-chain delivery of agricultural inputs and veterinary supplies.',
                'description' => '<p>Competitive sourcing, import and temperature-controlled delivery of agricultural inputs and veterinary supplies to a remote estate, with unbroken cold chain from origin to point of use verified by data logger.</p>',
                'key_facts' => ['Consignments' => '14', 'Cold-chain integrity' => '100%', 'Lead time achieved' => '32 days average'],
                'completion_percentage' => 100,
            ],
            [
                'title' => 'National Referral Hospital Imaging Suite',
                'division' => 'medical-equipment',
                'client' => 'Redemption Referral Hospital',
                'status' => 'upcoming',
                'location' => 'Monrovia',
                'start_date' => '2026-09-01',
                'end_date' => null,
                'summary' => 'Planned supply and commissioning of digital radiography and ultrasound systems with shielded room preparation.',
                'description' => '<p>A planned programme to supply and commission digital radiography and ultrasound systems, including radiation-shielded room preparation, power conditioning, regulatory sign-off and radiographer training.</p>',
                'key_facts' => ['Modalities' => 'DR & ultrasound', 'Room works' => 'Shielding & power', 'Status' => 'Awarded, mobilising'],
                'completion_percentage' => 0,
            ],
        ];

        foreach ($projects as $index => $data) {
            $divisionSlug = $data['division'];
            $clientName = $data['client'];
            unset($data['division'], $data['client']);

            Project::query()->updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, [
                    'division_id' => $divisions[$divisionSlug] ?? null,
                    'client_id' => $clientIds[$clientName] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                    'currency' => 'USD',
                ])
            );
        }

        $testimonials = [
            [
                'author_name' => 'Hon. Arthur N. Bloh',
                'author_title' => 'Deputy Minister for Technical Services',
                'company' => 'Ministry of Public Works',
                'quote' => 'What distinguishes WellDone is predictability. Material arrived when they said it would, graded as specified, with the paperwork complete. On a programme of this length, that is worth more than a lower unit price.',
                'division' => 'mining-quarry',
                'is_featured' => true,
            ],
            [
                'author_name' => 'Dr. Yassah M. Toe',
                'author_title' => 'County Health Officer',
                'company' => 'Bong County Health Team',
                'quote' => 'They did not simply deliver crates. Every unit was installed, tested and signed off, and our staff were trained before handover. A year on, the maintenance visits still arrive on schedule.',
                'division' => 'medical-equipment',
                'is_featured' => true,
            ],
            [
                'author_name' => 'Emmanuel K. Sherman',
                'author_title' => 'Director of Administration',
                'company' => 'Monrovia Consolidated School System',
                'quote' => 'The team understood that our schools lose connectivity for days at a time and built for it. Registers work offline and sync later — that single decision is why teachers actually use the system.',
                'division' => 'ict-services',
                'is_featured' => true,
            ],
            [
                'author_name' => 'Miatta Cooper',
                'author_title' => 'Head of Procurement',
                'company' => 'Atlantic Commercial Bank',
                'quote' => 'Clearance at the Freeport used to be our longest and least predictable step. Handing it to WellDone removed roughly three weeks from our average lead time.',
                'division' => 'procurement-logistics',
                'is_featured' => true,
            ],
            [
                'author_name' => 'Alfred Gbeh',
                'author_title' => 'Estate Manager',
                'company' => 'Cavalla Agricultural Estate',
                'quote' => 'Cold chain to Maryland County is not a trivial thing. Fourteen consignments, every data logger clean. We have not had to think about it since.',
                'division' => 'procurement-logistics',
            ],
        ];

        foreach ($testimonials as $index => $data) {
            $divisionSlug = $data['division'] ?? null;
            unset($data['division']);

            Testimonial::query()->updateOrCreate(
                ['author_name' => $data['author_name'], 'company' => $data['company']],
                array_merge($data, [
                    'division_id' => $divisionSlug ? ($divisions[$divisionSlug] ?? null) : null,
                    'client_id' => $clientIds[$data['company']] ?? null,
                    'rating' => 5,
                    'sort_order' => $index,
                    'is_active' => true,
                ])
            );
        }

        $partners = [
            ['name' => 'Liberia Business Registry', 'type' => 'accreditation'],
            ['name' => 'Environmental Protection Agency of Liberia', 'type' => 'accreditation'],
            ['name' => 'Liberia Revenue Authority', 'type' => 'accreditation'],
            ['name' => 'National Public Health Institute of Liberia', 'type' => 'affiliation'],
            ['name' => 'Liberia Chamber of Commerce', 'type' => 'affiliation'],
            ['name' => 'Freeport of Monrovia', 'type' => 'partner'],
            ['name' => 'West Africa Logistics Alliance', 'type' => 'partner'],
            ['name' => 'Global Medical Systems Ltd', 'type' => 'supplier'],
            ['name' => 'Atlas Heavy Plant', 'type' => 'supplier'],
            ['name' => 'Sahel Technologies', 'type' => 'supplier'],
        ];

        foreach ($partners as $index => $partner) {
            Partner::query()->updateOrCreate(
                ['name' => $partner['name']],
                array_merge($partner, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $galleries = [
            ['title' => 'Quarry Operations', 'division' => 'mining-quarry', 'description' => 'Extraction, crushing and stockpile management across our licensed sites.'],
            ['title' => 'Logistics & Warehousing', 'division' => 'procurement-logistics', 'description' => 'Consolidation, clearance and distribution in action.'],
            ['title' => 'Medical Equipment Installations', 'division' => 'medical-equipment', 'description' => 'Commissioning and operator training at partner health facilities.'],
            ['title' => 'ICT Deployments', 'division' => 'ict-services', 'description' => 'Network, surveillance and systems work on client sites.'],
        ];

        foreach ($galleries as $index => $gallery) {
            $divisionSlug = $gallery['division'];
            unset($gallery['division']);

            Gallery::query()->updateOrCreate(
                ['title' => $gallery['title']],
                array_merge($gallery, [
                    'division_id' => $divisions[$divisionSlug] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                ])
            );
        }

        $this->command?->info('Seeded portfolio content: clients, projects, testimonials, partners, galleries.');
    }
}
