<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\QuoteRequest;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The account area for registered visitors.
 *
 * Everything here is scoped to the signed-in user's own records by the query
 * itself — there is no route that takes another user's id.
 */
class AccountController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'quotes' => $user->quoteRequests()
                ->with('division:id,name')
                ->latest()
                ->limit(5)
                ->get(),
            'applications' => $user->jobApplications()
                ->with('career:id,title,slug')
                ->latest()
                ->limit(5)
                ->get(),
            'messages' => $user->contactMessages()->latest()->limit(5)->get(),
            'counts' => [
                'quotes' => $user->quoteRequests()->count(),
                'quotes_open' => $user->quoteRequests()->open()->count(),
                'applications' => $user->jobApplications()->count(),
                'messages' => $user->contactMessages()->count(),
            ],
            'openVacancies' => Career::query()->open()->latest()->limit(4)->get(),
        ]);
    }

    public function quotes(Request $request): View
    {
        return view('account.quotes', [
            'quotes' => $request->user()->quoteRequests()
                ->with(['division:id,name', 'services:id,name'])
                ->withCount('files')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function quote(Request $request, QuoteRequest $quote): View
    {
        // Scoped explicitly: a reference in the URL must belong to this user.
        abort_unless($quote->user_id === $request->user()->id, 403);

        $quote->load(['division', 'services', 'files']);

        return view('account.quote', ['quote' => $quote]);
    }

    public function applications(Request $request): View
    {
        return view('account.applications', [
            'applications' => $request->user()->jobApplications()
                ->with('career:id,title,slug,division_id')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function messages(Request $request): View
    {
        return view('account.messages', [
            'messages' => $request->user()->contactMessages()->latest()->paginate(10),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:150'],
        ], [], ['position' => 'organisation']);

        $emailChanged = $data['email'] !== $user->email;

        $user->fill($data);

        if ($emailChanged) {
            // A new address has to be confirmed before it is trusted again.
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return back()->with('status', 'Details saved. Please check your new email address for a verification link.');
        }

        return back()->with('status', 'Your details have been saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'That is not your current password.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('status', 'Your password has been changed.');
    }
}
