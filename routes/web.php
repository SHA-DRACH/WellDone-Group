<?php

use App\Http\Controllers\Site\BlogController;
use App\Http\Controllers\Site\CareerController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\DivisionController;
use App\Http\Controllers\Site\DownloadController;
use App\Http\Controllers\Site\GalleryController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProjectController;
use App\Http\Controllers\Site\QuoteRequestController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
|
| Every route here is named under the `site.` prefix so navigation helpers
| and the SEO resolver can match on route name. Admin routes live in
| routes/admin.php.
|
*/

Route::name('site.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // --------------------------------------------------- Company
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/about/leadership', [PageController::class, 'leadership'])->name('leadership');
    Route::get('/about/csr', [PageController::class, 'csr'])->name('csr');
    Route::get('/about/certifications', [PageController::class, 'certifications'])->name('certifications');
    Route::get('/faqs', [PageController::class, 'faqs'])->name('faqs');
    Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/terms-of-use', [PageController::class, 'terms'])->name('terms');

    // --------------------------------------------------- Divisions & services
    Route::get('/divisions', [DivisionController::class, 'index'])->name('divisions.index');
    Route::get('/divisions/{division:slug}', [DivisionController::class, 'show'])->name('divisions.show');
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

    // --------------------------------------------------- Portfolio
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::get('/gallery/{gallery:slug}', [GalleryController::class, 'show'])->name('gallery.show');

    // --------------------------------------------------- News & blog
    Route::get('/news', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/news/{blog:slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::post('/news/{blog:slug}/comments', [BlogController::class, 'storeComment'])
        ->middleware('throttle:comment')
        ->name('blog.comments.store');

    // --------------------------------------------------- Careers
    Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
    Route::get('/careers/{career:slug}', [CareerController::class, 'show'])->name('careers.show');
    Route::post('/careers/{career:slug}/apply', [CareerController::class, 'apply'])
        ->middleware('throttle:job-application')
        ->name('careers.apply');

    // --------------------------------------------------- Quotations
    Route::get('/request-a-quote', [QuoteRequestController::class, 'create'])->name('quote.create');
    Route::post('/request-a-quote', [QuoteRequestController::class, 'store'])
        ->middleware('throttle:quote-request')
        ->name('quote.store');
    Route::get('/request-a-quote/received/{reference}', [QuoteRequestController::class, 'thankYou'])
        ->name('quote.thanks');

    // --------------------------------------------------- Contact
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:contact-form')
        ->name('contact.store');

    // --------------------------------------------------- Downloads
    Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');
    Route::get('/downloads/{download:slug}', [DownloadController::class, 'download'])
        ->middleware('throttle:downloads')
        ->name('downloads.get');

    // --------------------------------------------------- Newsletter
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
        ->middleware('throttle:newsletter')
        ->name('newsletter.subscribe');
    Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
    Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
});

// Search engine files
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Auth::routes(['verify' => true, 'register' => false]);

// laravel/ui points a few flows at /home. Send everyone to the destination
// their role and division imply — the same rule the login controller uses.
Route::get('/home', fn () => redirect()->to(\App\Support\Navigation::homeRouteFor(auth()->user())))
    ->middleware('auth')
    ->name('home');
