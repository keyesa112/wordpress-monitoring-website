<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\Website; 

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
     public function boot(): void
        {
            Route::bind('website', function ($value) {
                return Website::where('id', $value)
                    ->where('user_id', auth()->id())
                    ->firstOrFail(); // 404 kalau bukan milik user
            });
        }
}
