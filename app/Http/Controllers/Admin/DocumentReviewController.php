<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DocumentReview;
use App\Models\User;
use App\Notifications\DocumentReviewed;
use App\Notifications\DocumentSubmittedForReview;
use App\Services\MediaUploader;
use App\Support\DivisionScope;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documents a division sends up for the CEO or Managing Director to decide on.
 *
 * Submitted from the division dashboard; reviewed from the Document Reviews
 * page, where the reviewer opens the file and approves it, rejects it or asks
 * for changes. Both sides are told by email.
 */
class DocumentReviewController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DocumentReview::class);

        $user = $request->user();
        $isReviewer = $user->can(Permissions::DOCUMENT_REVIEWS_REVIEW);
        $status = in_array($request->query('status'), DocumentReview::STATUSES, true) ? $request->query('status') : null;

        $reviews = DocumentReview::query()
            ->with(['division:id,name,slug', 'submitter:id,name', 'reviewer:id,name'])
            // Reviewers see everything; a division sees its own submissions.
            ->unless($isReviewer, fn ($q) => $q->where(fn ($inner) => $inner
                ->withinDivisionOf($user)
                ->orWhere('submitted_by', $user->id)))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->integer('division'), fn ($q, $id) => $q->where('division_id', $id))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($inner) => $inner
                ->where('title', 'like', '%'.$term.'%')
                ->orWhere('reference', 'like', '%'.$term.'%')))
            // Waiting items first, then newest.
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'isReviewer' => $isReviewer,
            'status' => $status,
            'divisions' => Division::query()->visibleTo($user)->ordered()->pluck('name', 'id'),
            'counts' => DocumentReview::query()
                ->unless($isReviewer, fn ($q) => $q->withinDivisionOf($user))
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    /** Submitted from the division dashboard. */
    public function store(Request $request, Division $division): RedirectResponse
    {
        $this->authorize('create', DocumentReview::class);

        $user = $request->user();

        abort_unless(DivisionScope::allows($user, $division->id), 403, 'You can only submit documents for your own division.');

        // Field names are prefixed (doc_*) and errors go to their own bag: the
        // dashboard's operations-log form sits on the same page and also has
        // a "notes" field, which would otherwise be refilled from this one.
        $data = $request->validateWithBag('review', [
            'doc_title' => ['required', 'string', 'max:200'],
            'doc_category' => ['required', Rule::in(array_keys(DocumentReview::CATEGORIES))],
            'doc_notes' => ['nullable', 'string', 'max:3000'],
            'doc_file' => ['required', 'file', 'mimes:'.DocumentReview::FILE_TYPES, 'max:'.DocumentReview::MAX_KB],
        ], [
            'doc_title.required' => 'Give the document a title.',
            'doc_file.required' => 'Attach the document to be reviewed.',
            'doc_file.mimes' => 'Upload a PDF, Word, Excel or image file.',
            'doc_file.max' => 'The file must be 20 MB or smaller.',
        ]);

        $file = $request->file('doc_file');

        $review = DocumentReview::query()->create([
            'division_id' => $division->id,
            'submitted_by' => $user->id,
            'title' => $data['doc_title'],
            'category' => $data['doc_category'],
            'notes' => $data['doc_notes'] ?? null,
            'file_path' => $this->uploader->privateFile($file, 'reviews/'.$division->slug),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'file_size' => $file->getSize(),
        ]);

        activity('document-review')->performedOn($review)->causedBy($user)->event('submitted')
            ->log('Submitted "'.$review->title.'" for review');

        Notification::send($this->reviewers()->except($user->id), new DocumentSubmittedForReview($review));

        return redirect()->to(route('admin.division.dashboard', $division->slug).'#document-review')
            ->with('success', 'Sent for review ('.$review->reference.'). You will be emailed the decision.');
    }

    public function show(DocumentReview $review): View
    {
        $this->authorize('view', $review);

        $review->load(['division:id,name,slug', 'submitter:id,name,email,position', 'reviewer:id,name,position']);

        return view('admin.reviews.show', ['review' => $review]);
    }

    /** Opens the file in the browser (PDF / image), or downloads it with ?download=1. */
    public function file(Request $request, DocumentReview $review): Response
    {
        $this->authorize('view', $review);

        abort_unless(Storage::disk('local')->exists($review->file_path), 404);

        activity('document-review')->performedOn($review)->causedBy($request->user())->event('opened')
            ->log('Opened the file of "'.$review->title.'"');

        if ($request->boolean('download') || ! $review->previewable) {
            return Storage::disk('local')->download($review->file_path, $review->original_name);
        }

        return Storage::disk('local')->response($review->file_path, $review->original_name, [
            'Content-Disposition' => 'inline; filename="'.addslashes($review->original_name).'"',
        ]);
    }

    public function decide(Request $request, DocumentReview $review): RedirectResponse
    {
        $this->authorize('review', $review);

        $data = $request->validate([
            'decision' => ['required', Rule::in(DocumentReview::DECISIONS)],
            'review_comment' => [
                Rule::requiredIf(fn () => $request->input('decision') !== 'approved'),
                'nullable', 'string', 'max:3000',
            ],
        ], [
            'review_comment.required' => 'Add a comment so the division knows what to change or why it was rejected.',
        ]);

        $review->update([
            'status' => $data['decision'],
            'review_comment' => $data['review_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        activity('document-review')->performedOn($review)->causedBy($request->user())->event($data['decision'])
            ->log($review->status_label.': "'.$review->title.'"');

        $review->submitter?->notify(new DocumentReviewed($review));

        return redirect()->route('admin.reviews.show', $review)
            ->with('success', 'Decision recorded: '.strtolower($review->status_label).'. '.($review->submitter ? $review->submitter->name.' has been emailed.' : ''));
    }

    /** Everyone who reviews submissions: CEO and Managing Director accounts. */
    protected function reviewers()
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [Roles::CEO, Roles::MANAGING_DIRECTOR]))
            ->get();
    }
}
