<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Notifications\ContactMessageReceived;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    public function index(): View
    {
        seo()->set([
            'title' => 'Contact Us — '.setting('company.name'),
            'description' => 'Office address, phone numbers, email and enquiry form for WellDone Group Inc. in Monrovia, Liberia.',
        ]);

        return view('site.contact', [
            'faqs' => Faq::query()->active()->where('category', 'general')->ordered()->limit(5)->get(),
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $message = ContactMessage::query()->create(array_merge($request->messageAttributes(), [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 250),
        ]));

        Notification::route('mail', setting('system.notify_contact_to', config('mail.from.address')))
            ->notify(new ContactMessageReceived($message));

        return redirect()
            ->route('site.contact')
            ->withFragment('contact-form')
            ->with('status', 'Thank you for getting in touch. A member of our team will respond shortly.');
    }
}
