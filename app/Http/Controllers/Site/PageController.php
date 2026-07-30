<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CsrInitiative;
use App\Models\Division;
use App\Models\Faq;
use App\Models\Milestone;
use App\Models\TeamMember;
use Illuminate\Contracts\View\View;

/**
 * The static-content corporate pages. Their copy comes from settings and
 * the small supporting tables, so an administrator can edit them without a
 * developer.
 */
class PageController extends Controller
{
    public function about(): View
    {
        seo()->set([
            'title' => 'About Us — '.setting('company.name'),
            'description' => setting('company.about_short'),
        ]);

        return view('site.pages.about', [
            'milestones' => Milestone::query()->active()->ordered()->get(),
            'leadership' => TeamMember::query()->active()->leadership()->ordered()->limit(6)->get(),
            'certificates' => Certificate::query()->active()->ordered()->limit(6)->get(),
            'divisions' => Division::query()->active()->ordered()->get(),
            'values' => $this->coreValues(),
        ]);
    }

    public function leadership(): View
    {
        seo()->set([
            'title' => 'Leadership & Team — '.setting('company.name'),
            'description' => 'Meet the leadership team directing WellDone Group\'s four business divisions.',
        ]);

        return view('site.pages.leadership', [
            'leadership' => TeamMember::query()->active()->leadership()->ordered()->with('division:id,name,slug')->get(),
            'team' => TeamMember::query()->active()->where('is_leadership', false)->ordered()->with('division:id,name,slug')->get(),
        ]);
    }

    public function csr(): View
    {
        seo()->set([
            'title' => 'Corporate Social Responsibility — '.setting('company.name'),
            'description' => setting('company.csr_statement'),
        ]);

        return view('site.pages.csr', [
            'initiatives' => CsrInitiative::query()->active()->ordered()->get(),
        ]);
    }

    public function certifications(): View
    {
        seo()->set([
            'title' => 'Certifications & Accreditations — '.setting('company.name'),
            'description' => 'Registrations, licences and accreditations held by WellDone Group Inc.',
        ]);

        return view('site.pages.certifications', [
            'certificates' => Certificate::query()->active()->ordered()->get(),
        ]);
    }

    public function faqs(): View
    {
        seo()->set([
            'title' => 'Frequently Asked Questions — '.setting('company.name'),
            'description' => 'Answers to common questions about our services, delivery and contracting.',
        ]);

        $faqs = Faq::query()->active()->ordered()->get()->groupBy('category');

        return view('site.pages.faqs', ['groups' => $faqs]);
    }

    public function privacy(): View
    {
        seo()->set(['title' => 'Privacy Policy — '.setting('company.name'), 'robots' => 'noindex, follow']);

        return view('site.pages.privacy');
    }

    public function terms(): View
    {
        seo()->set(['title' => 'Terms of Use — '.setting('company.name'), 'robots' => 'noindex, follow']);

        return view('site.pages.terms');
    }

    /** @return array<int, array<string, string>> */
    protected function coreValues(): array
    {
        $values = setting('company.core_values', []);

        return is_array($values) ? $values : [];
    }
}
