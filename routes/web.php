<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\OtpPasswordResetController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\Portal\PortalController;
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

    /*
     * Browsing is open to everyone; submitting is not.
     *
     * Quotation requests, job applications and contact enquiries all require
     * a verified account, so every lead is traceable to a real, confirmed
     * email address and the sender can follow it in their account area.
     */
    Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
    Route::get('/careers/{career:slug}', [CareerController::class, 'show'])->name('careers.show');

    Route::get('/request-a-quote', [QuoteRequestController::class, 'create'])->name('quote.create');
    Route::get('/request-a-quote/received/{reference}', [QuoteRequestController::class, 'thankYou'])
        ->middleware('auth')
        ->name('quote.thanks');

    Route::get('/contact', [ContactController::class, 'index'])->name('contact');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/careers/{career:slug}/apply', [CareerController::class, 'apply'])
            ->middleware('throttle:job-application')
            ->name('careers.apply');

        Route::post('/request-a-quote', [QuoteRequestController::class, 'store'])
            ->middleware('throttle:quote-request')
            ->name('quote.store');

        Route::post('/contact', [ContactController::class, 'store'])
            ->middleware('throttle:contact-form')
            ->name('contact.store');
    });

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

// Public self-registration is open: RegisterController assigns the Client
// role, which carries no admin permissions.
Auth::routes(['verify' => true, 'register' => true]);

// "Continue with Google". Accounts created this way are Clients, exactly as
// through the registration form — social sign-in never grants staff access.
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

/*
|--------------------------------------------------------------------------
| One-time codes
|--------------------------------------------------------------------------
|
| Six-digit codes emailed to the user, as an alternative to the verification
| and reset links. Easier on a phone, where a link often opens in a browser
| with no session and quietly fails.
|
| Rate limited: sending is capped per address by OtpService, and these routes
| add a per-IP ceiling on top.
|
*/

Route::middleware('throttle:otp')->group(function () {
    // Verify the signed-in user's own address.
    Route::get('/verify-email/code', [OtpVerificationController::class, 'show'])->name('otp.verify');
    Route::post('/verify-email/code/send', [OtpVerificationController::class, 'send'])->name('otp.send');
    Route::post('/verify-email/code', [OtpVerificationController::class, 'verify'])->name('otp.confirm');

    // Reset a forgotten password with a code instead of a link.
    Route::get('/password/code', [OtpPasswordResetController::class, 'request'])->name('password.otp.request');
    Route::post('/password/code', [OtpPasswordResetController::class, 'send'])->name('password.otp.send');
    Route::get('/password/code/verify', [OtpPasswordResetController::class, 'verify'])->name('password.otp.verify');
    Route::post('/password/code/reset', [OtpPasswordResetController::class, 'update'])->name('password.otp.update');
});

/*
|--------------------------------------------------------------------------
| Client account portal
|--------------------------------------------------------------------------
|
| Where registered visitors track their own quotation requests, job
| applications and enquiries. Every query is scoped to the signed-in user.
|
*/

/*
|--------------------------------------------------------------------------
| Employee self-service portal
|--------------------------------------------------------------------------
|
| For staff with an employee record. Everything is scoped to the signed-in
| person; employment terms are read-only here and belong to HR.
|
*/

Route::middleware(['auth', 'verified'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [PortalController::class, 'profile'])->name('profile');
    Route::put('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [PortalController::class, 'updatePassword'])->name('profile.password');
    Route::get('/announcements', [PortalController::class, 'announcements'])->name('announcements');
    Route::get('/announcements/{announcement:slug}', [PortalController::class, 'announcement'])->name('announcements.show');
});

Route::middleware(['auth'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/requests', [AccountController::class, 'quotes'])->name('quotes');
    Route::get('/requests/{quote}', [AccountController::class, 'quote'])->name('quotes.show');
    Route::get('/applications', [AccountController::class, 'applications'])->name('applications');
    Route::get('/messages', [AccountController::class, 'messages'])->name('messages');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password');
});

// laravel/ui points a few flows at /home. Send everyone to the destination
// their role and division imply — the same rule the login controller uses.
Route::get('/home', fn () => redirect()->to(\App\Support\Navigation::homeRouteFor(auth()->user())))
    ->middleware('auth')
    ->name('home');
