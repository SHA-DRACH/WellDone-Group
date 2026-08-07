<?php

namespace App\Services\Assistant;

use App\Models\Blog;
use App\Models\Career;
use App\Models\Certificate;
use App\Models\Division;
use App\Models\Download;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

/**
 * Everything the assistant is allowed to say, compiled into a flat list of
 * short entries.
 *
 * Two sources feed it. The site's own records — divisions, services, projects,
 * vacancies, downloads, FAQs, articles, settings — so an answer about what the
 * company does can never drift from what the site publishes. And the task
 * guides below, which are the part a visitor actually asks for: not "what is a
 * quotation" but "how do I get one, and why does it want me to sign in first".
 *
 * Each entry carries the URL of the page it came from, so every answer can end
 * with somewhere to click rather than a dead end.
 */
class KnowledgeBase
{
    public const CACHE_KEY = 'assistant.knowledge';

    /**
     * @return array<int, array{kind: string, title: string, body: string, url: ?string, label: ?string, keywords: array<int, string>}>
     */
    public function all(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            (int) config('assistant.cache_ttl', 900),
            fn (): array => $this->compile()
        );
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<int, array<string, mixed>> */
    protected function compile(): array
    {
        return array_merge(
            $this->guides(),
            $this->company(),
            $this->divisions(),
            $this->services(),
            $this->projects(),
            $this->careers(),
            $this->downloads(),
            $this->faqs(),
            $this->articles(),
            $this->credentials(),
        );
    }

    // ------------------------------------------------------------ Task guides

