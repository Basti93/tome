<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Guessing the current password: 5 tries per minute and USER. (A plain throttle:5,1 would count per IP and
        // together with the login/recovery limits, which share one counter per IP.)
        RateLimiter::for('change-password', fn (Request $request) => Limit::perMinute(5)->by('change-password|'.(Auth::guard('api')->id() ?? $request->ip())));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
