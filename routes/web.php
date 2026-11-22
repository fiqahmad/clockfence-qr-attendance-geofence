<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\ClockPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\SitePosterController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['guest', 'signed'])->group(function () {
    Route::get('/invitations/{user}', [InvitationController::class, 'show'])->name('invitations.show');
    Route::post('/invitations/{user}', [InvitationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('invitations.store');
});

Route::middleware('auth')->prefix('clock')->name('clock.')->group(function () {
    Route::get('/', ClockPageController::class)->name('page');
    Route::post('/geo-check', [ClockController::class, 'geoCheck'])->name('geo-check');
    Route::post('/', [ClockController::class, 'store'])->name('store');
});

Route::get('/sites/{site}/poster', SitePosterController::class)
    ->middleware(['auth', 'can:view,site'])
    ->name('sites.poster');
