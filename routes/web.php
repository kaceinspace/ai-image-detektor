<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImageReviewController;
use App\Http\Controllers\ProfileController;

// Home page - redirect to upload
Route::get('/', function () {
    return redirect()->route('upload.index');
});

// Public upload routes
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// Image serve route (protected - requires authentication)
Route::get('/images/{image}/serve', function (\App\Models\Image $image) {
    $path = \Illuminate\Support\Facades\Storage::path($image->file_path);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path);
})->middleware('auth')->name('image.serve');

// Dashboard redirect (Laravel Breeze expects 'dashboard' route after login)
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('dashboard');

// Admin routes (with auth middleware)
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Image review routes
    Route::get('/images', [ImageReviewController::class, 'index'])->name('images.index');
    Route::get('/images/{image}', [ImageReviewController::class, 'show'])->name('images.show');
    Route::post('/images/{image}/approve', [ImageReviewController::class, 'approve'])->name('images.approve');
    Route::post('/images/{image}/reject', [ImageReviewController::class, 'reject'])->name('images.reject');
    Route::post('/images/bulk-action', [ImageReviewController::class, 'bulkAction'])->name('images.bulk-action');
});

// Profile management (from Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Auth routes
require __DIR__.'/auth.php';
