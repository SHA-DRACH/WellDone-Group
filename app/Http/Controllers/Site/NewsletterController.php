<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Notifications\NewsletterConfirmation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * Double opt-in newsletter signup.
 *
 * A signup never confirms itself — a confirmation link is emailed, and only
 * following it makes the address mailable. This also means the endpoint
 * cannot be used to add someone else's address to the list.
 */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        abort_unless((bool) setting('system.enable_newsletter', true), 404);

        $validated = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'website_url' => ['prohibited'],
        ], [
            'website_url.prohibited' => 'Your submission could not be processed.',
        ]);

        $subscriber = Newsletter::query()->firstOrNew(['email' => $validated['email']]);

        // Already confirmed and still subscribed — say so without re-sending.
        if ($subscriber->exists && $subscriber->is_subscribed) {
            return back()->with('newsletter_status', 'You are already subscribed — thank you.');
        }

        $subscriber->fill([
            'name' => $validated['name'] ?? $subscriber->name,
            'source' => 'website',
            'ip_address' => $request->ip(),
            'unsubscribed_at' => null,
        ])->save();

        Notification::route('mail', $subscriber->email)->notify(new NewsletterConfirmation($subscriber));

        return back()->with(
            'newsletter_status',
            'Almost there — please check your inbox and confirm your subscription.'
        );
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = Newsletter::query()->where('token', $token)->first();

        if (! $subscriber) {
            return redirect()->route('site.home')
                ->with('status', 'That confirmation link is no longer valid.');
        }

        $subscriber->confirm();

        return redirect()->route('site.home')
            ->with('status', 'Your subscription is confirmed. Thank you for signing up.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = Newsletter::query()->where('token', $token)->first();

        $subscriber?->unsubscribe();

        return redirect()->route('site.home')
            ->with('status', 'You have been unsubscribed. Sorry to see you go.');
    }
}
