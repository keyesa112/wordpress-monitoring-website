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
    Route::post('/websites/{website}/check', [WebsiteController::class, 'check'])->name('websites.check');
    Route::get('/websites/{website}/check', [WebsiteController::class, 'check'])->name('websites.check');

    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Test routes (untuk development - nanti bisa dihapus)
Route::middleware('auth')->prefix('test')->group(function () {
    Route::get('/ping', [TestController::class, 'testPing']);
    Route::get('/posts', [TestController::class, 'testPosts']);
    Route::get('/header-footer', [TestController::class, 'testHeaderFooter']);
    Route::get('/meta', [TestController::class, 'testMeta']);
    Route::get('/sitemap', [TestController::class, 'testSitemap']);
    Route::get('/full-scan', [TestController::class, 'testFullScan']);
});

require __DIR__.'/auth.php';