<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Tag;
use App\Http\Requests\Site\StoreCommentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $posts = Blog::query()
            ->published()
            ->with(['category:id,name,slug,color', 'author:id,name'])
            ->inCategory($validated['category'] ?? null)
            ->withTag($validated['tag'] ?? null)
            ->search($validated['q'] ?? null)
            ->latest('published_at')
            ->paginate((int) setting('system.items_per_page', 9))
            ->withQueryString();

        seo()->set([
            'title' => 'News & Insights — '.setting('company.name'),
            'description' => 'Company news, project updates and commentary from across WellDone Group.',
        ]);

        return view('site.blog.index', [
            'posts' => $posts,
            'categories' => BlogCategory::query()->active()->ordered()->withCount(['blogs' => fn ($q) => $q->published()])->get(),
            'tags' => Tag::query()->has('blogs')->orderBy('name')->limit(20)->get(),
            'featured' => Blog::query()->published()->featured()->with('category:id,name,slug,color')->latest('published_at')->limit(3)->get(),
            'filters' => $validated,
        ]);
    }

    public function show(Blog $blog): View
    {
        abort_unless($blog->is_published, 404);

        $blog->load(['category', 'author', 'tags', 'seo', 'approvedComments.replies']);
        $blog->recordView();

        $related = Blog::query()
            ->published()
            ->where('blog_category_id', $blog->blog_category_id)
            ->whereKeyNot($blog->getKey())
            ->with('category:id,name,slug,color')
            ->latest('published_at')
            ->limit(3)
            ->get();

        seo()->forModel($blog)->set([
            'title' => $blog->title.' — '.setting('company.name'),
            'description' => $blog->excerpt,
            'og_image' => $blog->featured_image,
            'schema' => $this->articleSchema($blog),
        ]);

        return view('site.blog.show', compact('blog', 'related'));
    }

    public function storeComment(StoreCommentRequest $request, Blog $blog): RedirectResponse
    {
        abort_unless($blog->is_published && $blog->allow_comments, 404);
        abort_unless((bool) setting('system.enable_blog_comments', true), 404);

        $blog->comments()->create($request->commentAttributes());

        return back()
            ->withFragment('comments')
            ->with('status', 'Thank you — your comment has been submitted and will appear once reviewed.');
    }

    protected function articleSchema(Blog $blog): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $blog->title,
            'description' => $blog->excerpt,
            'image' => $blog->featured_image_url,
            'datePublished' => $blog->published_at?->toIso8601String(),
            'dateModified' => $blog->updated_at?->toIso8601String(),
            'author' => $blog->author ? ['@type' => 'Person', 'name' => $blog->author->name] : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => setting('company.name'),
            ],
        ]);
    }
}
