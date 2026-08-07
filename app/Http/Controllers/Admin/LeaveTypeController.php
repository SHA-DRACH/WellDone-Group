<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Leave entitlement rules.
 *
 * A type is never hard-deleted while requests reference it — the history of
 * what somebody took under it has to remain readable, so it is archived.
 */
class LeaveTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LeaveType::class);

        return view('admin.leave-types.index', [
            'records' => LeaveType::query()
                ->withCount(['requests' => fn ($q) => $q->approved()->forYear()])
                ->ordered()
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', LeaveType::class);

        return view('admin.leave-types.create', ['record' => new LeaveType]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveType::class);

        LeaveType::query()->create($this->validated($request, null));

        return redirect()->route('admin.leave-types.index')->with('success', 'Leave type added.');
    }

    public function edit(LeaveType $leaveType): View
    {
        $this->authorize('update', $leaveType);

        return view('admin.leave-types.edit', ['record' => $leaveType]);
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('update', $leaveType);

        $leaveType->update($this->validated($request, $leaveType));

        return redirect()->route('admin.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('delete', $leaveType);

        if ($leaveType->requests()->exists()) {
            $leaveType->update(['is_active' => false]);

            return back()->with('success',
                'Leave already taken under this type, so it has been deactivated rather than deleted. '
                .'It no longer appears on the application form.');
        }

        $leaveType->delete();

        return back()->with('success', 'Leave type deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?LeaveType $type): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash',
                Rule::unique('leave_types', 'code')->ignore($type?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'days_per_year' => ['required', 'numeric', 'min:0', 'max:365'],
            'is_paid' => ['boolean'],
            'requires_document' => ['boolean'],
            'document_required_after_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_carry_forward_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'applies_to' => ['required', Rule::in(['all', 'male', 'female'])],
            'minimum_service_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'colour' => ['required', 'string', 'max:20'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);
    }
}
