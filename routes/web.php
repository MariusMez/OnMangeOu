<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\AnalysisImageController;
use App\Http\Controllers\AnalysisPdfController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AnalysisController::class, 'create'])->name('home');

Route::get('/analyses', [AnalysisController::class, 'index'])->name('analyses.index');
Route::post('/analyses', [AnalysisController::class, 'store'])->name('analyses.store');
Route::get('/analyses/{analysis}', [AnalysisController::class, 'show'])->name('analyses.show');
Route::get('/analyses/{analysis}/pdf', [AnalysisPdfController::class, 'show'])->name('analyses.pdf');
Route::get('/analyses/{analysis}/images/{index}', [AnalysisImageController::class, 'show'])
    ->whereNumber('index')
    ->name('analyses.image');

Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
