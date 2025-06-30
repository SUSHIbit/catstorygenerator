<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Document routes
    Route::resource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/retry', [DocumentController::class, 'retry'])->name('documents.retry');
    Route::post('documents/{document}/process-now', [DocumentController::class, 'processNow'])->name('documents.process-now');
    Route::post('documents/{document}/generate-story', [DocumentController::class, 'generateStory'])->name('documents.generate-story');
    Route::post('documents/{document}/regenerate-story', [DocumentController::class, 'regenerateStory'])->name('documents.regenerate-story');
});

require __DIR__.'/auth.php';