    /**
     * How to actually do things here.
     *
     * These are hand-written rather than derived, because they describe rules
     * that live in routes and middleware rather than in any table — chiefly
     * that browsing is open to everyone but submitting anything requires a
     * verified account. That single rule is behind most of the confusion a
     * visitor arrives with, so it is spelled out in every guide it touches.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function guides(): array
    {
        $company = setting('company.name', 'WellDone Group Inc.');

        $guides = [
            [
                'title' => 'Request a quotation',
                'keywords' => ['quote', 'quotation', 'pricing', 'price', 'estimate', 'cost', 'tender', 'proposal', 'rfq', 'hire', 'engage'],
                'body' => 'Open the Request a Quote page and work through the short wizard: choose the division and the services you need, describe the scope, add your timeline and budget if you have one, and attach drawings or specifications. You must be signed in with a verified email address before the form can be submitted — this is what lets us tie the request to a real contact and lets you follow it afterwards. Once it is in, you get a reference number and the request appears under My Requests in your account, where you can watch its status change as the team works on it.',
                'url' => route('site.quote.create'),
                'label' => 'Request a quote',
            ],
            [
                'title' => 'Track a quotation request you already sent',
                'keywords' => ['track', 'status', 'progress', 'reference', 'my request', 'my quote', 'follow up', 'update'],
                'body' => 'Sign in and open My Requests in your account area. Every quotation request you have submitted is listed there with its reference number and current status, and opening one shows the full detail you sent along with any response from the team. Job applications and contact enquiries are tracked the same way, under their own tabs.',
                'url' => route('account.quotes'),
                'label' => 'My requests',
            ],
            [
                'title' => 'Apply for a job',
                'keywords' => ['job', 'jobs', 'career', 'careers', 'vacancy', 'vacancies', 'apply', 'application', 'hiring', 'recruitment', 'employment', 'work', 'cv', 'resume', 'position'],
                'body' => 'Open the Careers page to see every position currently open, with its department, location and employment type. Anyone can read a vacancy, but applying needs a verified account, so register or sign in first. The application form on each vacancy takes your details and lets you upload a CV and supporting documents. After you apply, the application shows up under My Applications in your account so you can see that it was received.',
                'url' => route('site.careers.index'),
                'label' => 'See open positions',
            ],
            [
                'title' => 'Contact the company',
                'keywords' => ['contact', 'reach', 'talk', 'speak', 'call', 'phone', 'email', 'address', 'office', 'location', 'visit', 'enquiry', 'inquiry', 'message', 'support', 'help'],
                'body' => 'The Contact page carries the office address, a map, the phone numbers, the departmental email addresses and the office hours, plus a form that reaches the right team. Sending the form needs a verified account so the reply can be traced back to you; the phone numbers and email addresses on that page need nothing at all. For anything commercial, a quotation request usually gets a faster and more useful answer than a general enquiry.',
                'url' => route('site.contact'),
                'label' => 'Contact us',
            ],
            [
                'title' => 'Create an account, and why one is needed',
                'keywords' => ['account', 'register', 'registration', 'sign up', 'signup', 'create account', 'join', 'verify', 'verification', 'confirm email'],
                'body' => 'Registration is open to anyone and takes an email address and a password, or one tap with Continue with Google. Browsing the site never requires an account — an account is only needed to submit something: a quotation request, a job application or a contact enquiry. That is deliberate, so every request we receive is attached to a confirmed email address and you can follow your own submissions afterwards. New accounts are ordinary client accounts and carry no staff access.',
                'url' => route('register'),
                'label' => 'Create an account',
            ],
            [
                'title' => 'Verify your email address',
                'keywords' => ['verify', 'verification', 'confirm', 'code', 'otp', 'activation', 'not verified', 'email link'],
                'body' => 'After registering you get a verification email. If the link is awkward to use — which is common when mail opens in a phone browser that is not signed in — ask for a six-digit code instead on the code verification page and type it in. Until the address is verified you can browse everything but cannot submit a quotation request, a job application or a contact enquiry.',
                'url' => route('otp.verify'),
                'label' => 'Verify with a code',
            ],
            [
                'title' => 'Sign in, or reset a forgotten password',
                'keywords' => ['login', 'log in', 'sign in', 'signin', 'password', 'forgot', 'reset', 'locked out', 'cannot log in', 'google'],
                'body' => 'Sign in with your email and password, or with Continue with Google if that is how the account was created. If the password is gone, you can either use the emailed reset link or ask for a six-digit code on the password code page, which is easier on a phone. If you were given a first password by HR, the site will ask you to choose your own before letting you go anywhere else.',
                'url' => route('login'),
                'label' => 'Sign in',
            ],
            [
                'title' => 'Download the company profile, brochures and certificates',
                'keywords' => ['download', 'downloads', 'brochure', 'catalogue', 'catalog', 'profile', 'pdf', 'document', 'company profile', 'datasheet'],
                'body' => 'The Downloads page holds the company profile, division brochures, certificates and other documents, grouped by category. Most are open to everyone and download on a click.',
                'url' => route('site.downloads.index'),
                'label' => 'Downloads',
            ],
            [
                'title' => 'Subscribe to the newsletter',
                'keywords' => ['newsletter', 'subscribe', 'mailing list', 'updates', 'unsubscribe'],
                'body' => 'The signup box in the footer of every page takes an email address. You will get a confirmation email and the subscription only starts once you click the link in it. Every newsletter carries an unsubscribe link that works immediately.',
                'url' => null,
                'label' => null,
            ],
            [
                'title' => 'Browse divisions, services and past projects',
                'keywords' => ['division', 'divisions', 'service', 'services', 'what do you do', 'capability', 'capabilities', 'sector', 'project', 'projects', 'portfolio', 'work', 'experience', 'gallery', 'photos'],
                'body' => "The Divisions page lists the company's operating arms and what each one covers. The Services pages break that down into the individual services you can request a quotation for. The Projects portfolio shows completed and ongoing work with client, location, scope and status, and the Gallery holds photographs from the field.",
                'url' => route('site.divisions.index'),
                'label' => 'Our divisions',
            ],
            [
                'title' => 'Employee self-service portal',
                'keywords' => ['portal', 'employee', 'staff', 'payslip', 'leave', 'announcement', 'hr', 'my profile'],
                'body' => 'Staff with an employee record sign in with their normal account and use the portal for announcements and their own profile. Employment terms shown there are read-only and are maintained by HR. Client accounts do not have portal access — that is expected, not a fault.',
                'url' => null,
                'label' => null,
            ],
            [
                'title' => "About {$company}, and where to read more",
                'keywords' => ['about', 'company', 'who are you', 'history', 'background', 'leadership', 'management', 'team', 'csr', 'values', 'mission', 'vision', 'certification', 'accreditation', 'iso'],
                'body' => 'The About page covers the history and what the company stands for. Leadership introduces the management team, Corporate Social Responsibility covers the community work, and Certifications lists accreditations and their reference numbers. The FAQ page answers the questions that come up most often, and News carries announcements and articles.',
                'url' => route('site.about'),
                'label' => 'About us',
            ],
        ];

        return array_map(fn (array $g) => $g + ['kind' => 'guide'], $guides);
    }

    // ------------------------------------------------------------ Site content

    /** @return array<int, array<string, mixed>> */
    protected function company(): array
    {
        $name = setting('company.name', 'WellDone Group Inc.');
        $entries = [];

        $identity = array_filter([
            setting('company.tagline'),
            setting('company.about_short'),
            setting('company.motto') ? 'Motto: '.setting('company.motto').'.' : null,
            setting('company.slogan') ? 'Slogan: '.setting('company.slogan').'.' : null,
            setting('company.founded_year') ? 'Founded in '.setting('company.founded_year').'.' : null,
            setting('company.legal_name') ? 'Registered as '.setting('company.legal_name').'.' : null,
        ]);

        if ($identity !== []) {
            $entries[] = [
                'kind' => 'company',
                'title' => "About {$name}",
                'body' => $this->plain(implode(' ', $identity)),
                'url' => route('site.about'),
                'label' => 'About us',
                'keywords' => ['about', 'company', 'who', 'founded', 'history', 'motto', 'slogan'],
            ];
        }

        foreach (['mission' => 'Mission', 'vision' => 'Vision', 'csr_statement' => 'Corporate social responsibility'] as $key => $heading) {
            if ($value = setting("company.{$key}")) {
                $entries[] = [
                    'kind' => 'company',
                    'title' => "{$heading} of {$name}",
                    'body' => $this->plain($value),
                    'url' => $key === 'csr_statement' ? route('site.csr') : route('site.about'),
                    'label' => $key === 'csr_statement' ? 'Our CSR work' : 'About us',
                    'keywords' => [$key, $heading, 'values', 'purpose'],
                ];
            }
        }

        $address = array_filter([
            setting('contact.address_line1'),
            setting('contact.address_line2'),
            setting('contact.city'),
            setting('contact.postal_code'),
            setting('contact.country'),
        ]);

        $contact = array_filter([
            $address !== [] ? 'Office: '.implode(', ', $address).'.' : null,
            setting('contact.phone_primary') ? 'Phone: '.setting('contact.phone_primary').'.' : null,
            setting('contact.phone_secondary') ? 'Alternative phone: '.setting('contact.phone_secondary').'.' : null,
            setting('contact.whatsapp') ? 'WhatsApp: '.setting('contact.whatsapp').'.' : null,
            setting('contact.email_general') ? 'General email: '.setting('contact.email_general').'.' : null,
            setting('contact.email_sales') ? 'Sales and quotations: '.setting('contact.email_sales').'.' : null,
            setting('contact.email_hr') ? 'Recruitment and HR: '.setting('contact.email_hr').'.' : null,
            // Office hours are entered a line per day; keep those boundaries
            // once the whitespace collapse in plain() removes the newlines.
            setting('contact.office_hours')
                ? 'Office hours — '.trim((string) preg_replace('/\s*\R\s*/u', '; ', (string) setting('contact.office_hours')), '; ').'.'
                : null,
        ]);

        if ($contact !== []) {
            $entries[] = [
                'kind' => 'company',
                'title' => 'Contact details and office hours',
                'body' => $this->plain(implode(' ', $contact)),
                'url' => route('site.contact'),
                'label' => 'Contact us',
                'keywords' => ['contact', 'phone', 'call', 'email', 'address', 'office', 'hours', 'open', 'located', 'whatsapp', 'where'],
            ];
        }

        return $entries;
    }

