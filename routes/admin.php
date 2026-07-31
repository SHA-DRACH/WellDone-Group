<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CsrInitiativeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\DivisionDashboardController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\JobApplicationController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MilestoneController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administration panel
|--------------------------------------------------------------------------
|
| Mounted at /admin with the `admin.` name prefix by bootstrap/app.php.
| Access to the area is gated by the `admin` middleware; individual actions
| are authorised by their policies inside each controller.
|
| Ordering note: fixed segments are declared before resource routes that
| would otherwise swallow them (e.g. seo/create before seo/{seo}).
|
*/

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Per-division dashboard. One sign-in form leads here for division staff;
    // group-level staff can open any division from the switcher.
    Route::get('division/{division:slug}', [DivisionDashboardController::class, 'index'])
        ->name('division.dashboard');

    // ------------------------------------------------ Own profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ------------------------------------------------ Access control
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
    Route::resource('roles', RoleController::class)->except(['show']);

    // ------------------------------------------------ Corporate structure
    Route::resource('divisions', DivisionController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('clients', ClientController::class);

    // ------------------------------------------------ Portfolio
    Route::post('projects/{project}/images', [ProjectController::class, 'storeImages'])->name('projects.images.store');
    Route::delete('projects/images/{image}', [ProjectController::class, 'destroyImage'])->name('projects.images.destroy');
    Route::resource('projects', ProjectController::class);

    Route::post('galleries/{gallery}/images', [GalleryController::class, 'storeImages'])->name('galleries.images.store');
    Route::delete('galleries/images/{image}', [GalleryController::class, 'destroyImage'])->name('galleries.images.destroy');
    Route::resource('galleries', GalleryController::class);

    // ------------------------------------------------ Editorial
    Route::resource('blog-categories', BlogCategoryController::class)->except(['show']);
    Route::resource('blogs', BlogController::class);

    Route::get('comments', [BlogCommentController::class, 'index'])->name('comments.index');
    Route::patch('comments/{comment}/approve', [BlogCommentController::class, 'approve'])->name('comments.approve');
    Route::delete('comments/{comment}', [BlogCommentController::class, 'destroy'])->name('comments.destroy');

    Route::resource('testimonials', TestimonialController::class)->except(['show']);
    Route::resource('partners', PartnerController::class)->except(['show']);
    Route::resource('sliders', SliderController::class)->except(['show']);
    Route::resource('downloads', DownloadController::class)->except(['show']);

    // ------------------------------------------------ Company profile
    Route::resource('team-members', TeamMemberController::class)->except(['show']);
    Route::resource('certificates', CertificateController::class)->except(['show']);
    Route::resource('milestones', MilestoneController::class)->except(['show']);
    Route::resource('csr-initiatives', CsrInitiativeController::class)->except(['show']);
    Route::resource('faqs', FaqController::class)->except(['show']);

    // ------------------------------------------------ Recruitment
    Route::resource('careers', CareerController::class);

    Route::get('applications', [JobApplicationController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}', [JobApplicationController::class, 'show'])->name('applications.show');
    Route::patch('applications/{application}', [JobApplicationController::class, 'update'])->name('applications.update');
    Route::delete('applications/{application}', [JobApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::get('applications/{application}/resume', [JobApplicationController::class, 'resume'])->name('applications.resume');

    // ------------------------------------------------ Leads
    Route::get('quotes', [QuoteRequestController::class, 'index'])->name('quotes.index');
    Route::get('quotes/files/{file}', [QuoteRequestController::class, 'downloadFile'])->name('quotes.files.download');
    Route::get('quotes/{quote}', [QuoteRequestController::class, 'show'])->name('quotes.show');
    Route::patch('quotes/{quote}', [QuoteRequestController::class, 'update'])->name('quotes.update');
    Route::delete('quotes/{quote}', [QuoteRequestController::class, 'destroy'])->name('quotes.destroy');

    Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
    Route::patch('messages/{message}', [ContactMessageController::class, 'update'])->name('messages.update');
    Route::post('messages/{message}/reply', [ContactMessageController::class, 'reply'])->name('messages.reply');
    Route::delete('messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('newsletters/export', [NewsletterController::class, 'export'])->name('newsletters.export');
    Route::get('newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
    Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');

    // ------------------------------------------------ Platform
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('media', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('seo', [SeoController::class, 'index'])->name('seo.index');
    Route::get('seo/create', [SeoController::class, 'create'])->name('seo.create');
    Route::post('seo', [SeoController::class, 'store'])->name('seo.store');
    Route::get('seo/{seo}/edit', [SeoController::class, 'edit'])->name('seo.edit');
    Route::put('seo/{seo}', [SeoController::class, 'update'])->name('seo.update');
    Route::delete('seo/{seo}', [SeoController::class, 'destroy'])->name('seo.destroy');

    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::get('settings/{group?}', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');
});
