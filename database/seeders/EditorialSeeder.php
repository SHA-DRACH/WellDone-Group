<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Tag;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;

/**
 * News categories, tags and a starter set of published articles.
 */
class EditorialSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Company News', 'slug' => 'company-news', 'color' => '#0b3c91', 'description' => 'Announcements and updates from across the group.'],
            ['name' => 'Projects', 'slug' => 'projects', 'color' => '#1e8e5a', 'description' => 'Progress and completion reports from active contracts.'],
            ['name' => 'Industry Insight', 'slug' => 'industry-insight', 'color' => '#d4af37', 'description' => 'Commentary on the sectors we operate in.'],
            ['name' => 'Health & Safety', 'slug' => 'health-safety', 'color' => '#c62828', 'description' => 'Safety performance, standards and practice.'],
            ['name' => 'Community & CSR', 'slug' => 'community-csr', 'color' => '#1a7fa8', 'description' => 'Our work with the communities that host us.'],
        ];

        foreach ($categories as $index => $category) {
            BlogCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $tagNames = [
            'Mining', 'Logistics', 'Healthcare', 'ICT', 'Liberia', 'Infrastructure',
            'Safety', 'Procurement', 'Training', 'Community',
        ];

        foreach ($tagNames as $name) {
            Tag::query()->firstOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name]);
        }

        $author = User::query()->whereHas('roles', fn ($q) => $q->where('name', Roles::CONTENT_MANAGER))->first()
            ?? User::query()->first();

        $categoryIds = BlogCategory::query()->pluck('id', 'slug');
        $tagIds = Tag::query()->pluck('id', 'name');

        $articles = [
            [
                'title' => 'WellDone Group completes 46 km road aggregate programme ahead of rainy season',
                'category' => 'projects',
                'tags' => ['Mining', 'Infrastructure', 'Liberia'],
                'published_at' => now()->subDays(6),
                'is_featured' => true,
                'excerpt' => 'The twelve-month supply programme for the Kakata–Bong Mines corridor closed with 180,000 m³ delivered and eleven of twelve monthly targets met.',
                'body' => '<p>The final consignment of graded base course was released this month, closing a twelve-month aggregate supply programme along the Kakata–Bong Mines corridor.</p><h2>Delivering against a laying programme</h2><p>Aggregate supply is rarely constrained by production capacity alone. The harder problem is synchronisation: material has to reach seventeen stockpile points in the sequence the contractor intends to lay it, and it has to be graded to specification when it arrives.</p><p>Our production scheduling was rebuilt around the contractor\'s laying programme rather than our own crushing convenience. Each consignment was held until grading verification cleared it, and released against a stockpile call-off rather than a fixed calendar.</p><h2>Weather as a planning input</h2><p>Two extended rain periods interrupted haulage. Because stockpile buffers had been sized for exactly this, laying continued through both. Eleven of twelve monthly targets were met in full; the twelfth closed within the following two weeks.</p><h2>What carries forward</h2><p>The programme confirmed something we already suspected: on long infrastructure contracts, predictability outperforms unit price. Clients can plan around a supplier who delivers what was promised, and that planning value compounds across a programme.</p>',
            ],
            [
                'title' => 'Four county health facilities equipped and handed over in Bong',
                'category' => 'projects',
                'tags' => ['Healthcare', 'Procurement', 'Training'],
                'published_at' => now()->subDays(14),
                'is_featured' => true,
                'excerpt' => 'Over 310 items of theatre, maternity and laboratory equipment installed, tested and handed over — with 68 staff trained and a 24-month maintenance contract in place.',
                'body' => '<p>A nine-month equipping programme across four Bong County health facilities has completed handover, covering operating theatre, maternity, laboratory and sterilisation departments.</p><h2>Equipment is not the deliverable</h2><p>It is worth stating plainly: crates arriving at a facility is not a delivered project. Equipment that is uninstalled, uncalibrated, or operated by staff who were never trained on it represents cost without capability — and it is a common outcome across the region.</p><p>Our scope therefore ran from specification review through sourcing, freight, clearance, site preparation, installation, acceptance testing and operator training, ending only at documented handover.</p><h2>Acceptance testing and training</h2><p>Every unit was tested against manufacturer specification before sign-off, and 68 clinical and technical staff completed operator training. Acceptance records were issued to each facility alongside the maintenance schedule.</p><h2>Twenty-four months of cover</h2><p>A preventive maintenance and calibration contract now covers the installed base, with held spares for the platforms supplied. Downtime on this equipment should be measured in days.</p>',
            ],
            [
                'title' => 'Why offline-first matters for school systems in Liberia',
                'category' => 'industry-insight',
                'tags' => ['ICT', 'Liberia', 'Training'],
                'published_at' => now()->subDays(22),
                'excerpt' => 'A management system that requires connectivity to record attendance will not be used. Designing for intermittent networks is not an optimisation — it is the requirement.',
                'body' => '<p>There is a familiar failure pattern in education technology deployments: a capable, well-specified platform is rolled out, used enthusiastically for a term, and quietly abandoned.</p><h2>The usual diagnosis is wrong</h2><p>The abandonment is often attributed to training gaps or resistance to change. In our experience the cause is more mechanical. If a teacher cannot mark a register because the network is down, they revert to paper — and once the paper register is authoritative for a week, the digital one is dead.</p><h2>Designing for the network you have</h2><p>The platform being deployed across twenty-two Monrovia schools records attendance, assessment and enrolment locally on the device, and synchronises when connectivity returns. Conflicts resolve on a last-write-wins basis for attendance, with explicit reconciliation for grades.</p><p>This is not a sophisticated architecture. It is simply an honest one about operating conditions.</p><h2>The measurable outcome</h2><p>Phase one schools are recording attendance digitally on days when they have no connectivity at all. That is the entire point.</p>',
            ],
            [
                'title' => 'Zero lost-time incidents across quarry operations for the fourth consecutive quarter',
                'category' => 'health-safety',
                'tags' => ['Safety', 'Mining'],
                'published_at' => now()->subDays(30),
                'excerpt' => 'Sustained safety performance across extraction, blasting and haulage operations reflects a documented management system — and the authority for any worker to stop a job.',
                'body' => '<p>Our quarry operations have now recorded four consecutive quarters without a lost-time incident, across extraction, drilling and blasting, crushing and haulage activities.</p><h2>What the record rests on</h2><p>Every site operates under a documented safety management system: pre-task risk assessment, controlled blast procedures with exclusion zone verification, mandatory PPE, and scheduled plant inspection.</p><p>The provision that matters most is the simplest. Any worker, at any level, may stop a job they believe is unsafe, without needing to justify it first and without consequence. A stopped job costs hours. The alternative costs considerably more.</p><h2>Where attention goes next</h2><p>Haulage remains our highest-exposure activity. Route risk assessment and driver fatigue management are the focus of the current review cycle.</p>',
            ],
            [
                'title' => 'Cold chain to Maryland County: fourteen consignments, no excursions',
                'category' => 'projects',
                'tags' => ['Logistics', 'Healthcare', 'Procurement'],
                'published_at' => now()->subDays(41),
                'excerpt' => 'Maintaining temperature integrity to a remote estate is a coordination problem more than a refrigeration one.',
                'body' => '<p>Fourteen consignments of agricultural and veterinary inputs reached a Maryland County estate this season with unbroken cold chain, verified by data logger on every shipment.</p><h2>Where cold chain actually breaks</h2><p>Refrigeration equipment rarely fails outright. Temperature excursions accumulate at handover points — on the apron, at the clearance queue, during transfer between vehicles. Each is short. Together they spoil consignments.</p><h2>Coordination as the control</h2><p>We pre-cleared documentation before vessel arrival, staged reefer capacity at the port, and scheduled onward transport so that no consignment waited for a vehicle. Loggers accompanied every shipment and were reviewed on arrival.</p><h2>The result</h2><p>Zero excursions across fourteen shipments, and an average origin-to-site lead time of thirty-two days.</p>',
            ],
            [
                'title' => 'Scholarship programme extends to three additional host communities',
                'category' => 'community-csr',
                'tags' => ['Community', 'Liberia', 'Training'],
                'published_at' => now()->subDays(55),
                'excerpt' => 'Secondary and vocational scholarships now reach students in three further communities adjacent to our operations.',
                'body' => '<p>Our scholarship programme has extended to three additional communities adjacent to WellDone Group operations, supporting secondary and vocational students with fees, materials and examination costs.</p><h2>Why vocational places matter</h2><p>Alongside secondary scholarships, the programme funds vocational training in welding, plant operation, electrical installation and biomedical technician skills. These are the skills our own divisions recruit for, and the shortage is real.</p><p>Several graduates of earlier cohorts now work within the group.</p><h2>Selection</h2><p>Awards are made by a committee including community representatives, on the basis of academic performance and financial need. Selection criteria are published locally.</p>',
            ],
            [
                'title' => 'Group opens expanded bonded warehouse capacity in Monrovia',
                'category' => 'company-news',
                'tags' => ['Logistics', 'Procurement', 'Liberia'],
                'published_at' => now()->subDays(68),
                'excerpt' => 'Additional bonded and temperature-controlled capacity shortens clearance-to-delivery times for client consignments.',
                'body' => '<p>WellDone Group has commissioned expanded bonded warehouse capacity in Monrovia, adding general and temperature-controlled storage to support growing clearance and distribution volumes.</p><h2>What the capacity changes</h2><p>Bonded capacity allows consignments to move out of the port promptly while duty and documentation are finalised, removing demurrage exposure and shortening clearance-to-delivery times.</p><p>The temperature-controlled section supports pharmaceutical, reagent and veterinary consignments that previously required immediate onward transport.</p><h2>Availability</h2><p>Capacity is available to clearance clients and on a standalone storage basis.</p>',
            ],
        ];

        foreach ($articles as $data) {
            $categorySlug = $data['category'];
            $tags = $data['tags'];
            unset($data['category'], $data['tags']);

            $blog = Blog::query()->updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, [
                    'blog_category_id' => $categoryIds[$categorySlug] ?? null,
                    'user_id' => $author?->id,
                    'status' => 'published',
                    'allow_comments' => true,
                ])
            );

            $blog->tags()->sync(collect($tags)->map(fn ($t) => $tagIds[$t] ?? null)->filter()->all());
        }

        $this->command?->info('Seeded '.count($articles).' articles across '.count($categories).' categories.');
    }
}
