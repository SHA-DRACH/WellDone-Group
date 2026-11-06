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
                'slogan' => 'Well Done. Every Time. Anywhere.',
                'description' => 'A diversified Liberian business corporation delivering ICT services, logistics and procurement, mining services, medical supplies, and trading and investment.',
                'image' => '/images/hero/hero-group.svg',
                'primary_button_text' => 'Request a Quote',
                'primary_button_url' => '/request-a-quote',
                'secondary_button_text' => 'Explore Services',
                'secondary_button_url' => '/services',
            ],
            [
                'eyebrow' => 'Mining Services',
                'title' => 'Mining operations,',
                'highlight' => 'done responsibly.',
                'slogan' => 'Operations, equipment rental, mineral trading.',
                'description' => 'Direct mining activities, excavator rental exclusively for mining operations, and gold purchase from legitimate and authorized sources.',
                'image' => '/images/hero/hero-mining.svg',
                'primary_button_text' => 'Mining Services',
                'primary_button_url' => '/divisions/mining-quarry',
                'secondary_button_text' => 'View Projects',
                'secondary_button_url' => '/projects',
            ],
            [
                'eyebrow' => 'Logistics & Procurement',
                'title' => 'From source to site,',
                'highlight' => 'delivered on time.',
                'slogan' => 'Sourcing, transport, warehousing, delivery.',
                'description' => 'Sourcing and purchasing, supplier coordination, transportation, warehousing, inventory management and the delivery of goods and materials.',
                'image' => '/images/hero/hero-logistics.svg',
                'primary_button_text' => 'Logistics & Procurement',
                'primary_button_url' => '/divisions/procurement-logistics',
                'secondary_button_text' => 'Talk to Us',
                'secondary_button_url' => '/contact',
            ],
            [
                'eyebrow' => 'Medical Supply & ICT',
                'title' => 'Essential supplies',
                'highlight' => 'and smarter systems.',
                'slogan' => 'Medical supplies. Digital solutions.',
                'description' => 'Quality medical equipment, diagnostic and laboratory supplies for health institutions — plus HRMS, SIMS, PMIS, MEAL and other ICT systems built for your information needs.',
                'image' => '/images/hero/hero-medical.svg',
                'primary_button_text' => 'Medical Supplies',
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
