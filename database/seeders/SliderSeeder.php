<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    /**
     * Slide artwork ships with the application under public/images/hero, so
     * the paths are root-relative rather than public-disk keys. Uploading a
     * replacement from the CMS stores a normal disk path instead.
     */
    public function run(): void
    {
        $slides = [
            [
                'eyebrow' => 'WellDone Group Inc.',
                'title' => 'Building Liberia.',
                'highlight' => 'Delivering Excellence.',
                'slogan' => 'One group. Four divisions. A single standard.',
                'description' => 'A registered Liberian corporation delivering mining and quarry operations, procurement and logistics, medical equipment supply, and information technology — under one accountable contract.',
                'image' => '/images/hero/hero-group.svg',
                'primary_button_text' => 'Request a Quote',
                'primary_button_url' => '/request-a-quote',
                'secondary_button_text' => 'Explore Services',
                'secondary_button_url' => '/services',
            ],
            [
                'eyebrow' => 'Mining & Quarry',
                'title' => 'Aggregate you can',
                'highlight' => 'build a programme around.',
                'slogan' => 'Graded, tested, delivered on schedule.',
                'description' => 'Licensed quarry operations supplying crushed stone, sand and base course with per-consignment test data and haulage to site.',
                'image' => '/images/hero/hero-mining.svg',
                'primary_button_text' => 'Mining & Quarry',
                'primary_button_url' => '/divisions/mining-quarry',
                'secondary_button_text' => 'View Projects',
                'secondary_button_url' => '/projects',
            ],
            [
                'eyebrow' => 'Procurement & Logistics',
                'title' => 'From source to site,',
                'highlight' => 'one point of contact.',
                'slogan' => 'Sourcing, freight, clearance, delivery.',
                'description' => 'International sourcing, freight forwarding, licensed customs clearance, warehousing and nationwide distribution — coordinated end to end.',
                'image' => '/images/hero/hero-logistics.svg',
                'primary_button_text' => 'Procurement & Logistics',
                'primary_button_url' => '/divisions/procurement-logistics',
                'secondary_button_text' => 'Talk to Us',
                'secondary_button_url' => '/contact',
            ],
            [
                'eyebrow' => 'Medical Equipment & ICT',
                'title' => 'Equipment that works',
                'highlight' => 'on day one — and after.',
                'slogan' => 'Installed, commissioned, maintained.',
                'description' => 'Certified hospital and laboratory equipment with installation, operator training and maintenance contracts — plus the ICT systems that run alongside them.',
                'image' => '/images/hero/hero-medical.svg',
                'primary_button_text' => 'Medical Equipment',
                'primary_button_url' => '/divisions/medical-equipment',
                'secondary_button_text' => 'ICT Services',
                'secondary_button_url' => '/divisions/ict-services',
            ],
        ];

        foreach ($slides as $index => $slide) {
            Slider::query()->updateOrCreate(
                ['title' => $slide['title']],
                array_merge($slide, ['sort_order' => $index, 'is_active' => true])
            );
        }

        $this->command?->info('Seeded '.count($slides).' hero slides.');
    }
}