    /** @return array<int, array<string, mixed>> */
    protected function divisions(): array
    {
        return Division::query()->active()->ordered()->get()
            ->map(fn (Division $division) => [
                'kind' => 'division',
                'title' => $division->name.' division',
                'body' => $this->plain(implode(' ', array_filter([
                    $division->tagline,
                    $division->summary ?: $division->description,
                ]))),
                'url' => $division->url,
                'label' => $division->name,
                'keywords' => array_merge(['division'], $this->words($division->name)),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function services(): array
    {
        return Service::query()->active()->with('division')->ordered()->limit(60)->get()
            ->map(fn (Service $service) => [
                'kind' => 'service',
                'title' => $service->name,
                'body' => $this->plain(implode(' ', array_filter([
                    $service->summary ?: $service->description,
                    $service->division ? 'Part of the '.$service->division->name.' division.' : null,
                    $service->is_quotable ? 'You can request a quotation for this service.' : null,
                    is_array($service->features) && $service->features !== []
                        ? 'Includes: '.implode(', ', array_slice($service->features, 0, 8)).'.'
                        : null,
                ]))),
                'url' => $service->url,
                'label' => $service->name,
                'keywords' => array_merge(['service'], $this->words($service->name)),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function projects(): array
    {
        // `client` is loaded because public_client_name consults it, and the
        // application runs with lazy loading disabled.
        return Project::query()->active()->with(['division', 'client'])->ordered()->limit(30)->get()
            ->map(fn (Project $project) => [
                'kind' => 'project',
                'title' => $project->title,
                'body' => $this->plain(implode(' ', array_filter([
                    $project->summary ?: $project->description,
                    $project->location ? 'Location: '.$project->location.'.' : null,
                    $project->status ? 'Status: '.str($project->status)->replace('_', ' ')->title().'.' : null,
                    $project->public_client_name ? 'Client: '.$project->public_client_name.'.' : null,
                    $project->division ? 'Delivered by the '.$project->division->name.' division.' : null,
                ]))),
                'url' => $project->url,
                'label' => $project->title,
                'keywords' => array_merge(['project', 'portfolio', 'work'], $this->words($project->title)),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function careers(): array
    {
        if (! setting('system.enable_careers', true)) {
            return [];
        }

        return Career::query()->open()->latest('id')->limit(30)->get()
            ->map(fn (Career $career) => [
                'kind' => 'career',
                'title' => 'Vacancy: '.$career->title,
                'body' => $this->plain(implode(' ', array_filter([
                    $career->department ? 'Department: '.$career->department.'.' : null,
                    $career->location ? 'Location: '.$career->location.'.' : null,
                    $career->employment_type ? 'Employment type: '.$career->employment_type_label.'.' : null,
                    $career->experience_level ? 'Experience level: '.$career->experience_level_label.'.' : null,
                    $career->closes_at ? 'Applications close on '.$career->closes_at->toFormattedDateString().'.' : null,
                    $career->description,
                ]))),
                'url' => $career->url,
                'label' => 'Apply for '.$career->title,
                'keywords' => array_merge(['job', 'vacancy', 'career', 'hiring', 'apply', 'position'], $this->words($career->title)),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function downloads(): array
    {
        return Download::query()->active()->ordered()->limit(40)->get()
            ->map(fn (Download $download) => [
                'kind' => 'download',
                'title' => 'Download: '.$download->title,
                'body' => $this->plain(implode(' ', array_filter([
                    $download->description,
                    $download->category ? 'Category: '.$download->category.'.' : null,
                    $download->requires_details
                        ? 'This one asks for your details before the file is released.'
                        : 'Downloads immediately, no details needed.',
                ]))),
                'url' => route('site.downloads.index'),
                'label' => 'Downloads',
                'keywords' => array_merge(['download', 'document', 'pdf', 'brochure'], $this->words($download->title)),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function faqs(): array
    {
        return Faq::query()->active()->ordered()->limit(60)->get()
            ->map(fn (Faq $faq) => [
                'kind' => 'faq',
                'title' => $faq->question,
                'body' => $this->plain($faq->answer),
                'url' => route('site.faqs'),
                'label' => 'All FAQs',
                'keywords' => $this->words($faq->question),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function articles(): array
    {
        return Blog::query()->published()->latest('published_at')->limit(12)->get()
            ->map(fn (Blog $blog) => [
                'kind' => 'article',
                'title' => 'News: '.$blog->title,
                'body' => $this->plain($blog->excerpt ?: $blog->body),
                'url' => $blog->url,
                'label' => 'Read the article',
                'keywords' => array_merge(['news', 'article', 'announcement', 'blog'], $this->words($blog->title)),
            ])
            ->all();
    }

    /**
     * Accreditations and the gallery. Both come up as trust questions —
     * "are you certified", "can I see the work" — so they are worth indexing
     * even though neither has much text.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function credentials(): array
    {
        $entries = [];

        $certificates = Certificate::query()->where('is_active', true)->orderBy('sort_order')->limit(20)->get();

        if ($certificates->isNotEmpty()) {
            $entries[] = [
                'kind' => 'company',
                'title' => 'Certifications and accreditations',
                'body' => $this->plain($certificates
                    ->map(fn (Certificate $c) => trim($c->title.($c->issuer ? ' (issued by '.$c->issuer.')' : '')))
                    ->implode('; ').'.'),
                'url' => route('site.certifications'),
                'label' => 'Our certifications',
                'keywords' => ['certification', 'certificate', 'accreditation', 'iso', 'licence', 'license', 'compliance', 'registered', 'qualified'],
            ];
        }

        $galleries = Gallery::query()->where('is_active', true)->orderBy('sort_order')->limit(15)->get();

        if ($galleries->isNotEmpty()) {
            $entries[] = [
                'kind' => 'gallery',
                'title' => 'Photo galleries',
                'body' => 'Photographs from our sites and operations, grouped into these albums: '
                    .$galleries->pluck('title')->implode(', ').'.',
                'url' => route('site.gallery.index'),
                'label' => 'Browse the gallery',
                'keywords' => ['gallery', 'photo', 'photos', 'picture', 'image', 'album', 'see'],
            ];
        }

        return $entries;
    }

    // ------------------------------------------------------------ Helpers

    /**
     * Editor content is HTML. Flatten it to a single line of readable text and
     * cap it, so one long service description cannot crowd everything else out
     * of the model's context.
     */
    protected function plain(?string $html, int $limit = 700): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit).'…' : $text;
    }

    /**
     * Significant words from a title, used as extra match terms.
     *
     * @return array<int, string>
     */
    protected function words(?string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower((string) $text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($words, fn (string $w) => mb_strlen($w) > 2));
    }
}
