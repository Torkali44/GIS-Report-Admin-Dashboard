<?php

use App\Http\Controllers\Admin\InspectionAreaController;
use App\Http\Controllers\Admin\PropertyHouseController;
use App\Http\Controllers\Admin\ReadyNotesController;
use App\Http\Controllers\Admin\ReadySectionsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:12,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', fn() => redirect()->route('admin.houses.index'));
    Route::get('houses/{house}/report.pdf', [PropertyHouseController::class, 'report'])->name('houses.report');
    Route::get('houses/{house}/report.docx', [PropertyHouseController::class, 'reportWord'])->name('houses.report.word');
    Route::resource('houses', PropertyHouseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::patch('houses/{house}/final-result', [PropertyHouseController::class, 'updateFinalResult'])->name('houses.final-result.update');
    Route::post('houses/{house}/areas', [InspectionAreaController::class, 'store'])->name('houses.areas.store');
    Route::patch('houses/{house}/areas/{area}', [InspectionAreaController::class, 'update'])->name('houses.areas.update');
    Route::patch('houses/{house}/areas/{area}/reorder', [InspectionAreaController::class, 'reorder'])->name('houses.areas.reorder');
    Route::delete('houses/{house}/areas/{area}', [InspectionAreaController::class, 'destroy'])->name('houses.areas.destroy');

    // Ready Notes JSON API (for dropdowns)
    Route::get('api/ready-notes/categories', [ReadyNotesController::class, 'categoriesJson'])->name('api.ready-notes.categories');
    Route::get('api/ready-notes/categories/{category}/notes', [ReadyNotesController::class, 'notesByCategoryJson'])->name('api.ready-notes.notes');
    Route::get('api/ready-notes/categories/{category}/recommendations', [ReadyNotesController::class, 'recommendationsByCategoryJson'])->name('api.ready-notes.recommendations');

    // Ready Sections Management
    Route::get('ready-sections', [ReadySectionsController::class, 'manage'])->name('ready-sections.manage');
    Route::post('ready-sections', [ReadySectionsController::class, 'store'])->name('ready-sections.store');
    Route::patch('ready-sections/{section}', [ReadySectionsController::class, 'update'])->name('ready-sections.update');
    Route::delete('ready-sections/{section}', [ReadySectionsController::class, 'destroy'])->name('ready-sections.destroy');

    // Ready Notes Management
    Route::get('ready-notes', [ReadyNotesController::class, 'manage'])->name('ready-notes.manage');
    Route::post('ready-notes/categories', [ReadyNotesController::class, 'storeCategory'])->name('ready-notes.categories.store');
    Route::patch('ready-notes/categories/{category}', [ReadyNotesController::class, 'updateCategory'])->name('ready-notes.categories.update');
    Route::delete('ready-notes/categories/{category}', [ReadyNotesController::class, 'destroyCategory'])->name('ready-notes.categories.destroy');
    Route::post('ready-notes/notes', [ReadyNotesController::class, 'storeNote'])->name('ready-notes.notes.store');
    Route::patch('ready-notes/notes/{note}', [ReadyNotesController::class, 'updateNote'])->name('ready-notes.notes.update');
    Route::delete('ready-notes/notes/{note}', [ReadyNotesController::class, 'destroyNote'])->name('ready-notes.notes.destroy');
    Route::post('ready-notes/recommendations', [ReadyNotesController::class, 'storeRecommendation'])->name('ready-notes.recommendations.store');
    Route::patch('ready-notes/recommendations/{recommendation}', [ReadyNotesController::class, 'updateRecommendation'])->name('ready-notes.recommendations.update');
    Route::delete('ready-notes/recommendations/{recommendation}', [ReadyNotesController::class, 'destroyRecommendation'])->name('ready-notes.recommendations.destroy');
});

