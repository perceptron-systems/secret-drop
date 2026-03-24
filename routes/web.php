<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LocalizedPagesController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\SecretsController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SuperAdminController;
use App\Support\LocaleConfig;
use Illuminate\Support\Facades\Route;

// Root: redirect to /{detected_locale}
Route::get('/', [RedirectController::class, 'root']);

// Legacy URL redirects (301)
Route::get('/how-it-works', [RedirectController::class, 'howItWorks']);
Route::get('/use-cases', [RedirectController::class, 'useCases']);
Route::get('/legal', [RedirectController::class, 'legal']);

// Non-localized admin/superadmin redirects
Route::get('/admin', [RedirectController::class, 'admin']);
Route::get('/superadmin', [RedirectController::class, 'superadmin']);

// SEO
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemap.xsl', [SeoController::class, 'sitemapStylesheet']);
Route::get('/.well-known/security.txt', [SeoController::class, 'securityTxt']);

// Localized pages (public + admin + superadmin)
Route::prefix('{locale}')
    ->where(['locale' => LocaleConfig::localePattern()])
    ->group(function () {
        Route::get('/', [SecretsController::class, 'create'])->name('home');

        // Admin (user secrets management)
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
        Route::post('/admin/request-access', [AdminController::class, 'requestAccess'])
            ->middleware('throttle.captcha:3,10')
            ->name('admin.requestAccess');
        Route::get('/admin/verify/{token}', [AdminController::class, 'verify'])
            ->middleware('throttle:5,1')
            ->name('admin.verify');
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
        Route::post('/admin/secrets/{id}/revoke', [AdminController::class, 'revoke'])->name('admin.revoke');
        Route::post('/admin/secrets/{id}/extend', [AdminController::class, 'extend'])->name('admin.extend');

        // Super Admin (global statistics)
        Route::get('/superadmin', [SuperAdminController::class, 'index'])->name('superadmin.index');
        Route::post('/superadmin/request-access', [SuperAdminController::class, 'requestAccess'])
            ->middleware('throttle.captcha:3,10')
            ->name('superadmin.requestAccess');
        Route::get('/superadmin/verify/{token}', [SuperAdminController::class, 'verify'])
            ->middleware('throttle:5,1')
            ->name('superadmin.verify');
        Route::get('/superadmin/dashboard', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
        Route::get('/superadmin/dashboard/poll', [SuperAdminController::class, 'poll'])->name('superadmin.poll');
        Route::post('/superadmin/logout', [SuperAdminController::class, 'logout'])->name('superadmin.logout');

        // Catch-all for localized pages (must be last)
        Route::get('{pageSlug}', [LocalizedPagesController::class, 'show'])
            ->name('page.show');
    });

// Secrets
Route::get('/s/{token}', [SecretsController::class, 'show'])
    ->middleware(['throttle:30,1', 'no.cache'])
    ->name('secrets.show');
Route::get('/s/{token}/download', [SecretsController::class, 'download'])
    ->middleware(['throttle:30,1', 'no.cache'])
    ->name('secrets.download');

// Contact
Route::get('/contact', [ContactController::class, 'email'])->name('contact.email');
