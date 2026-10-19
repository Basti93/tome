<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Intentionally empty: the SPA (public_app) is served by nginx and handles the password reset link
// (/#/reset-password?token=...&email=..., see App\User::getResetUrl). Do not add closure routes here:
// they break `php artisan route:cache`.
