<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Welcome page - accessible to everyone
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authentication Required Routes
|--------------------------------------------------------------------------
*/

// Dashboard - main application entry point
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Profile Management Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    /*
    |--------------------------------------------------------------------------
    | Document Routes with Large File Support
    |--------------------------------------------------------------------------
    */
    
    // Standard RESTful document routes
    Route::resource('documents', DocumentController::class);
    
    /*
    |--------------------------------------------------------------------------
    | Additional Document Action Routes
    |--------------------------------------------------------------------------
    */
    
    // Download original document file
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
         ->name('documents.download');
    
    // Retry processing for failed documents
    Route::post('documents/{document}/retry', [DocumentController::class, 'retry'])
         ->name('documents.retry');
    
    // Manually trigger immediate processing (for testing/debugging)
    Route::post('documents/{document}/process-now', [DocumentController::class, 'processNow'])
         ->name('documents.process-now');
    
    // Generate cat story for documents that have content but no story
    Route::post('documents/{document}/generate-story', [DocumentController::class, 'generateStory'])
         ->name('documents.generate-story');
    
    // Regenerate cat story (replace existing story)
    Route::post('documents/{document}/regenerate-story', [DocumentController::class, 'regenerateStory'])
         ->name('documents.regenerate-story');

    /*
    |--------------------------------------------------------------------------
    | Additional Utility Routes (Optional)
    |--------------------------------------------------------------------------
    */
    
    // API endpoint for checking document processing status (for AJAX)
    Route::get('documents/{document}/status', function(\App\Models\Document $document) {
        return response()->json([
            'status' => $document->status,
            'has_story' => $document->hasStory(),
            'error_message' => $document->error_message,
            'processed_at' => $document->processed_at?->toISOString(),
        ]);
    })->name('documents.status');
    
    // API endpoint for getting processing statistics
    Route::get('/api/documents/stats', function() {
        $user = auth()->user();
        return response()->json([
            'total' => $user->documents()->count(),
            'completed' => $user->documents()->completed()->count(),
            'processing' => $user->documents()->where('status', 'processing')->count(),
            'failed' => $user->documents()->where('status', 'failed')->count(),
        ]);
    })->name('api.documents.stats');

});

/*
|--------------------------------------------------------------------------
| Include Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';