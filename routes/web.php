<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\OrganizationImportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LoginLinkController;
use App\Http\Controllers\BottinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberSearchController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSearchController;
use App\Http\Controllers\PropertiesController;
use Illuminate\Support\Facades\Route;

Route::get('/', BottinController::class)->name('bottin');

Route::middleware('guest:web,member')->group(function () {
    Route::get('/connexion', [LoginLinkController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginLinkController::class, 'store'])->name('login.store');
    Route::view('/connexion/envoye', 'auth.link-sent')->name('login.sent');
});

Route::get('/connexion/{organization}/verifier', [AuthenticatedSessionController::class, 'store'])
    ->middleware('signed')
    ->name('login.consume');

Route::get('/membre/connexion/{member}/verifier', [AuthenticatedSessionController::class, 'storeMember'])
    ->middleware('signed')
    ->name('member-login.consume');

// Responsable (organization) space.
Route::middleware('auth:web')->group(function () {
    Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');
    Route::get('/tableau-de-bord/proprietes', PropertiesController::class)->name('dashboard.properties');
    Route::get('/tableau-de-bord/organisations', OrganizationSearchController::class)->name('dashboard.organizations');
    Route::get('/tableau-de-bord/membres', MemberSearchController::class)->name('dashboard.members');

    Route::post('/organisations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('/organisations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organisations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

    Route::post('/membres', [MemberController::class, 'store'])->name('members.store');
    Route::put('/membres/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/membres/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
});

// Member space.
Route::middleware('auth:member')->group(function () {
    Route::post('/membre/deconnexion', [AuthenticatedSessionController::class, 'destroyMember'])->name('member.logout');
    Route::get('/membre/tableau-de-bord', MemberDashboardController::class)->name('member.dashboard');
});

// Admin space.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/connexion', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/connexion', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/deconnexion', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/tableau-de-bord', AdminDashboardController::class)->name('dashboard');

        Route::get('/organisations/creer', [AdminOrganizationController::class, 'create'])->name('organizations.create');
        Route::post('/organisations', [AdminOrganizationController::class, 'store'])->name('organizations.store');
        Route::get('/organisations/{organization}/modifier', [AdminOrganizationController::class, 'edit'])->name('organizations.edit');
        Route::put('/organisations/{organization}', [AdminOrganizationController::class, 'update'])->name('organizations.update');

        Route::get('/organisations/importer', [OrganizationImportController::class, 'create'])->name('organizations.import.create');
        Route::post('/organisations/importer', [OrganizationImportController::class, 'store'])->name('organizations.import.store');
    });
});
