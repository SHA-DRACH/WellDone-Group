<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreJobApplicationRequest;
use App\Models\Career;
use App\Models\Division;
use App\Models\JobApplication;
use App\Notifications\JobApplicationReceived;
use App\Notifications\JobApplicationSubmitted;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CareerController extends Controller
{
    public function __construct(protected MediaUploader $uploader)
    {
        $this->middleware(function ($request, $next) {
            abort_unless((bool) setting('system.enable_careers', true), 404);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'division' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'in:'.implode(',', Career::EMPLOYMENT_TYPES)],
        ]);

        $vacancies = Career::query()
            ->open()
            ->with('division:id,name,slug')
            ->when($validated['division'] ?? null, fn ($q, $slug) => $q->whereHas('division', fn ($d) => $d->where('slug', $slug)))
            ->when($validated['type'] ?? null, fn ($q, $type) => $q->where('employment_type', $type))
            ->orderByDesc('is_featured')
            ->orderBy('closes_at')
            ->paginate(10)
            ->withQueryString();

        seo()->set([
            'title' => 'Careers — '.setting('company.name'),
            'description' => 'Current vacancies and internship opportunities across WellDone Group Inc.',
        ]);

        return view('site.careers.index', [
            'vacancies' => $vacancies,
            'divisions' => Division::query()->active()->ordered()->get(['id', 'name', 'slug']),
            'filters' => $validated,
        ]);
    }

    public function show(Career $career): View
    {
        abort_unless($career->is_active, 404);

        $career->load(['division', 'seo']);

        seo()->forModel($career)->set([
            'title' => $career->title.' — Careers at '.setting('company.name'),
            'description' => \App\Support\Format::excerpt($career->description),
        ]);

        return view('site.careers.show', [
            'career' => $career,
            'others' => Career::query()->open()->whereKeyNot($career->getKey())->limit(4)->get(),
        ]);
    }

    public function apply(StoreJobApplicationRequest $request, Career $career): RedirectResponse
    {
        abort_unless($career->is_open, 404, 'This vacancy is no longer accepting applications.');

        $application = DB::transaction(function () use ($request, $career) {
            // The résumé goes to the private disk — it is personal data and
            // must never be reachable under /storage.
            $path = $this->uploader->privateFile($request->file('resume'), 'applications/'.$career->id);

            return JobApplication::query()->create(array_merge($request->applicationAttributes(), [
                'career_id' => $career->id,
                'resume_path' => $path,
                'resume_original_name' => $request->file('resume')->getClientOriginalName(),
                'ip_address' => $request->ip(),
            ]));
        });

        $application->setRelation('career', $career);

        // Confirmation to the candidate, alert to the HR inbox.
        Notification::route('mail', $application->email)
            ->notify(new JobApplicationSubmitted($application));

        Notification::route('mail', setting('system.notify_applications_to', config('mail.from.address')))
            ->notify(new JobApplicationReceived($application));

        return redirect()
            ->route('site.careers.show', $career->slug)
            ->with('application_reference', $application->reference)
            ->with('status', 'Your application has been submitted. Reference: '.$application->reference);
    }
}
