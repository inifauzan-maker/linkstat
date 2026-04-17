<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AnalyticsComparisonController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardAnalyticsExportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageLinkController;
use App\Http\Controllers\PublicLandingPageController;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicLandingPageController::class, 'root'])->name('home');
Route::get('/cta', [PublicLandingPageController::class, 'domainCta'])->name('public.domains.cta');
Route::get('/links/{link}', [PublicLandingPageController::class, 'domainLink'])->name('public.domains.links');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/dashboard/page', [DashboardController::class, 'update'])->name('dashboard.page.update');
    Route::get('/dashboard/analytics/export', DashboardAnalyticsExportController::class)->name('dashboard.analytics.export');
    Route::post('/dashboard/domain/verify', [DashboardController::class, 'verifyDomain'])->name('dashboard.domain.verify');
    Route::post('/dashboard/links', [LandingPageLinkController::class, 'store'])->name('dashboard.links.store');
    Route::put('/dashboard/links/{link}', [LandingPageLinkController::class, 'update'])->name('dashboard.links.update');
    Route::delete('/dashboard/links/{link}', [LandingPageLinkController::class, 'destroy'])->name('dashboard.links.destroy');
});

Route::middleware(['auth', EnsureUserIsActive::class, EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/analytics', [AnalyticsComparisonController::class, 'index'])->name('analytics.index');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });

Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/u/{landingPage:slug}', [PublicLandingPageController::class, 'show'])->name('public.pages.show');
Route::get('/u/{landingPage:slug}/cta', [PublicLandingPageController::class, 'cta'])->name('public.pages.cta');
Route::get('/u/{landingPage:slug}/links/{link}', [PublicLandingPageController::class, 'link'])->name('public.pages.links');
