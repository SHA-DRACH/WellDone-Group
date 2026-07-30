<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\DivisionDashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
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
| Modules still to be wired up are listed at the bottom of this file. Their
| routes are deliberately absent rather than pointing at classes that do not
| exist yet, so the router never resolves a missing controller.
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

    // ------------------------------------------------ Corporate structure
    Route::resource('divisions', DivisionController::class);
    Route::resource('services', ServiceController::class);

    // ------------------------------------------------ Leads
    Route::get('quotes', [QuoteRequestController::class, 'index'])->name('quotes.index');
    Route::get('quotes/{quote}', [QuoteRequestController::class, 'show'])->name('quotes.show');
    Route::patch('quotes/{quote}', [QuoteRequestController::class, 'update'])->name('quotes.update');
    Route::delete('quotes/{quote}', [QuoteRequestController::class, 'destroy'])->name('quotes.destroy');
    Route::get('quotes/files/{file}', [QuoteRequestController::class, 'downloadFile'])->name('quotes.files.download');

    // ------------------------------------------------ Platform
    Route::get('settings/{group?}', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings/{group}', [SettingController::class, 'update'])->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| Still to build
|--------------------------------------------------------------------------
|
| These modules have their database tables, models, policies, permissions and
| sidebar entries in place; only the controller and views remain. Each one is
| a thin subclass of Admin\BaseCrudController plus (usually) the generic list
| and form views — see Admin\DivisionController for the pattern.
|
|   projects (+ project images)   blog-categories       galleries (+ images)
|   blogs (+ comments)            testimonials          partners
|   sliders                       downloads             clients
|   careers                       applications          messages
|   newsletters                   team-members          certificates
|   milestones                    csr-initiatives       faqs
|   roles                         media                 seo
|   activity
|
*/
