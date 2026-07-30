<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogComment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Comment moderation. Nothing a reader submits reaches the public article
 * until it is approved here.
 */
class BlogCommentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('moderate', Blog::class);

        $comments = BlogComment::query()
            ->with('blog:id,title,slug')
            ->when($request->query('state') === 'approved', fn ($q) => $q->approved())
            ->when($request->query('state') !== 'approved', fn ($q) => $q->pending())
            ->when($request->query('q'), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($inner) => $inner
                    ->where('author_name', 'like', $like)
                    ->orWhere('body', 'like', $like));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.comments.index', [
            'records' => $comments,
            'search' => $request->query('q', ''),
            'state' => $request->query('state', 'pending'),
            'pendingCount' => BlogComment::query()->pending()->count(),
            'approvedCount' => BlogComment::query()->approved()->count(),
        ]);
    }

    public function approve(BlogComment $comment): RedirectResponse
    {
        $this->authorize('moderate', Blog::class);

        $comment->update(['is_approved' => true]);

        return back()->with('success', 'Comment approved and now visible on the article.');
    }

    public function destroy(BlogComment $comment): RedirectResponse
    {
        $this->authorize('moderate', Blog::class);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
