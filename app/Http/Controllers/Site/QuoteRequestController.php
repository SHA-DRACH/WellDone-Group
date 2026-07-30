<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreQuoteRequestRequest;
use App\Models\Division;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Notifications\QuoteRequestReceived;
use App\Notifications\QuoteRequestSubmitted;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The multi-step "Request a Quote" flow.
 *
 * The wizard is a front-end affordance only — everything arrives in a single
 * POST and is validated server-side in one pass, so a request crafted
 * outside the browser is held to exactly the same rules.
 */
class QuoteRequestController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function create(): View
    {
        seo()->set([
            'title' => 'Request a Quote — '.setting('company.name'),
            'description' => 'Tell us what you need delivered. Select the services required, describe the scope, and our team will return a written quotation.',
        ]);

        return view('site.quote.create', [
            'divisions' => Division::query()
                ->active()
                ->ordered()
                ->with(['activeServices' => fn ($q) => $q->quotable()])
                ->get(),
            'selectedService' => request()->query('service'),
            'selectedDivision' => request()->query('division'),
        ]);
    }

    public function store(StoreQuoteRequestRequest $request): RedirectResponse
    {
        $quote = DB::transaction(function () use ($request) {
            $quote = QuoteRequest::query()->create(array_merge($request->quoteAttributes(), [
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 250),
            ]));

            // Only services flagged quotable may be attached, so a tampered
            // form cannot reference an internal or retired service.
            $serviceIds = Service::query()
                ->whereIn('id', $request->input('services', []))
                ->active()
                ->quotable()
                ->pluck('id');

            $quote->services()->sync($serviceIds);

            foreach ($request->file('attachments', []) as $file) {
                $quote->files()->create([
                    'path' => $this->uploader->privateFile($file, 'quotes/'.$quote->id),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $quote;
        });

        $quote->load(['services', 'division', 'files']);

        Notification::route('mail', $quote->email)->notify(new QuoteRequestSubmitted($quote));

        Notification::route('mail', setting('system.notify_quotes_to', config('mail.from.address')))
            ->notify(new QuoteRequestReceived($quote));

        return redirect()->route('site.quote.thanks', $quote->reference);
    }

    public function thankYou(string $reference): View
    {
        // Look up by reference only to confirm it exists — no lead detail is
        // rendered on a page reachable by anyone holding the URL.
        $exists = QuoteRequest::query()->where('reference', $reference)->exists();

        abort_unless($exists, 404);

        seo()->set([
            'title' => 'Quotation Request Received — '.setting('company.name'),
            'robots' => 'noindex, nofollow',
        ]);

        return view('site.quote.thanks', ['reference' => $reference]);
    }
}
