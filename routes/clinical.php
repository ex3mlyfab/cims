<?php

use App\Http\Controllers\Clinical\EncounterController;
use App\Http\Controllers\Clinical\PatientController;
use App\Http\Controllers\Clinical\TreatmentStatusController;
use Illuminate\Support\Facades\Route;

/*
 * CIMA Phase 5: clinical modules (patient registry, later admission
 * records). Every route is permission-checked server-side; record rules
 * live in policies.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    // CIMA-050: patient search & registration (search-first module).
    Route::middleware(['permission:patients.view'])->group(function () {
        Route::get('patients', [PatientController::class, 'index'])->name('patients.index');
    });

    // Registration is its own permission (doctors/ED managers can search
    // without being able to create; records officers/nurses register).
    Route::middleware(['permission:patients.create'])->group(function () {
        Route::post('patients', [PatientController::class, 'store'])->name('patients.store');
    });

    // CIMA-051: admission-record create flow.
    Route::middleware(['permission:encounters.manage'])->group(function () {
        Route::get('encounters', [EncounterController::class, 'index'])->name('encounters.index');
        Route::get('encounters/create', [EncounterController::class, 'create'])->name('encounters.create');
        Route::get('encounters/patient-search', [EncounterController::class, 'searchPatients'])->name('encounters.patients.search');
        Route::post('encounters', [EncounterController::class, 'store'])->name('encounters.store');
        Route::get('encounters/{encounter}/edit', [EncounterController::class, 'edit'])->name('encounters.edit');
        Route::put('encounters/{encounter}', [EncounterController::class, 'update'])->name('encounters.update');
        Route::delete('encounters/{encounter}/discharge', [EncounterController::class, 'discharge'])->name('encounters.discharge');
    });

    // CIMA-052: treatment status registry.
    Route::middleware(['permission:statuses.view'])->group(function () {
        Route::get('treatment-statuses', [TreatmentStatusController::class, 'index'])->name('treatment-statuses.index');
    });

    Route::middleware(['permission:statuses.manage'])->group(function () {
        Route::get('treatment-statuses/create', [TreatmentStatusController::class, 'create'])->name('treatment-statuses.create');
        Route::post('treatment-statuses', [TreatmentStatusController::class, 'store'])->name('treatment-statuses.store');
        Route::get('treatment-statuses/{status}/edit', [TreatmentStatusController::class, 'edit'])->name('treatment-statuses.edit');
        Route::put('treatment-statuses/{status}', [TreatmentStatusController::class, 'update'])->name('treatment-statuses.update');
        Route::delete('treatment-statuses/{status}', [TreatmentStatusController::class, 'destroy'])->name('treatment-statuses.destroy');
    });
});
