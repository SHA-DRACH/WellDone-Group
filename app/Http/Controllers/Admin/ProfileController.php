<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * A signed-in user editing their own account. No permission is required —
 * the policy's updateProfile ability is scoped to self.
 */
class ProfileController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function edit(): View
    {
        return view('admin.profile', ['record' => auth()->user()->load('division')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('updateProfile', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $emailChanged = $data['email'] !== $user->email;

        $user->fill(collect($data)->except('avatar')->all());

        if ($request->boolean('remove_avatar')) {
            $this->uploader->deleteFrom('public', $user->avatar);
            $user->avatar = null;
        }

        if ($request->hasFile('avatar')) {
            $user->avatar = $this->uploader->image(
                $request->file('avatar'), 'users/avatars', 400, replacing: $user->avatar
            );
        }

        // Changing the address invalidates verification — the new one has to
        // be confirmed before the account is trusted again.
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return back()->with('success', 'Profile updated. Please check your new email address for a verification link.');
        }

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.current_password' => 'That is not your current password.',
        ]);

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        return back()->with('success', 'Password changed.');
    }
}
