<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Employee;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Employee self-service portal.
 *
 * Everything is scoped to the signed-in person's own employee record — there
 * is no route that takes another employee's id. Staff may correct their own
 * contact details; anything with employment or pay consequence (department,
 * position, salary, status) is read-only here and belongs to HR.
 */
class PortalController extends Controller
{
    public function __construct(protected MediaUploader $uploader)
    {
        $this->middleware(['auth', 'verified']);
    }

    public function dashboard(Request $request): View
    {
        $employee = $this->employee($request);

        return view('portal.dashboard', [
            'employee' => $employee,
            'announcements' => $this->announcementsFor($employee)->limit(5)->get(),
            'colleagues' => $employee->department_id
                ? Employee::query()
                    ->where('department_id', $employee->department_id)
                    ->whereKeyNot($employee->getKey())
                    ->employed()
                    ->with('position:id,title')
                    ->ordered()
                    ->limit(8)
                    ->get()
                : collect(),
            'directReports' => $employee->directReports()->employed()->with('position:id,title')->get(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('portal.profile', [
            'employee' => $this->employee($request)->load([
                'department', 'division', 'position', 'employmentType',
                'manager', 'emergencyContacts',
            ]),
        ]);
    }

    /**
     * Staff may correct how to reach them and who to call in an emergency.
     * Employment terms are not editable here by design.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);

        $data = $request->validate([
            'personal_email' => ['nullable', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'alternative_phone' => ['nullable', 'string', 'max:40'],
            'residential_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'photo' => ['nullable', 'image', 'max:3072'],

            'emergency_name' => ['nullable', 'string', 'max:150'],
            'emergency_relationship' => ['nullable', 'string', 'max:80'],
            'emergency_phone' => ['nullable', 'string', 'max:40'],
            'emergency_alternative_phone' => ['nullable', 'string', 'max:40'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->uploader->image(
                $request->file('photo'), 'employees/photos', 800, replacing: $employee->photo
            );
        } else {
            unset($data['photo']);
        }

        $employee->update(collect($data)->except([
            'emergency_name', 'emergency_relationship',
            'emergency_phone', 'emergency_alternative_phone',
        ])->all());

        if (filled($data['emergency_name'] ?? null) && filled($data['emergency_phone'] ?? null)) {
            $employee->emergencyContacts()->updateOrCreate(
                ['is_primary' => true],
                [
                    'name' => $data['emergency_name'],
                    'relationship' => $data['emergency_relationship'] ?? null,
                    'phone' => $data['emergency_phone'],
                    'alternative_phone' => $data['emergency_alternative_phone'] ?? null,
                ]
            );
        }

        return back()->with('status', 'Your details have been updated.');
    }

    public function announcements(Request $request): View
    {
        $employee = $this->employee($request);

        return view('portal.announcements', [
            'employee' => $employee,
            'announcements' => $this->announcementsFor($employee)->paginate(10),
        ]);
    }

    public function announcement(Request $request, Announcement $announcement): View
    {
        $employee = $this->employee($request);

        // Confirm this announcement is actually addressed to them.
        $visible = $this->announcementsFor($employee)
            ->whereKey($announcement->getKey())
            ->exists();

        abort_unless($visible, 404);

        // Record that it was read, so HR can see a policy update landed.
        $announcement->readers()->syncWithoutDetaching([
            $request->user()->id => ['read_at' => now()],
        ]);

        return view('portal.announcement', [
            'employee' => $employee,
            'announcement' => $announcement->load('author:id,name', 'department:id,name'),
        ]);
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

    // ------------------------------------------------------------------

    /**
     * The signed-in user's employee record.
     *
     * A staff account with no employee record has nothing to show here, so
     * this is a 403 rather than an empty page — it means the account was
     * never linked, which HR needs to fix.
     */
    protected function employee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        abort_unless(
            $employee !== null,
            403,
            'Your account is not linked to an employee record. Please contact Human Resources.'
        );

        return $employee;
    }

    protected function announcementsFor(Employee $employee)
    {
        return Announcement::query()
            ->live()
            ->visibleTo($employee)
            ->with('author:id,name')
            ->ordered();
    }
}
