<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * The five business divisions WellDone Group Inc. is currently implementing,
 * and the services under each — taken from the Company Profile.
 *
 * Divisions are ordinary rows, not code — adding another subsidiary later is
 * a CMS task, not a deployment. The original slugs are kept so existing
 * references (division logs, staff assignments, URLs) continue to work.
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

            $slugs = [];

            foreach ($services as $serviceIndex => $service) {
                $slugs[] = $service['slug'];

                Service::query()->updateOrCreate(
                    ['slug' => $service['slug']],
                    array_merge($service, [
                        'division_id' => $division->id,
                        'sort_order' => $serviceIndex,
                        'is_active' => true,
                    ])
                );
            }

            // Services the profile no longer lists are hidden, not deleted,
            // so quote requests that reference them keep their history.
            Service::query()
                ->where('division_id', $division->id)
                ->whereNotIn('slug', $slugs)
                ->update(['is_active' => false]);
        }

        $this->command?->info('Seeded '.Division::count().' divisions and '.Service::count().' services.');
    }

    /** @return array<int, array<string, mixed>> */
    protected function divisions(): array
    {
        return [
            // -------------------------------------------------------- ICT
            [
                'slug' => 'ict-services',
                'name' => 'WellDone ICT Services',
                'tagline' => 'Innovative information technology solutions that streamline operations and inform decisions.',
                'icon' => 'fa-solid fa-microchip',
                'accent_color' => '#1e8e5a',
                'is_featured' => true,
                'summary' => 'Customized software and technology solutions for government institutions, private-sector organizations, NGOs, educational institutions, political parties and development partners, plus web development, software and hardware maintenance, and ICT support.',
                'description' => '<p>WellDone ICT Services provides innovative information technology solutions that help organizations streamline operations, improve efficiency, and make informed decisions through modern digital systems.</p><p>The company designs, implements, and supports customized software and technology solutions for government institutions, private-sector organizations, non-governmental organizations, educational institutions, political parties, and development partners. The company also does web development, software and hardware maintenance, and other ICT-related support.</p>',
                'capabilities' => '<p>WellDone ICT solutions are not limited to the business solutions listed here — the division has the ability and expertise to develop any ICT solution based on an organization\'s information needs.</p>',
                'highlights' => [
                    'Human Resources, Procurement and Purchase Management Systems',
                    'Political Party, Student and Agricultural Enterprise information systems',
                    'MEAL solutions and SUSU savings & loan management',
                    'Web development, software and hardware maintenance, ICT support',
                ],
                'services' => [
                    [
                        'name' => 'Human Resources Management System (HRMS)',
                        'slug' => 'human-resources-management-system',
                        'icon' => 'fa-solid fa-users-gear',
                        'summary' => 'A secure, efficient and user-friendly platform that automates and integrates core human resource functions.',
                        'description' => '<p>A secure, efficient, and user-friendly platform that automates and integrates core human resource functions, including a centralized staff database, performance management, leave management, attendance tracking, payroll processing, recruitment and selection, and employee benefits administration.</p>',
                        'features' => ['Centralized staff database', 'Performance and leave management', 'Attendance tracking and payroll processing', 'Recruitment, selection and benefits administration'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Political Party Information Management System (PPIMS)',
                        'slug' => 'political-party-information-management-system',
                        'icon' => 'fa-solid fa-people-group',
                        'summary' => 'Membership registration, organizational management, voter engagement and election-related data management.',
                        'description' => '<p>Supports membership registration, organizational management, voter engagement, and election-related data management, including a national register of party members, multi-channel enrolment (web, mobile app, USSD, and SMS), identity verification against national ID records, and real-time membership analytics.</p>',
                        'features' => ['National register of party members', 'Web, mobile app, USSD and SMS enrolment', 'Identity verification against national ID records', 'Real-time membership analytics'],
                    ],
                    [
                        'name' => 'Student Information Management System (SIMS)',
                        'slug' => 'student-information-management-system',
                        'icon' => 'fa-solid fa-graduation-cap',
                        'summary' => 'Manages student enrolment, academic records, attendance, examinations and reporting.',
                        'description' => '<p>Manages student enrolment, academic records, attendance, examinations, and reporting.</p>',
                        'features' => ['Student enrolment', 'Academic records', 'Attendance and examinations', 'Reporting'],
                    ],
                    [
                        'name' => 'Procurement Management Information System (PMIS)',
                        'slug' => 'procurement-management-information-system',
                        'icon' => 'fa-solid fa-file-contract',
                        'summary' => 'Automates procurement planning, vendor management, tendering, purchasing and contract administration.',
                        'description' => '<p>Automates procurement planning, vendor management, tendering, purchasing, and contract administration.</p>',
                        'features' => ['Procurement planning', 'Vendor management', 'Tendering and purchasing', 'Contract administration'],
                    ],
                    [
                        'name' => 'Purchase Management Systems',
                        'slug' => 'purchase-management-systems',
                        'icon' => 'fa-solid fa-cart-shopping',
                        'summary' => 'Improve purchasing workflows, inventory control, supplier performance and expenditure tracking.',
                        'description' => '<p>Improve purchasing workflows, inventory control, supplier performance, and expenditure tracking.</p>',
                        'features' => ['Purchasing workflows', 'Inventory control', 'Supplier performance', 'Expenditure tracking'],
                    ],
                    [
                        'name' => 'Monitoring, Evaluation, Accountability & Learning (MEAL) Solutions',
                        'slug' => 'meal-solutions',
                        'icon' => 'fa-solid fa-chart-line',
                        'summary' => 'Collect, analyze, visualize and report on program performance data for evidence-based decisions and donor reporting.',
                        'description' => '<p>Enable organizations to collect, analyze, visualize, and report on program performance data to support evidence-based decision-making and donor reporting.</p>',
                        'features' => ['Data collection', 'Analysis and visualization', 'Program performance reporting', 'Donor reporting'],
                    ],
                    [
                        'name' => 'Agricultural Enterprise Management Information System (AE-MIS)',
                        'slug' => 'agricultural-enterprise-management-information-system',
                        'icon' => 'fa-solid fa-seedling',
                        'summary' => 'A single, authoritative platform for agricultural enterprise data, mapping and knowledge management.',
                        'description' => '<p>A single, authoritative platform comprising a web portal for consolidation, workflow, and reporting; an offline-capable Android data-collection application that captures GPS coordinates and beneficiary details; a geo-map interface that plots supported enterprises and equipment nationally; and a knowledge-management module that surfaces evidence on livelihood improvement, job creation, and stakeholder satisfaction.</p>',
                        'features' => ['Web portal for consolidation, workflow and reporting', 'Offline-capable Android data collection with GPS', 'National geo-map of enterprises and equipment', 'Knowledge-management module'],
                    ],
                    [
                        'name' => 'SUSU Management System (SMS)',
                        'slug' => 'susu-management-system',
                        'icon' => 'fa-solid fa-piggy-bank',
                        'summary' => 'A complete, modular platform for savings groups — members, savings, loans, mobile money and accounting.',
                        'description' => '<p>A complete, modular platform for member administration, compliance, savings management, loan lifecycle management, mobile-money integration (including MTN MoMo and Airtel Money), transaction management, built-in accounting and reporting, automated member communication, administrative controls, data security, third-party system integration, and data migration support.</p>',
                        'features' => ['Member administration and compliance', 'Savings and loan lifecycle management', 'MTN MoMo and Airtel Money integration', 'Built-in accounting, reporting and data migration'],
                    ],
                    [
                        'name' => 'Web Development',
                        'slug' => 'website-design-development',
                        'icon' => 'fa-solid fa-laptop-code',
                        'summary' => 'Design and development of websites and web applications.',
                        'description' => '<p>WellDone ICT Services designs and develops websites and web applications for organizations of every size.</p>',
                        'features' => ['Website design', 'Web application development', 'Hosting support', 'Ongoing updates'],
                    ],
                    [
                        'name' => 'Software & Hardware Maintenance and ICT Support',
                        'slug' => 'ict-consultancy-support',
                        'icon' => 'fa-solid fa-screwdriver-wrench',
                        'summary' => 'Software and hardware maintenance and other ICT-related support.',
                        'description' => '<p>Software and hardware maintenance and other ICT-related support, keeping the systems organizations depend on running.</p>',
                        'features' => ['Software maintenance', 'Hardware maintenance', 'ICT support', 'Custom ICT solutions on request'],
                    ],
                ],
            ],

            // ------------------------------------------ Logistics & procurement
            [
                'slug' => 'procurement-logistics',
                'name' => 'WellDone Logistics & Procurement Services',
                'tagline' => 'Reliable, integrated procurement, supply, transportation and logistics solutions.',
                'icon' => 'fa-solid fa-truck-fast',
                'accent_color' => '#0b3c91',
                'is_featured' => true,
                'summary' => 'Integrated procurement, supply, transportation and logistics solutions for public institutions, private companies, NGOs, development partners and other organizations.',
                'description' => '<p>WellDone Logistics and Procurement Services provides reliable and integrated procurement, supply, transportation, and logistics solutions to public institutions, private companies, NGOs, development partners, and other organizations. The division supports clients with sourcing and purchasing, supplier coordination, transportation, warehousing, inventory management, and the delivery of goods and materials.</p><p>By combining efficient procurement processes with dependable logistics and supply-chain management, WellDone helps clients reduce operational delays, improve cost efficiency, and ensure the timely delivery of goods and services.</p>',
                'capabilities' => '<p>The company is committed to professionalism, transparency, reliability, and customer satisfaction, providing tailored solutions that meet each client\'s operational requirements.</p>',
                'highlights' => [
                    'Sourcing and purchasing',
                    'Supplier coordination',
                    'Transportation and delivery of goods and materials',
                    'Warehousing and inventory management',
                ],
                'services' => [
                    [
                        'name' => 'Sourcing & Purchasing',
                        'slug' => 'strategic-procurement-sourcing',
                        'icon' => 'fa-solid fa-cart-flatbed',
                        'summary' => 'Efficient procurement processes that source and purchase goods to each client\'s requirements.',
                        'description' => '<p>We support clients with sourcing and purchasing, combining efficient procurement processes with transparency and cost efficiency.</p>',
                        'features' => ['Sourcing to specification', 'Purchasing', 'Cost efficiency', 'Transparent processes'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Supplier Coordination',
                        'slug' => 'supplier-coordination',
                        'icon' => 'fa-solid fa-handshake',
                        'summary' => 'Coordination with suppliers so goods and services arrive as agreed.',
                        'description' => '<p>We coordinate suppliers on our clients\' behalf, reducing operational delays and keeping supply on schedule.</p>',
                        'features' => ['Supplier liaison', 'Order follow-up', 'Delivery scheduling', 'Quality checks'],
                    ],
                    [
                        'name' => 'Transportation & Delivery',
                        'slug' => 'transportation-distribution',
                        'icon' => 'fa-solid fa-truck',
                        'summary' => 'Dependable transportation and the timely delivery of goods and materials.',
                        'description' => '<p>Dependable transportation and logistics that ensure the timely delivery of goods, materials and services to where they are needed.</p>',
                        'features' => ['Transportation', 'Delivery of goods and materials', 'Timely delivery', 'Logistics coordination'],
                    ],
                    [
                        'name' => 'Warehousing & Inventory Management',
                        'slug' => 'warehousing-inventory-management',
                        'icon' => 'fa-solid fa-warehouse',
                        'summary' => 'Warehousing and inventory management as part of integrated supply-chain management.',
                        'description' => '<p>Warehousing and inventory management that support dependable supply-chain management for our clients.</p>',
                        'features' => ['Warehousing', 'Inventory management', 'Stock control', 'Supply-chain management'],
                    ],
                ],
            ],

            // -------------------------------------------------------- Mining
            [
                'slug' => 'mining-quarry',
                'name' => 'WellDone Mining Services',
                'tagline' => 'Mining operations, equipment rentals and responsible mineral trading.',
                'icon' => 'fa-solid fa-mountain',
                'accent_color' => '#8a6d16',
                'is_featured' => true,
                'summary' => 'Specialized services and support to the mining sector, focusing on mining operations, equipment rentals and responsible mineral trading.',
                'description' => '<p>WellDone Mining Services provides specialized services and support to the mining sector, focusing on mining operations, equipment rentals, and responsible mineral trading. The company also engages directly in mining activities and works with mining operators, partners, and other stakeholders to facilitate efficient, reliable mining operations.</p><p>As part of its mining services, WellDone provides excavator rental services exclusively for mining operations, supporting activities such as excavation, earthmoving, and other approved mining-related operations. The company also engages in the purchase of gold from legitimate and authorized sources, subject to applicable laws, regulations, verification, and compliance requirements.</p>',
                'capabilities' => '<p>WellDone Mining Services is committed to professionalism, operational efficiency, transparency, and responsible business practices, while developing strategic partnerships and investment opportunities that contribute to the growth of the mining sector and the wider economy.</p>',
                'highlights' => [
                    'Direct engagement in mining activities',
                    'Excavator rental exclusively for mining operations',
                    'Gold purchase from legitimate and authorized sources',
                    'Strategic partnerships and investment in the mining sector',
                ],
                'services' => [
                    [
                        'name' => 'Mining Operations',
                        'slug' => 'quarry-operations-management',
                        'icon' => 'fa-solid fa-helmet-safety',
                        'summary' => 'Direct mining activities and support to mining operators, partners and stakeholders.',
                        'description' => '<p>The company engages directly in mining activities and works with mining operators, partners, and other stakeholders to facilitate efficient, reliable mining operations.</p>',
                        'features' => ['Direct mining activities', 'Support to mining operators', 'Partner collaboration', 'Efficient, reliable operations'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Excavator Rental for Mining',
                        'slug' => 'mining-equipment-plant-hire',
                        'icon' => 'fa-solid fa-truck-monster',
                        'summary' => 'Excavator rental services exclusively for mining operations.',
                        'description' => '<p>WellDone provides excavator rental services exclusively for mining operations, supporting activities such as excavation, earthmoving, and other approved mining-related operations.</p>',
                        'features' => ['Excavation', 'Earthmoving', 'Approved mining-related operations', 'For mining operations only'],
                    ],
                    [
                        'name' => 'Gold Purchasing & Mineral Trading',
                        'slug' => 'gold-purchasing-mineral-trading',
                        'icon' => 'fa-solid fa-coins',
                        'summary' => 'Responsible purchase of gold from legitimate and authorized sources.',
                        'description' => '<p>The company engages in the purchase of gold from legitimate and authorized sources, subject to applicable laws, regulations, verification, and compliance requirements.</p>',
                        'features' => ['Legitimate and authorized sources', 'Verification', 'Regulatory compliance', 'Responsible mineral trading'],
                    ],
                ],
            ],

            // ------------------------------------------------------- Medical
            [
                'slug' => 'medical-equipment',
                'name' => 'WellDone Medical Supply Services',
                'tagline' => 'Reliable medical equipment, supplies and healthcare products.',
                'icon' => 'fa-solid fa-stethoscope',
                'accent_color' => '#1a7fa8',
                'is_featured' => true,
                'summary' => 'Sourcing, importing, supplying and distributing quality medical equipment, supplies and healthcare products to hospitals, clinics, pharmacies, laboratories, healthcare organizations, NGOs and other institutional clients.',
                'description' => '<p>WellDone Medical Supply Services provides reliable medical equipment, supplies, and healthcare products to hospitals, clinics, pharmacies, laboratories, healthcare organizations, NGOs, and other institutional clients. The division focuses on sourcing, importing, supplying, and distributing quality medical products to meet the operational and healthcare needs of its clients.</p><p>WellDone works to establish relationships with reputable manufacturers, suppliers, and healthcare partners to facilitate access to medical equipment, diagnostic supplies, laboratory materials, consumables, and other essential healthcare products. The company also supports clients with procurement coordination and the delivery of medical supplies based on their specific requirements.</p>',
                'capabilities' => '<p>WellDone Medical Supply Services is committed to quality, reliability, timely delivery, regulatory compliance, and customer satisfaction, while supporting healthcare institutions and contributing to improved access to essential medical supplies and equipment in Liberia and the wider region.</p>',
                'highlights' => [
                    'Sourcing and importation of quality medical products',
                    'Medical equipment, diagnostic and laboratory supplies',
                    'Consumables and essential healthcare products',
                    'Procurement coordination and delivery to requirements',
                ],
                'services' => [
                    [
                        'name' => 'Medical Equipment Supply',
                        'slug' => 'hospital-equipment-supply',
                        'icon' => 'fa-solid fa-hospital',
                        'summary' => 'Medical equipment for hospitals, clinics and healthcare organizations from reputable manufacturers.',
                        'description' => '<p>We source, import and supply medical equipment from reputable manufacturers to hospitals, clinics, healthcare organizations, NGOs and other institutional clients.</p>',
                        'features' => ['Reputable manufacturers', 'Importation', 'Supply and distribution', 'Institutional clients'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Diagnostic & Laboratory Supplies',
                        'slug' => 'laboratory-equipment-reagents',
                        'icon' => 'fa-solid fa-flask',
                        'summary' => 'Diagnostic supplies and laboratory materials for laboratories and health facilities.',
                        'description' => '<p>We facilitate access to diagnostic supplies and laboratory materials for laboratories, pharmacies and health facilities.</p>',
                        'features' => ['Diagnostic supplies', 'Laboratory materials', 'Pharmacies and laboratories', 'Quality products'],
                    ],
                    [
                        'name' => 'Medical Consumables & Healthcare Products',
                        'slug' => 'medical-consumables-disposables',
                        'icon' => 'fa-solid fa-kit-medical',
                        'summary' => 'Consumables and other essential healthcare products.',
                        'description' => '<p>We supply consumables and other essential healthcare products to meet the operational and healthcare needs of our clients.</p>',
                        'features' => ['Consumables', 'Essential healthcare products', 'Reliable supply', 'Timely delivery'],
                    ],
                    [
                        'name' => 'Procurement Coordination & Delivery',
                        'slug' => 'medical-procurement-coordination-delivery',
                        'icon' => 'fa-solid fa-truck-medical',
                        'summary' => 'Procurement coordination and delivery of medical supplies based on each client\'s specific requirements.',
                        'description' => '<p>The company supports clients with procurement coordination and the delivery of medical supplies based on their specific requirements.</p>',
                        'features' => ['Procurement coordination', 'Delivery to requirements', 'Regulatory compliance', 'Customer satisfaction'],
                    ],
                ],
            ],

            // --------------------------------------------- Trading & investment
            [
                'slug' => 'trading-investment',
                'name' => 'WellDone Trading & Investment',
                'tagline' => 'Sourcing, value addition, distribution and trading of agricultural, energy, mining and consumer products.',
                'icon' => 'fa-solid fa-scale-balanced',
                'accent_color' => '#9b3d2e',
                'is_featured' => true,
                'summary' => 'Sourcing, purchasing, processing, value addition, distribution and trading of agricultural, energy, mining and consumer products, connecting producers, suppliers, mining companies, businesses and end consumers.',
                'description' => '<p>WellDone Trading and Investment focuses on the sourcing, purchasing, processing, value addition of applicable locally available products, distribution, and trading of agricultural, energy, mining, and consumer products.</p><p>The division identifies sustainable market opportunities and develops supply chains that connect producers, suppliers, mining companies, businesses, and end consumers.</p>',
                'capabilities' => '<p>The division identifies sustainable market opportunities and builds the supply chains that bring locally available products to market.</p>',
                'highlights' => [
                    'Sourcing and purchasing',
                    'Processing and value addition of local products',
                    'Distribution and trading',
                    'Agricultural, energy, mining and consumer products',
                ],
                'services' => [
                    [
                        'name' => 'Sourcing & Purchasing',
                        'slug' => 'trading-sourcing-purchasing',
                        'icon' => 'fa-solid fa-basket-shopping',
                        'summary' => 'Sourcing and purchasing of agricultural, energy, mining and consumer products.',
                        'description' => '<p>We source and purchase agricultural, energy, mining, and consumer products from producers and suppliers.</p>',
                        'features' => ['Agricultural products', 'Energy products', 'Mining products', 'Consumer products'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Processing & Value Addition',
                        'slug' => 'processing-value-addition',
                        'icon' => 'fa-solid fa-industry',
                        'summary' => 'Processing and value addition of applicable locally available products.',
                        'description' => '<p>We process and add value to applicable locally available products before they reach market.</p>',
                        'features' => ['Processing', 'Value addition', 'Locally available products', 'Market-ready goods'],
                    ],
                    [
                        'name' => 'Distribution & Trading',
                        'slug' => 'distribution-trading',
                        'icon' => 'fa-solid fa-boxes-stacked',
                        'summary' => 'Supply chains that connect producers, suppliers, mining companies, businesses and end consumers.',
                        'description' => '<p>The division identifies sustainable market opportunities and develops supply chains that connect producers, suppliers, mining companies, businesses, and end consumers.</p>',
                        'features' => ['Distribution', 'Trading', 'Sustainable market opportunities', 'Supply-chain development'],
                    ],
                ],
            ],
        ];
    }
}
