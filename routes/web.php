<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\MemberImportController as AdminMemberImportController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\OrganizationImportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\BottinLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginLinkController;
use App\Http\Controllers\AllowedRoleController;
use App\Http\Controllers\Bottin\DashboardController as BottinDashboardController;
use App\Http\Controllers\Bottin\OrganizationDirectoryController as BottinOrganizationDirectoryController;
use App\Http\Controllers\Bottin\OrganizationMembersController as BottinOrganizationMembersController;
use App\Http\Controllers\BottinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberSearchController;
use App\Http\Controllers\MinimumRoleController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSearchController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\RoleSwitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', BottinController::class)->name('bottin');

Route::middleware('guest:web,member')->group(function () {
    Route::get('/connexion', LoginController::class)->name('login');

    Route::post('/connexion/bottin', [LoginLinkController::class, 'storeBottin'])->name('login.bottin.store');

    Route::view('/connexion/envoye', 'auth.link-sent')->name('login.sent');
});

Route::get('/connexion/verifier', [BottinLoginController::class, 'show'])
    ->middleware(['signed', 'guest:web,member'])
    ->name('bottin-login.verify');

Route::post('/connexion/verifier', [BottinLoginController::class, 'store'])
    ->middleware(['signed', 'guest:web,member']);

// Bottin — consultation, shared by members and by responsables using the Bottin door.
Route::middleware('auth:member,web')->group(function () {
    Route::get('/bottin', BottinDashboardController::class)->name('bottin.index');
    Route::get('/bottin/exporter', [BottinDashboardController::class, 'export'])->name('bottin.export');
    Route::get('/bottin/organisations', BottinOrganizationDirectoryController::class)->name('bottin.organizations');
    Route::get('/bottin/organisations/{organization}/membres', BottinOrganizationMembersController::class)->name('bottin.organization-members');
    Route::post('/role', [RoleSwitchController::class, 'store'])->name('role.switch');
    Route::get('/profil', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profil/filtres', [ProfileController::class, 'storeFilter'])->name('profile.filters.store');
    Route::put('/profil/filtres/{personalFilter}', [ProfileController::class, 'updateFilter'])->name('profile.filters.update');
    Route::delete('/profil/filtres/{personalFilter}', [ProfileController::class, 'destroyFilter'])->name('profile.filters.destroy');
});

Route::post('/membre/deconnexion', [AuthenticatedSessionController::class, 'destroyMember'])
    ->middleware('auth:member')
    ->name('member.logout');

// Éditeur (organization) space.
Route::middleware('auth:web')->group(function () {
    Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/tableau-de-bord/organisations-gerees', [OrganizationSwitchController::class, 'index'])->name('dashboard.switch');
    Route::post('/tableau-de-bord/organisations-gerees/{organization}', [OrganizationSwitchController::class, 'store'])->name('dashboard.switch.store');

    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    Route::get('/tableau-de-bord/proprietes', [PropertiesController::class, 'show'])->name('dashboard.properties');

    Route::get('/tableau-de-bord/proprietes/responsable/modifier', [ResponsableController::class, 'edit'])->name('responsable.edit');
    Route::post('/tableau-de-bord/proprietes/responsable/modifier', [ResponsableController::class, 'update'])->name('responsable.update');

    Route::get('/tableau-de-bord/organisations', OrganizationSearchController::class)->name('dashboard.organizations');
    Route::get('/tableau-de-bord/organisations/exporter', [OrganizationSearchController::class, 'export'])->name('dashboard.organizations.export');
    Route::get('/tableau-de-bord/membres', MemberSearchController::class)->name('dashboard.members');

    Route::get('/tableau-de-bord/proprietes/organisation/modifier', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::get('/organisations/ajouter', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organisations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('/organisations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organisations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

    Route::get('/membres/ajouter', [MemberController::class, 'create'])->name('members.create');
    Route::post('/membres/ajouter', [MemberController::class, 'createConfirmed']);
    Route::post('/membres', [MemberController::class, 'store'])->name('members.store');
    Route::delete('/membres/{memberRole}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::post('/profil/roles-minimum', [MinimumRoleController::class, 'store'])->name('minimum-roles.store');
    Route::put('/profil/roles-minimum/{minimumRole}', [MinimumRoleController::class, 'update'])->name('minimum-roles.update');
    Route::delete('/profil/roles-minimum/{minimumRole}', [MinimumRoleController::class, 'destroy'])->name('minimum-roles.destroy');

    Route::post('/profil/roles-permis', [AllowedRoleController::class, 'store'])->name('allowed-roles.store');
    Route::put('/profil/roles-permis/{allowedRole}', [AllowedRoleController::class, 'update'])->name('allowed-roles.update');
    Route::delete('/profil/roles-permis/{allowedRole}', [AllowedRoleController::class, 'destroy'])->name('allowed-roles.destroy');
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

        Route::get('/organisations', [AdminOrganizationController::class, 'index'])->name('organizations.index');
        Route::get('/organisations/creer', [AdminOrganizationController::class, 'create'])->name('organizations.create');
        Route::post('/organisations', [AdminOrganizationController::class, 'store'])->name('organizations.store');
        Route::get('/organisations/{organization}/modifier', [AdminOrganizationController::class, 'edit'])->name('organizations.edit');
        Route::put('/organisations/{organization}', [AdminOrganizationController::class, 'update'])->name('organizations.update');
        Route::delete('/organisations/{organization}', [AdminOrganizationController::class, 'destroy'])->name('organizations.destroy');

        Route::get('/organisations/importer', [OrganizationImportController::class, 'create'])->name('organizations.import.create');
        Route::post('/organisations/importer', [OrganizationImportController::class, 'store'])->name('organizations.import.store');

        Route::get('/membres', [AdminMemberController::class, 'index'])->name('members.index');
        Route::post('/membres', [AdminMemberController::class, 'store'])->name('members.store');
        Route::delete('/membres/{memberRole}', [AdminMemberController::class, 'destroy'])->name('members.destroy');

        Route::get('/membres/importer', [AdminMemberImportController::class, 'create'])->name('members.import.create');
        Route::post('/membres/importer', [AdminMemberImportController::class, 'store'])->name('members.import.store');
    });
});
