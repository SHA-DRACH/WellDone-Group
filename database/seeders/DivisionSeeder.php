<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * The four founding business divisions and the services under each.
 *
 * Divisions are ordinary rows, not code — adding a fifth subsidiary later is
 * a CMS task, not a deployment.
 */
class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->divisions() as $index => $data) {
            $services = $data['services'];
            unset($data['services']);

            $division = Division::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['sort_order' => $index, 'is_active' => true])
            );

            foreach ($services as $serviceIndex => $service) {
                Service::query()->updateOrCreate(
                    ['slug' => $service['slug']],
                    array_merge($service, [
                        'division_id' => $division->id,
                        'sort_order' => $serviceIndex,
                        'is_active' => true,
                    ])
                );
            }
        }

        $this->command?->info('Seeded '.Division::count().' divisions and '.Service::count().' services.');
    }

    /** @return array<int, array<string, mixed>> */
    protected function divisions(): array
    {
        return [
            [
                'slug' => 'mining-quarry',
                'name' => 'Mining & Quarry',
                'tagline' => 'Aggregate production and mineral operations built on compliance and uptime.',
                'icon' => 'fa-solid fa-mountain',
                'accent_color' => '#8a6d16',
                'is_featured' => true,
                'summary' => 'Licensed quarry and mineral operations supplying crushed stone, sand and aggregate to Liberia\'s construction and infrastructure sector, with contract mining support for concession holders.',
                'description' => '<p>WellDone Group\'s Mining &amp; Quarry division operates licensed extraction and aggregate production sites, supplying graded crushed stone, sand, laterite and base course to road contractors, developers and public works programmes.</p><p>Our operations are planned around three fixed commitments: environmental compliance, verified material grading, and delivery schedules our clients can build a programme around. Every site runs under a documented safety management system, and every consignment ships with grading data.</p>',
                'capabilities' => '<p>Beyond aggregate supply, the division provides contract drilling and blasting, overburden removal, haulage, site rehabilitation and technical advisory to exploration licence holders operating in Liberia.</p>',
                'highlights' => [
                    'Licensed quarry sites with documented environmental management plans',
                    'Graded aggregate with per-consignment test data',
                    'Contract drilling, blasting and haulage crews',
                    'Site rehabilitation and closure planning',
                ],
                'services' => [
                    [
                        'name' => 'Aggregate & Crushed Stone Supply',
                        'slug' => 'aggregate-crushed-stone-supply',
                        'icon' => 'fa-solid fa-cubes-stacked',
                        'summary' => 'Graded crushed stone, chippings, sand and base course supplied to specification and delivered to site.',
                        'description' => '<p>We produce and supply graded aggregate across the standard construction sizes, with material test data issued per consignment and haulage arranged to your site.</p>',
                        'features' => ['3/4", 1/2" and 3/8" chippings', 'Base course and sub-base', 'Washed and unwashed sand', 'Bulk haulage to site'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Quarry Operations & Management',
                        'slug' => 'quarry-operations-management',
                        'icon' => 'fa-solid fa-industry',
                        'summary' => 'Turnkey operation of quarry sites including extraction planning, crushing, screening and stockpile management.',
                        'description' => '<p>We take on full operational responsibility for quarry sites — from extraction sequencing through crushing, screening, stockpile control and despatch — under agreed production and compliance targets.</p>',
                        'features' => ['Extraction planning', 'Crushing and screening', 'Stockpile and despatch control', 'Production reporting'],
                    ],
                    [
                        'name' => 'Drilling & Blasting',
                        'slug' => 'drilling-blasting',
                        'icon' => 'fa-solid fa-burst',
                        'summary' => 'Licensed drill-and-blast services with vibration control and full regulatory documentation.',
                        'description' => '<p>Certified shot-firers plan and execute blasting programmes designed for fragmentation quality and controlled ground vibration, with all statutory permits and records maintained.</p>',
                        'features' => ['Blast design and fragmentation control', 'Vibration and air-overpressure monitoring', 'Licensed explosives handling', 'Statutory record keeping'],
                    ],
                    [
                        'name' => 'Mining Equipment & Plant Hire',
                        'slug' => 'mining-equipment-plant-hire',
                        'icon' => 'fa-solid fa-truck-monster',
                        'summary' => 'Excavators, loaders, crushers, tippers and support plant available with or without operators.',
                        'description' => '<p>A maintained fleet of earthmoving and processing plant is available on short or long-term hire, supplied wet (with operator and maintenance) or dry.</p>',
                        'features' => ['Excavators and wheel loaders', 'Mobile crushing and screening plant', 'Tipper and haulage fleet', 'Wet or dry hire terms'],
                    ],
                    [
                        'name' => 'Site Rehabilitation & Environmental Compliance',
                        'slug' => 'site-rehabilitation-environmental-compliance',
                        'icon' => 'fa-solid fa-seedling',
                        'summary' => 'Progressive rehabilitation, water management and closure planning aligned to EPA requirements.',
                        'description' => '<p>We prepare and execute rehabilitation programmes covering landform reinstatement, revegetation, water management and post-closure monitoring, documented to satisfy regulatory review.</p>',
                        'features' => ['Progressive rehabilitation programmes', 'Water and sediment management', 'Revegetation', 'Closure and monitoring plans'],
                    ],
                ],
            ],

            [
                'slug' => 'procurement-logistics',
                'name' => 'Procurement & Logistics',
                'tagline' => 'Sourcing, freight, clearance and warehousing under one accountable contract.',
                'icon' => 'fa-solid fa-truck-fast',
                'accent_color' => '#0b3c91',
                'is_featured' => true,
                'summary' => 'End-to-end supply chain services — international sourcing, freight forwarding, customs clearance, warehousing and last-mile distribution across Liberia.',
                'description' => '<p>Our Procurement &amp; Logistics division removes the coordination burden from complex supply. We source against your specification, consolidate and ship, clear through customs, store under appropriate conditions, and deliver to the point of use — reporting against one contract and one point of contact throughout.</p><p>The division serves government ministries, NGOs, concession operators and private enterprise, with particular depth in temperature-sensitive and high-value consignments.</p>',
                'capabilities' => '<p>We hold established supplier relationships across Europe, Asia, the Middle East and West Africa, and maintain bonded and general warehousing in Monrovia with distribution capability to all fifteen counties.</p>',
                'highlights' => [
                    'Specification-based international sourcing',
                    'Sea, air and road freight consolidation',
                    'Licensed customs brokerage and clearance',
                    'Bonded and general warehousing in Monrovia',
                ],
                'services' => [
                    [
                        'name' => 'Strategic Procurement & Sourcing',
                        'slug' => 'strategic-procurement-sourcing',
                        'icon' => 'fa-solid fa-cart-flatbed',
                        'summary' => 'Specification-driven sourcing with competitive tendering, supplier vetting and quality verification.',
                        'description' => '<p>We convert your requirement into a defensible procurement: written specification, competitive supplier tender, vetting on quality and compliance, and documented recommendation before any order is placed.</p>',
                        'features' => ['Specification development', 'Competitive supplier tendering', 'Supplier due diligence', 'Pre-shipment inspection'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Freight Forwarding',
                        'slug' => 'freight-forwarding',
                        'icon' => 'fa-solid fa-ship',
                        'summary' => 'Sea, air and road freight with consolidation, tracking and marine cargo insurance.',
                        'description' => '<p>We book, consolidate and track sea, air and overland freight into Liberia, arranging insurance and providing consignment visibility from origin to arrival.</p>',
                        'features' => ['FCL and LCL sea freight', 'Air freight for urgent consignments', 'Cargo consolidation', 'Marine and transit insurance'],
                    ],
                    [
                        'name' => 'Customs Clearance & Compliance',
                        'slug' => 'customs-clearance-compliance',
                        'icon' => 'fa-solid fa-file-shield',
                        'summary' => 'Licensed brokerage covering documentation, duty assessment, inspection and release.',
                        'description' => '<p>Our licensed brokers manage declaration, classification, duty and tariff assessment, inspection coordination and release at the Freeport of Monrovia and Roberts International Airport.</p>',
                        'features' => ['Import and export declarations', 'Tariff classification and duty assessment', 'Inspection coordination', 'Exemption and waiver processing'],
                    ],
                    [
                        'name' => 'Warehousing & Inventory Management',
                        'slug' => 'warehousing-inventory-management',
                        'icon' => 'fa-solid fa-warehouse',
                        'summary' => 'Secure general, bonded and temperature-controlled storage with inventory reporting.',
                        'description' => '<p>Secure storage with access control, stock reconciliation and periodic inventory reporting, including cold-chain capacity for pharmaceutical and reagent consignments.</p>',
                        'features' => ['General and bonded warehousing', 'Cold-chain storage', 'Stock control and cycle counting', 'Inventory reporting'],
                    ],
                    [
                        'name' => 'Transportation & Distribution',
                        'slug' => 'transportation-distribution',
                        'icon' => 'fa-solid fa-truck-ramp-box',
                        'summary' => 'Nationwide haulage and last-mile distribution, including remote and county-level delivery.',
                        'description' => '<p>A maintained vehicle fleet supports scheduled and ad-hoc distribution nationwide, with proof-of-delivery capture and route planning for hard-to-reach destinations.</p>',
                        'features' => ['Nationwide haulage', 'Last-mile county distribution', 'Proof-of-delivery capture', 'Escorted high-value movements'],
                    ],
                    [
                        'name' => 'Supply Chain Advisory',
                        'slug' => 'supply-chain-advisory',
                        'icon' => 'fa-solid fa-chart-line',
                        'summary' => 'Review and redesign of procurement processes, supplier bases and stock policy.',
                        'description' => '<p>We assess existing supply arrangements and recommend practical improvements to cost, lead time, supplier concentration and stock-holding policy.</p>',
                        'features' => ['Spend and lead-time analysis', 'Supplier base rationalisation', 'Stock policy design', 'Process documentation'],
                    ],
                ],
            ],

            [
                'slug' => 'medical-equipment',
                'name' => 'Medical Equipment & Supplies',
                'tagline' => 'Certified equipment, installed, commissioned and maintained.',
                'icon' => 'fa-solid fa-stethoscope',
                'accent_color' => '#1a7fa8',
                'is_featured' => true,
                'summary' => 'Importation and supply of hospital, laboratory and diagnostic equipment with installation, operator training, calibration and after-sales maintenance.',
                'description' => '<p>Equipment that arrives but never works is worse than no equipment at all. Our Medical Equipment division supplies certified hospital, laboratory and diagnostic systems and stays with them — installing, commissioning, training operators, calibrating and maintaining across the asset\'s life.</p><p>We supply public hospitals, county health teams, private clinics, diagnostic laboratories and NGO health programmes, sourcing only from manufacturers with recognised regulatory approval.</p>',
                'capabilities' => '<p>Our biomedical technicians are trained on the platforms we supply, and we hold consumable and spare-part lines for the equipment in our installed base so downtime is measured in days, not months.</p>',
                'highlights' => [
                    'CE / FDA-approved manufacturer sourcing',
                    'Installation, commissioning and operator training',
                    'Preventive maintenance and calibration contracts',
                    'Consumables and spare-part continuity',
                ],
                'services' => [
                    [
                        'name' => 'Hospital Equipment Supply',
                        'slug' => 'hospital-equipment-supply',
                        'icon' => 'fa-solid fa-hospital',
                        'summary' => 'Theatre, ward, maternity, ICU and emergency equipment from certified manufacturers.',
                        'description' => '<p>We supply the full ward-to-theatre range — operating tables and lights, anaesthesia and ventilation, patient monitors, delivery beds, infant care, sterilisation and emergency resuscitation equipment.</p>',
                        'features' => ['Operating theatre systems', 'Patient monitoring and ICU', 'Maternity and neonatal care', 'Sterilisation and CSSD'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Laboratory Equipment & Reagents',
                        'slug' => 'laboratory-equipment-reagents',
                        'icon' => 'fa-solid fa-flask-vial',
                        'summary' => 'Analysers, microscopy, centrifuges and cold-chain reagent supply.',
                        'description' => '<p>Haematology and chemistry analysers, microscopes, centrifuges, incubators and safety cabinets, backed by a reagent supply line shipped under maintained cold chain.</p>',
                        'features' => ['Haematology and chemistry analysers', 'Microscopy and centrifugation', 'Cold-chain reagent supply', 'Laboratory safety equipment'],
                    ],
                    [
                        'name' => 'Diagnostic Imaging Systems',
                        'slug' => 'diagnostic-imaging-systems',
                        'icon' => 'fa-solid fa-x-ray',
                        'summary' => 'X-ray, ultrasound and imaging systems with siting, shielding and commissioning support.',
                        'description' => '<p>We supply and commission radiography and ultrasound systems, coordinating room preparation, radiation shielding, power conditioning and regulatory sign-off.</p>',
                        'features' => ['Digital and analogue radiography', 'Ultrasound systems', 'Room preparation and shielding', 'Radiation safety documentation'],
                    ],
                    [
                        'name' => 'Medical Consumables & Disposables',
                        'slug' => 'medical-consumables-disposables',
                        'icon' => 'fa-solid fa-briefcase-medical',
                        'summary' => 'Reliable resupply of consumables, PPE, surgical disposables and sundries.',
                        'description' => '<p>Scheduled resupply of the consumable lines that keep a facility running, with contract pricing and forecast-based replenishment.</p>',
                        'features' => ['Surgical disposables', 'PPE and infection control', 'Diagnostic consumables', 'Scheduled replenishment contracts'],
                    ],
                    [
                        'name' => 'Installation & Commissioning',
                        'slug' => 'installation-commissioning',
                        'icon' => 'fa-solid fa-screwdriver-wrench',
                        'summary' => 'Site preparation, installation, acceptance testing and operator training.',
                        'description' => '<p>Every system we supply is installed by trained technicians, subjected to acceptance testing against manufacturer specification, and handed over only after operator training is complete.</p>',
                        'features' => ['Site survey and preparation', 'Installation and acceptance testing', 'Operator and biomedical training', 'Handover documentation'],
                    ],
                    [
                        'name' => 'Maintenance & After-Sales Support',
                        'slug' => 'maintenance-after-sales-support',
                        'icon' => 'fa-solid fa-toolbox',
                        'summary' => 'Preventive maintenance, calibration, corrective repair and spare-part supply.',
                        'description' => '<p>Scheduled preventive maintenance and calibration contracts, backed by responsive corrective repair and a held stock of spares for the equipment in our installed base.</p>',
                        'features' => ['Preventive maintenance schedules', 'Calibration and verification', 'Corrective repair response', 'Spare-part continuity'],
                    ],
                ],
            ],

            [
                'slug' => 'ict-services',
                'name' => 'ICT Services & Systems',
                'tagline' => 'Software, infrastructure and security engineered for Liberian operating conditions.',
                'icon' => 'fa-solid fa-microchip',
                'accent_color' => '#1e8e5a',
                'is_featured' => true,
                'summary' => 'Software development, enterprise systems, network and security infrastructure, cloud services and ICT consultancy for public and private sector clients.',
                'description' => '<p>Our ICT division builds and runs the systems our clients depend on — web and mobile applications, management information systems, networks, surveillance and cloud infrastructure — engineered for real conditions: intermittent power, variable bandwidth, and teams who need the system to be usable on day one.</p><p>We deliver against a written specification, hand over documentation and source, and support what we build.</p>',
                'capabilities' => '<p>The team covers full-stack development, network engineering, cybersecurity, and structured cabling, with implementation experience across school, hospital, inventory and financial management systems.</p>',
                'highlights' => [
                    'Custom software and enterprise systems',
                    'Network, CCTV and structured cabling',
                    'Cloud migration and managed hosting',
                    'Cybersecurity assessment and hardening',
                ],
                'services' => [
                    [
                        'name' => 'Website Design & Development',
                        'slug' => 'website-design-development',
                        'icon' => 'fa-solid fa-globe',
                        'summary' => 'Corporate websites, portals and content management systems built for performance and search visibility.',
                        'description' => '<p>Responsive, accessible, search-optimised websites with a content management system your team can actually operate — plus hosting, monitoring and maintenance.</p>',
                        'features' => ['Corporate and e-commerce sites', 'Content management systems', 'SEO and performance optimisation', 'Hosting and maintenance'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Custom Software Development',
                        'slug' => 'custom-software-development',
                        'icon' => 'fa-solid fa-code',
                        'summary' => 'Bespoke business applications delivered from requirements through deployment and support.',
                        'description' => '<p>We build line-of-business applications to a documented specification, with staged delivery, user acceptance testing, training and post-deployment support.</p>',
                        'features' => ['Requirements and specification', 'Staged delivery and UAT', 'Data migration', 'Training and support'],
                    ],
                    [
                        'name' => 'Mobile Application Development',
                        'slug' => 'mobile-application-development',
                        'icon' => 'fa-solid fa-mobile-screen-button',
                        'summary' => 'Android and iOS applications, including offline-capable field data collection.',
                        'description' => '<p>Native and cross-platform mobile applications, with particular experience in offline-first field data capture that syncs when connectivity returns.</p>',
                        'features' => ['Android and iOS delivery', 'Offline-first data capture', 'Store publication', 'Analytics and crash reporting'],
                    ],
                    [
                        'name' => 'ERP & Management Information Systems',
                        'slug' => 'erp-management-information-systems',
                        'icon' => 'fa-solid fa-diagram-project',
                        'summary' => 'Finance, inventory, HR and operations systems, implemented and integrated.',
                        'description' => '<p>Implementation and integration of enterprise resource planning and management information systems covering finance, inventory, procurement, human resources and operations reporting.</p>',
                        'features' => ['Finance and inventory modules', 'HR and payroll', 'Reporting and dashboards', 'Third-party integration'],
                    ],
                    [
                        'name' => 'School Management Systems',
                        'slug' => 'school-management-systems',
                        'icon' => 'fa-solid fa-graduation-cap',
                        'summary' => 'Enrolment, attendance, grading, fees and parent communication in one platform.',
                        'description' => '<p>A complete school administration platform covering student records, enrolment, attendance, assessment and grading, fee billing and collection, and parent communication.</p>',
                        'features' => ['Student records and enrolment', 'Attendance and grading', 'Fee billing and receipting', 'Parent and staff portals'],
                    ],
                    [
                        'name' => 'Hospital Management Systems',
                        'slug' => 'hospital-management-systems',
                        'icon' => 'fa-solid fa-notes-medical',
                        'summary' => 'Patient records, appointments, pharmacy, laboratory and billing modules.',
                        'description' => '<p>Health facility management covering patient registration and records, appointment scheduling, pharmacy and laboratory workflow, inpatient management and billing.</p>',
                        'features' => ['Patient records and registration', 'Pharmacy and laboratory modules', 'Inpatient and theatre management', 'Billing and health insurance claims'],
                    ],
                    [
                        'name' => 'Networking & Structured Cabling',
                        'slug' => 'networking-structured-cabling',
                        'icon' => 'fa-solid fa-network-wired',
                        'summary' => 'LAN, WAN, wireless and fibre infrastructure, certified and documented.',
                        'description' => '<p>Design and installation of structured cabling, switching, routing, enterprise wireless and point-to-point links, with certification testing and as-built documentation.</p>',
                        'features' => ['Structured cabling and certification', 'Switching and routing', 'Enterprise wireless', 'Fibre and point-to-point links'],
                    ],
                    [
                        'name' => 'CCTV & Security Systems',
                        'slug' => 'cctv-security-systems',
                        'icon' => 'fa-solid fa-video',
                        'summary' => 'IP surveillance, access control, alarms and remote monitoring.',
                        'description' => '<p>IP camera systems with recording, retention and remote viewing, integrated with access control, intrusion detection and alarm response.</p>',
                        'features' => ['IP camera and NVR systems', 'Access control', 'Intrusion detection and alarms', 'Remote monitoring'],
                    ],
                    [
                        'name' => 'Cloud Computing & Hosting',
                        'slug' => 'cloud-computing-hosting',
                        'icon' => 'fa-solid fa-cloud',
                        'summary' => 'Cloud migration, managed hosting, email services and backup.',
                        'description' => '<p>Migration to cloud platforms, managed hosting and business email, with monitored backup and tested restore procedures.</p>',
                        'features' => ['Cloud migration', 'Managed hosting', 'Business email and collaboration', 'Backup and disaster recovery'],
                    ],
                    [
                        'name' => 'Cybersecurity Services',
                        'slug' => 'cybersecurity-services',
                        'icon' => 'fa-solid fa-lock',
                        'summary' => 'Security assessment, hardening, monitoring and staff awareness training.',
                        'description' => '<p>Vulnerability assessment and penetration testing, infrastructure hardening, endpoint protection, monitoring, and security awareness training for staff.</p>',
                        'features' => ['Vulnerability assessment', 'Infrastructure hardening', 'Endpoint protection and monitoring', 'Security awareness training'],
                    ],
                    [
                        'name' => 'ICT Consultancy & Support',
                        'slug' => 'ict-consultancy-support',
                        'icon' => 'fa-solid fa-headset',
                        'summary' => 'ICT strategy, procurement advisory and ongoing technical support contracts.',
                        'description' => '<p>Independent advice on ICT strategy, architecture and procurement, plus contracted helpdesk and on-site technical support.</p>',
                        'features' => ['ICT strategy and architecture', 'Procurement advisory', 'Helpdesk and on-site support', 'Managed service contracts'],
                    ],
                ],
            ],
        ];
    }
}
