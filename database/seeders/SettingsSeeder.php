<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds the editable site configuration. Existing values are preserved —
 * re-running only adds keys that are missing, so a re-seed never overwrites
 * content an administrator has changed.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $index => $definition) {
            [$group, $key] = explode('.', $definition['key'], 2);

            Setting::query()->firstOrCreate(
                ['group' => $group, 'key' => $key],
                [
                    'value' => $definition['value'] ?? null,
                    'type' => $definition['type'] ?? 'string',
                    'label' => $definition['label'],
                    'description' => $definition['description'] ?? null,
                    'options' => $definition['options'] ?? null,
                    'is_public' => $definition['is_public'] ?? true,
                    'sort_order' => $index,
                ]
            );
        }

        $this->command?->info('Seeded site settings.');
    }

    /** @return array<int, array<string, mixed>> */
    protected function definitions(): array
    {
        return [
            // ---------------------------------------------------- Company
            ['key' => 'company.name', 'label' => 'Company Name', 'value' => 'WellDone Group Inc.'],
            ['key' => 'company.legal_name', 'label' => 'Registered Legal Name', 'value' => 'WellDone Group Incorporated'],
            ['key' => 'company.slogan', 'label' => 'Slogan', 'value' => 'Partnership · Trust · Excellence'],
            ['key' => 'company.motto', 'label' => 'Motto', 'value' => 'Building a Better Tomorrow'],
            ['key' => 'company.tagline', 'label' => 'Short Tagline', 'value' => 'A diversified Liberian corporation in mining, logistics, medical supply and technology.'],
            ['key' => 'company.registration_number', 'label' => 'Business Registration Number', 'value' => ''],
            ['key' => 'company.tin', 'label' => 'Tax Identification Number', 'value' => ''],
            ['key' => 'company.founded_year', 'label' => 'Year Founded', 'value' => '2014', 'type' => 'integer'],
            /*
             * These default to the files shipped in public/images/brand.
             * Uploading a replacement from the CMS stores a public-disk path
             * instead, which takes precedence automatically.
             */
            ['key' => 'company.logo', 'label' => 'Logo', 'value' => '/images/brand/logo.png', 'type' => 'image',
                'description' => 'Shown in the site header, footer, admin panel and sign-in screen.'],
            ['key' => 'company.logo_light', 'label' => 'Logo (for dark backgrounds)', 'value' => '/images/brand/logo-light.png', 'type' => 'image',
                'description' => 'Optional. Falls back to the main logo when not supplied.'],
            ['key' => 'company.favicon', 'label' => 'Favicon', 'value' => '/images/brand/logo-mark.png', 'type' => 'image',
                'description' => 'Square image used as the browser tab icon.'],
            ['key' => 'company.watermark_header', 'label' => 'Show logo watermark on page banners', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'company.profile_pdf', 'label' => 'Company Profile (PDF)', 'value' => null, 'type' => 'file'],

            ['key' => 'company.about_short', 'label' => 'About — Short Introduction', 'type' => 'text',
                'value' => 'WellDone Group Inc. is a registered Liberian corporation delivering integrated solutions across mining and quarry operations, procurement and logistics, medical equipment supply, and information and communications technology.'],
            ['key' => 'company.history', 'label' => 'Company History', 'type' => 'richtext',
                'value' => '<p>WellDone Group Inc. was established to bring dependable, standards-driven delivery to sectors that underpin Liberia\'s growth. What began as a focused supply and logistics operation has grown into a diversified group serving government agencies, concession holders, health institutions and private enterprise across the country.</p><p>Today the group operates four complementary divisions, sharing one commitment: work that is done well, the first time.</p>'],
            ['key' => 'company.mission', 'label' => 'Mission Statement', 'type' => 'text',
                'value' => 'To deliver dependable, standards-compliant solutions that create measurable value for our clients, opportunity for our people, and lasting benefit for the communities we serve.'],
            ['key' => 'company.vision', 'label' => 'Vision Statement', 'type' => 'text',
                'value' => 'To be West Africa\'s most trusted diversified enterprise — the partner of choice wherever quality, integrity and delivery matter.'],
            ['key' => 'company.core_values', 'label' => 'Core Values', 'type' => 'json',
                'value' => json_encode([
                    ['title' => 'Integrity', 'icon' => 'fa-solid fa-shield-halved', 'description' => 'We do what we say, and we account for what we do.'],
                    ['title' => 'Excellence', 'icon' => 'fa-solid fa-award', 'description' => 'Standards are met before a job is called finished.'],
                    ['title' => 'Safety', 'icon' => 'fa-solid fa-helmet-safety', 'description' => 'Every operation is planned so people go home unharmed.'],
                    ['title' => 'Partnership', 'icon' => 'fa-solid fa-handshake', 'description' => 'We succeed when our clients and communities succeed.'],
                    ['title' => 'Accountability', 'icon' => 'fa-solid fa-clipboard-check', 'description' => 'Clear ownership, transparent reporting, no surprises.'],
                    ['title' => 'Innovation', 'icon' => 'fa-solid fa-lightbulb', 'description' => 'We adopt better methods and technology as they prove themselves.'],
                ])],
            ['key' => 'company.csr_statement', 'label' => 'CSR Statement', 'type' => 'text',
                'value' => 'We reinvest in the communities that host our operations through education support, health outreach, local employment and environmental stewardship.'],

            // ---------------------------------------------------- Contact
            ['key' => 'contact.address_line1', 'label' => 'Street Address', 'value' => 'Tubman Boulevard, Sinkor'],
            ['key' => 'contact.address_line2', 'label' => 'Address Line 2', 'value' => '12th Street'],
            ['key' => 'contact.city', 'label' => 'City', 'value' => 'Monrovia'],
            ['key' => 'contact.country', 'label' => 'Country', 'value' => 'Liberia'],
            ['key' => 'contact.postal_code', 'label' => 'Postal Code', 'value' => '1000'],
            ['key' => 'contact.phone_primary', 'label' => 'Primary Phone', 'value' => '+231 77 000 0000'],
            ['key' => 'contact.phone_secondary', 'label' => 'Secondary Phone', 'value' => '+231 88 000 0000'],
            ['key' => 'contact.whatsapp', 'label' => 'WhatsApp Number', 'value' => '231770000000',
                'description' => 'Digits only, including country code — used to build the wa.me quick-chat link.'],
            ['key' => 'contact.email_general', 'label' => 'General Email', 'value' => 'welldonegroupinc@gmail.com'],
            ['key' => 'contact.email_sales', 'label' => 'Sales Email', 'value' => 'sales@welldonegroup.com'],
            ['key' => 'contact.email_hr', 'label' => 'Careers Email', 'value' => 'careers@welldonegroup.com'],
            ['key' => 'contact.office_hours', 'label' => 'Office Hours', 'type' => 'text',
                'value' => "Monday – Friday: 8:00 AM – 5:00 PM\nSaturday: 9:00 AM – 1:00 PM\nSunday & Public Holidays: Closed"],
            ['key' => 'contact.map_embed', 'label' => 'Google Maps Embed URL', 'type' => 'text', 'value' => '',
                'description' => 'Paste the src URL from the Google Maps "Embed a map" share option.'],
            ['key' => 'contact.latitude', 'label' => 'Latitude', 'value' => '6.300774'],
            ['key' => 'contact.longitude', 'label' => 'Longitude', 'value' => '-10.797160'],

            // ---------------------------------------------------- Social
            ['key' => 'social.facebook', 'label' => 'Facebook URL', 'value' => ''],
            ['key' => 'social.linkedin', 'label' => 'LinkedIn URL', 'value' => ''],
            ['key' => 'social.twitter', 'label' => 'X (Twitter) URL', 'value' => ''],
            ['key' => 'social.instagram', 'label' => 'Instagram URL', 'value' => ''],
            ['key' => 'social.youtube', 'label' => 'YouTube URL', 'value' => ''],

            // ---------------------------------------------------- Homepage
            ['key' => 'home.about_heading', 'label' => 'About Section Heading', 'value' => 'One group. Four divisions. A single standard of delivery.'],
            ['key' => 'home.about_body', 'label' => 'About Section Body', 'type' => 'text',
                'value' => 'From quarry face to hospital ward to server room, WellDone Group brings the same discipline to every contract: qualified people, compliant processes, and delivery you can plan around.'],
            ['key' => 'home.why_choose_us', 'label' => 'Why Choose Us Items', 'type' => 'json',
                'value' => json_encode([
                    ['title' => 'Locally rooted, internationally sourced', 'icon' => 'fa-solid fa-globe', 'description' => 'Liberian teams on the ground, backed by vetted international supply lines.'],
                    ['title' => 'Regulatory compliance as standard', 'icon' => 'fa-solid fa-file-contract', 'description' => 'Licensed, insured and audited across every division we operate.'],
                    ['title' => 'End-to-end accountability', 'icon' => 'fa-solid fa-route', 'description' => 'One contract, one point of contact, from procurement through commissioning.'],
                    ['title' => 'Technical depth', 'icon' => 'fa-solid fa-microchip', 'description' => 'Certified engineers and technicians across mining, biomedical and ICT disciplines.'],
                ])],
            ['key' => 'home.stats', 'label' => 'Statistics Counters', 'type' => 'json',
                'value' => json_encode([
                    ['value' => 12, 'suffix' => '+', 'label' => 'Years of operation', 'icon' => 'fa-solid fa-calendar-check'],
                    ['value' => 240, 'suffix' => '+', 'label' => 'Projects delivered', 'icon' => 'fa-solid fa-diagram-project'],
                    ['value' => 85, 'suffix' => '+', 'label' => 'Institutional clients', 'icon' => 'fa-solid fa-building-columns'],
                    ['value' => 310, 'suffix' => '+', 'label' => 'People employed', 'icon' => 'fa-solid fa-users'],
                ])],
            ['key' => 'home.cta_heading', 'label' => 'Call-to-action Heading', 'value' => 'Have a project in mind?'],
            ['key' => 'home.cta_body', 'label' => 'Call-to-action Body', 'type' => 'text',
                'value' => 'Tell us what you need delivered. Our team will review the scope and return a written quotation.'],

            // ---------------------------------------------------- SEO
            ['key' => 'seo.default_title', 'label' => 'Default Meta Title', 'value' => 'WellDone Group Inc. — Mining, Logistics, Medical Supply & ICT in Liberia'],
            ['key' => 'seo.default_description', 'label' => 'Default Meta Description', 'type' => 'text',
                'value' => 'WellDone Group Inc. is a registered Liberian corporation delivering mining and quarry operations, procurement and logistics, medical equipment supply, and ICT solutions.'],
            ['key' => 'seo.default_keywords', 'label' => 'Default Meta Keywords', 'type' => 'text',
                'value' => 'Liberia mining, quarry Liberia, procurement Liberia, logistics Monrovia, medical equipment Liberia, ICT Liberia, WellDone Group'],
            ['key' => 'seo.default_og_image', 'label' => 'Default Social Share Image', 'type' => 'image', 'value' => null],
            ['key' => 'seo.google_analytics_id', 'label' => 'Google Analytics Measurement ID', 'value' => '', 'is_public' => false],
            ['key' => 'seo.google_site_verification', 'label' => 'Google Site Verification', 'value' => '', 'is_public' => false],

            // ---------------------------------------------------- System
            ['key' => 'system.maintenance_notice', 'label' => 'Site-wide Notice', 'type' => 'text', 'value' => '',
                'description' => 'Displayed as a banner across the public site while not empty.'],
            ['key' => 'system.enable_blog_comments', 'label' => 'Enable Article Comments', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'system.enable_newsletter', 'label' => 'Enable Newsletter Signup', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'system.enable_careers', 'label' => 'Enable Careers Portal', 'type' => 'boolean', 'value' => '1'],
            ['key' => 'system.items_per_page', 'label' => 'Items Per Page', 'type' => 'integer', 'value' => '9'],
            ['key' => 'system.notify_quotes_to', 'label' => 'Quote Notification Recipient', 'value' => 'sales@welldonegroup.com', 'is_public' => false],
            ['key' => 'system.notify_contact_to', 'label' => 'Contact Notification Recipient', 'value' => 'welldonegroupinc@gmail.com', 'is_public' => false],
            ['key' => 'system.notify_applications_to', 'label' => 'Application Notification Recipient', 'value' => 'careers@welldonegroup.com', 'is_public' => false],

            // ---------------------------------------------------- Assistant
            ['key' => 'assistant.enabled', 'label' => 'Enable the Site Assistant', 'type' => 'boolean', 'value' => '1',
                'description' => 'The chat helper in the corner of every public page. It answers from published site content only.'],
            ['key' => 'assistant.name', 'label' => 'Assistant Name', 'value' => 'WellDone Assistant',
                'description' => 'Shown on the chat window and used when it introduces itself.'],
            ['key' => 'assistant.greeting', 'label' => 'Opening Message', 'type' => 'text',
                'value' => 'Hello. I can help you find your way around the site — request a quotation, apply for a job, or reach the right team. What are you looking for?'],
            ['key' => 'assistant.suggestions', 'label' => 'Suggested Questions', 'type' => 'json',
                'value' => '["How do I request a quotation?","What services do you offer?","Are you hiring?","How do I contact you?"]',
                'description' => 'Up to four starter questions offered as buttons. A JSON array of strings.'],

            // ---------------------------------------------------- Homepage welcome video
            ['key' => 'home.welcome_video_heading', 'label' => 'Welcome Video — Heading',
                'value' => 'Welcome to WellDone Group'],
            ['key' => 'home.welcome_video_body', 'label' => 'Welcome Video — Introduction', 'type' => 'text',
                'value' => 'A minute with the people behind our four divisions — the quarry crews, logistics teams, biomedical engineers and ICT specialists who deliver for our clients across Liberia.'],
            ['key' => 'home.welcome_video', 'label' => 'Welcome Video (upload)', 'type' => 'video', 'value' => null,
                'description' => 'MP4, between 30 seconds and 1 minute. The section is hidden until a video or link is added.'],
            ['key' => 'home.welcome_video_url', 'label' => 'Welcome Video (YouTube or Vimeo link)', 'type' => 'video_url', 'value' => null,
                'description' => 'Used when no file is uploaded. Keep the clip between 30 seconds and 1 minute.'],
            ['key' => 'home.welcome_video_poster', 'label' => 'Welcome Video — Cover Image', 'type' => 'image', 'value' => null,
                'description' => 'Shown before the video plays.'],

            /*
             * ---------------------------------------------------- Finance
             *
             * Statutory figures are starting points, not advice. The income
             * tax bands are the Liberia Revenue Code's annual bands in
             * Liberian dollars; payroll converts salaries at the exchange
             * rate entered on each run. Finance should confirm every figure
             * against current LRA and NASSCORP rules before the first run.
             */
            ['key' => 'finance.currency', 'label' => 'Base Currency', 'value' => 'USD', 'is_public' => false,
                'description' => 'ISO code used for invoices, expenses, budgets and reports.'],
            ['key' => 'finance.tax_bands', 'label' => 'Income Tax Bands (annual)', 'type' => 'json', 'is_public' => false,
                'value' => json_encode([
                    ['from' => 0, 'rate' => 0],
                    ['from' => 70000, 'rate' => 5],
                    ['from' => 200000, 'rate' => 15],
                    ['from' => 800000, 'rate' => 25],
                ]),
                'description' => 'Progressive bands on annual taxable income: each rate applies to the income above "from". Confirm against current LRA rules.'],
            ['key' => 'finance.tax_band_currency', 'label' => 'Currency of the Tax Bands', 'value' => 'LRD', 'is_public' => false,
                'description' => 'When it differs from an employee\'s pay currency, payroll converts at the exchange rate entered on the run.'],
            ['key' => 'finance.exchange_rate', 'label' => 'Default Exchange Rate (tax band currency per 1 base currency)', 'value' => '', 'is_public' => false,
                'description' => 'e.g. how many LRD to one USD. Pre-fills each payroll run, where it can be changed.'],
            ['key' => 'finance.social_security_employee_rate', 'label' => 'Social Security — Employee Contribution (%)', 'value' => '4', 'is_public' => false,
                'description' => 'Deducted from gross pay. Confirm the current NASSCORP rate.'],
            ['key' => 'finance.social_security_employer_rate', 'label' => 'Social Security — Employer Contribution (%)', 'value' => '6', 'is_public' => false,
                'description' => 'Paid by the company on top of gross pay; counted in payroll cost.'],
            ['key' => 'finance.invoice_tax_rate', 'label' => 'Default Invoice Tax Rate (%)', 'value' => '0', 'is_public' => false,
                'description' => 'Pre-filled on new invoices; can be changed per invoice.'],
            ['key' => 'finance.invoice_due_days', 'label' => 'Invoice Payment Terms (days)', 'type' => 'integer', 'value' => '30', 'is_public' => false],
            ['key' => 'finance.invoice_payment_details', 'label' => 'Payment Instructions on Invoices', 'type' => 'text', 'value' => '', 'is_public' => false,
                'description' => 'Bank name, account name and number, mobile money — printed at the foot of every invoice.'],
            ['key' => 'finance.invoice_footer', 'label' => 'Invoice Footer Note', 'type' => 'text', 'is_public' => false,
                'value' => 'Thank you for your business. Please quote the invoice number with your payment.'],
        ];
    }
}
