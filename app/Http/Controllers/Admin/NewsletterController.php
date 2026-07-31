<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Newsletter::class);

        $subscribers = Newsletter::query()
            ->when($request->query('state') === 'confirmed', fn ($q) => $q->subscribed())
            ->when($request->query('state') === 'pending', fn ($q) => $q->pending())
            ->when($request->query('state') === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->when($request->query('q'), fn ($q, $term) => $q->where('email', 'like', '%'.$term.'%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.newsletters.index', [
            'records' => $subscribers,
            'search' => $request->query('q', ''),
            'confirmed' => Newsletter::query()->subscribed()->count(),
            'pending' => Newsletter::query()->pending()->count(),
            'unsubscribed' => Newsletter::query()->whereNotNull('unsubscribed_at')->count(),
        ]);
    }

    /**
     * CSV export of confirmed subscribers only — an address that never
     * completed double opt-in must not end up on a mailing list.
     */
    public function export(): StreamedResponse
    {
        $this->authorize('export', Newsletter::class);

        $filename = 'welldone-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Email', 'Name', 'Confirmed at', 'Source']);

            // Chunked so a large list never loads entirely into memory.
            Newsletter::query()->subscribed()->orderBy('email')->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $subscriber) {
                    fputcsv($handle, [
                        $subscriber->email,
                        $subscriber->name,
                        $subscriber->confirmed_at?->toDateTimeString(),
                        $subscriber->source,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(Newsletter $newsletter): RedirectResponse
    {
        $this->authorize('delete', $newsletter);

        $newsletter->delete();

        return back()->with('success', 'Subscriber removed.');
    }
}
