<?php

use App\Http\Controllers\Public\WardDisplayController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('/display/{ward}', [WardDisplayController::class, 'show'])->name('display.ward');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/facility.php';
require __DIR__.'/clinical.php';
