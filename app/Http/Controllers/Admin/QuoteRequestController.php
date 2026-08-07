<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestFile;
use App\Models\User;
use App\Support\DivisionScope;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The quotation-request queue — the lead pipeline.
 *
 * Division-scoped staff only ever see their own division's requests; that is
 * applied to the query itself rather than to the view, so it cannot be
 * bypassed with a crafted filter.
 */
class QuoteRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', QuoteRequest::class);

        $quotes = $this->scopedQuery()
            ->with(['division:id,name', 'assignee:id,name'])
            ->withCount('files')
            ->status($request->query('status'))
            ->search($request->query('q'))
            ->when($request->query('division'), fn (Builder $q, $id) => $q->where('division_id', $id))
            ->when($request->query('assigned') === 'me', fn (Builder $q) => $q->where('assigned_to', auth()->id()))
            ->when($request->query('assigned') === 'none', fn (Builder $q) => $q->whereNull('assigned_to'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.quotes.index', [
            'records' => $quotes,
            'search' => $request->query('q', ''),
            'divisions' => Division::query()->ordered()->pluck('name', 'id'),
            'counts' => $this->scopedQuery()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function show(QuoteRequest $quote): View
    {
        $this->authorize('view', $quote);
        $this->assertWithinScope($quote);

        $quote->load(['division', 'services.division:id,name', 'files', 'assignee']);

        return view('admin.quotes.show', [
            'quote' => $quote,
            'assignees' => User::query()
                ->active()
                ->whereHas('roles')
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, QuoteRequest $quote): RedirectResponse
    {
        $this->authorize('update', $quote);
        $this->assertWithinScope($quote);

        $data = $request->validate([
            'status' => ['required', Rule::in(QuoteRequest::STATUSES)],
            'priority' => ['required', Rule::in(QuoteRequest::PRIORITIES)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'quoted_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // Reassignment is a narrower right than working a lead.
        if (! $request->user()->can('assign', $quote)) {
            unset($data['assigned_to']);
        }

        // Stamp the milestones the first time each is reached.
        if ($data['status'] !== 'new' && $quote->responded_at === null) {
            $data['responded_at'] = now();
        }

        if (filled($data['quoted_amount'] ?? null) && $quote->quoted_at === null) {
            $data['quoted_at'] = now();
        }

        $quote->update($data);

        return back()->with('success', 'Request updated.');
    }

    public function destroy(QuoteRequest $quote): RedirectResponse
    {
        $this->authorize('delete', $quote);
        $this->assertWithinScope($quote);

        // Attachments are removed by the QuoteRequestFile model's delete hook.
        $quote->files->each->delete();
        $quote->delete();

        return redirect()
            ->route('admin.quotes.index')
            ->with('success', 'Request deleted.');
    }

    /**
     * Attachments live on the private disk and are streamed here so that
     * authorisation is checked on every download.
     */
    public function downloadFile(QuoteRequestFile $file): StreamedResponse
    {
        $quote = $file->quoteRequest;

        $this->authorize('downloadFiles', $quote);
        $this->assertWithinScope($quote);

        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    // ------------------------------------------------------------------

    /** Base query, already narrowed to what this user is allowed to see. */
    protected function scopedQuery(): Builder
    {
        return QuoteRequest::query()->withinDivisionOf(auth()->user());
    }

    protected function assertWithinScope(QuoteRequest $quote): void
    {
        DivisionScope::assertVisible(auth()->user(), $quote->division_id, 'request');
    }
}
