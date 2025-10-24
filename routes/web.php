<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Custom GET logout (untuk AdminLTE menu)
Route::get('/logout', function() {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout.get');

// Protected routes (harus login)
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Website CRUD
    Route::resource('websites', WebsiteController::class);
    
    // Manual check website
    Route::post('/websites/{website}/check', [WebsiteController::class, 'check'])
        ->name('websites.check');
    // Route::get('/websites/{website}/check', [WebsiteController::class, 'check'])
    //     ->name('websites.check');
    
    // File Monitoring Routes
    Route::post('websites/{website}/file-baseline', [WebsiteController::class, 'createFileBaseline'])
        ->name('websites.file-baseline');
    Route::post('websites/{website}/file-scan', [WebsiteController::class, 'scanFiles'])
        ->name('websites.file-scan');
    Route::get('websites/{website}/file-changes', [WebsiteController::class, 'fileChanges'])
        ->name('websites.file-changes');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Test routes (untuk development - nanti bisa dihapus)
Route::middleware('auth')->prefix('test')->group(function () {
    // Scanner tests
    Route::get('/ping', [TestController::class, 'testPing'])->name('test.ping');
    Route::get('/posts', [TestController::class, 'testPosts'])->name('test.posts');
    Route::get('/header-footer', [TestController::class, 'testHeaderFooter'])->name('test.header-footer');
    Route::get('/meta', [TestController::class, 'testMeta'])->name('test.meta');
    Route::get('/sitemap', [TestController::class, 'testSitemap'])->name('test.sitemap');
    Route::get('/full-scan', [TestController::class, 'testFullScan'])->name('test.full-scan');
    
    // Recommendation tests
    Route::get('/recommendations', [TestController::class, 'testRecommendations'])
        ->name('test.recommendations');
    Route::get('/recommendations-dummy', [TestController::class, 'testRecommendationsDummy'])
        ->name('test.recommendations-dummy');
});

require __DIR__.'/auth.php';
