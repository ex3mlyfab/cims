<?php

use App\Http\Controllers\Facility\BedController;
use App\Http\Controllers\Facility\TeamController;
use App\Http\Controllers\Facility\WardController;
use Illuminate\Support\Facades\Route;

/*
 * CIMA Phase 4: facility modules (teams, wards, beds). Every route is
 * permission-checked server-side; record rules live in policies.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware(['permission:teams.view'])->group(function () {
        Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    });

    Route::middleware(['permission:teams.manage'])->group(function () {
        Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
        Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
        Route::get('teams/{team}/edit', [TeamController::class, 'edit'])->name('teams.edit');
        Route::put('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    });

    // CIMA-044: wards (CRUD, capacity, unsafe-delete prevention).
    Route::middleware(['permission:wards.view'])->group(function () {
        Route::get('wards', [WardController::class, 'index'])->name('wards.index');
    });

    Route::middleware(['permission:wards.manage'])->group(function () {
        Route::get('wards/create', [WardController::class, 'create'])->name('wards.create');
        Route::post('wards', [WardController::class, 'store'])->name('wards.store');
        Route::get('wards/{ward}/edit', [WardController::class, 'edit'])->name('wards.edit');
        Route::put('wards/{ward}', [WardController::class, 'update'])->name('wards.update');
        Route::delete('wards/{ward}', [WardController::class, 'destroy'])->name('wards.destroy');
    });

    // CIMA-045: beds (CRUD + bed state machine; `occupied` is CIMA-060's).
    Route::middleware(['permission:beds.view'])->group(function () {
        Route::get('beds', [BedController::class, 'index'])->name('beds.index');
    });

    Route::middleware(['permission:beds.manage'])->group(function () {
        Route::get('beds/create', [BedController::class, 'create'])->name('beds.create');
        Route::post('beds', [BedController::class, 'store'])->name('beds.store');
        Route::get('beds/{bed}/edit', [BedController::class, 'edit'])->name('beds.edit');
        Route::put('beds/{bed}', [BedController::class, 'update'])->name('beds.update');
        Route::delete('beds/{bed}', [BedController::class, 'destroy'])->name('beds.destroy');
    });

    // CIMA-047: dedicated bed history page. Registered after `/beds/create`
    // so the static segment is never swallowed by the `{bed}` parameter;
    // history is read-only, hence view (not manage) permission.
    Route::get('beds/{bed}', [BedController::class, 'show'])
        ->middleware(['permission:beds.view'])
        ->name('beds.show');
});
