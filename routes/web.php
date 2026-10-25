<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\WardDisplayController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/display/{ward}', [WardDisplayController::class, 'show'])->name('display.ward');
Route::get('/manifest.webmanifest', static function () {
    $content = file_get_contents(base_path('public/manifest.webmanifest'));

    return response(
        $content !== false ? $content : '',
        200,
        ['Content-Type' => 'application/manifest+json; charset=utf-8'],
    );
});
Route::get('/sw.js', static function () {
    $content = file_get_contents(base_path('public/sw.js'));

    return response(
        $content !== false ? $content : '',
        200,
        ['Content-Type' => 'application/javascript; charset=utf-8'],
    );
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/facility.php';
require __DIR__.'/clinical.php';
