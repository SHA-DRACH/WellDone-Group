<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\JobApplication;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Candidate applications. Read-and-progress only — applications arrive from
 * the public careers portal and are never created here.
 */
class JobApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', JobApplication::class);

        $applications = $this->scopedQuery()
            ->with('career:id,title,division_id')
            ->status($request->query('status'))
            ->search($request->query('q'))
            ->when($request->query('career'), fn ($q, $id) => $q->where('career_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.applications.index', [
            'records' => $applications,
            'search' => $request->query('q', ''),
            'vacancies' => Career::query()
                ->withTrashed()
                ->when(
                    Navigation::isDivisionScoped($request->user()),
                    fn ($q) => $q->where('division_id', $request->user()->division_id)
                )
                ->orderBy('title')
                ->pluck('title', 'id'),
            'counts' => $this->scopedQuery()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function show(JobApplication $application): View
    {
        $this->authorize('view', $application);
        $this->assertWithinScope($application);

        $application->load(['career.division', 'reviewer:id,name']);

        return view('admin.applications.show', ['application' => $application]);
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        $this->authorize('update', $application);
        $this->assertWithinScope($application);

        $data = $request->validate([
            'status' => ['required', Rule::in(JobApplication::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // Record who moved it out of "new" and when, the first time it happens.
        if ($data['status'] !== 'new' && $application->reviewed_at === null) {
            $data['reviewed_by'] = $request->user()->id;
            $data['reviewed_at'] = now();
        }

        $application->update($data);

        return back()->with('success', 'Application updated.');
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        $this->authorize('delete', $application);
        $this->assertWithinScope($application);

        // The model's deleted hook removes the résumé from the private disk.
        $application->delete();

        return redirect()
            ->route('admin.applications.index')
            ->with('success', 'Application and its résumé deleted.');
    }

    /**
     * Résumés are personal data on the private disk, streamed only to users
     * holding the separate download permission.
     */
    public function resume(JobApplication $application): StreamedResponse
    {
        $this->authorize('downloadResume', $application);
        $this->assertWithinScope($application);

        abort_unless(Storage::disk('local')->exists($application->resume_path), 404,
            'The résumé file is no longer on disk.');

        return Storage::disk('local')->download(
            $application->resume_path,
            $application->resume_original_name ?: basename($application->resume_path)
        );
    }

    // ------------------------------------------------------------------

    /**
     * Applications inherit their division from the vacancy applied for, so a
     * division head sees only candidates for their own roles.
     */
    protected function scopedQuery(): Builder
    {
        return JobApplication::query()->withinDivisionOf(auth()->user());
    }

    protected function assertWithinScope(JobApplication $application): void
    {
        $application->loadMissing('career:id,division_id');

        abort_unless(
            $application->isVisibleTo(auth()->user()),
            403,
            'This application is for a vacancy in another division.'
        );
    }
}
