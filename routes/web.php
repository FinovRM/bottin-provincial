<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LoginLinkController;
use App\Http\Controllers\BottinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::get('/', BottinController::class)->name('bottin');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginLinkController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginLinkController::class, 'store'])->name('login.store');
    Route::view('/connexion/envoye', 'auth.link-sent')->name('login.sent');
    Route::get('/connexion/{organization}/verifier', [AuthenticatedSessionController::class, 'store'])
        ->middleware('signed')
        ->name('login.consume');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    Route::post('/organisations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('/organisations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organisations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
});
