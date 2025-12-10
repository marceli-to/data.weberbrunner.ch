<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// SPA routes (protected by auth middleware)
Route::middleware('auth')->group(function () {
    Route::get('/{any?}', function () {
        return view('app');
    })->where('any', '.*');
});
