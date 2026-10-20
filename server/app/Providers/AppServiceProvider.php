<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Login and recovery: per e-mail AND IP (a club wifi must not lock everybody out); the account lockout
        // after 5 wrong passwords protects the single account
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by('login|'.Str::lower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('recovery', fn (Request $request) => Limit::perMinute(3)->by('recovery|'.Str::lower((string) $request->input('email')).'|'.$request->ip()));
        // Guessing the current password: 5 tries per minute and USER (a plain throttle:5,1 would count per IP)
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
