<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $apiKey = $request->header('API-Key');
            if ($apiKey) {
                return Limit::perMinute(300)->by('key:' . $apiKey)->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many requests. Please slow down.',
                ], 429));
            }
            return Limit::perMinute(60)->by($request->ip())->response(fn () => response()->json([
                'status' => 'error',
                'message' => 'Too many requests. Please slow down.',
            ], 429));
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by('auth:' . $request->ip())->response(fn () => response()->json([
                'status' => 'error',
                'message' => 'Too many login attempts. Please try again in a minute.',
            ], 429));
        });

        RateLimiter::for('otp', function (Request $request) {
            $email = strtolower($request->input('email', ''));
            return [
                Limit::perMinutes(15, 5)->by('otp:email:' . $email)->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many OTP attempts. Please wait 15 minutes before trying again.',
                ], 429)),
                Limit::perMinutes(15, 10)->by('otp:ip:' . $request->ip())->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many OTP attempts from this IP. Please wait 15 minutes.',
                ], 429)),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = strtolower($request->input('email', ''));
            return [
                Limit::perMinutes(15, 3)->by('pwd:email:' . $email)->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many password reset requests. Please wait 15 minutes.',
                ], 429)),
                Limit::perMinutes(15, 5)->by('pwd:ip:' . $request->ip())->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many password reset requests from this IP. Please wait 15 minutes.',
                ], 429)),
            ];
        });

        RateLimiter::for('orders', function (Request $request) {
            return Limit::perMinute(10)->by('order:user:' . $request->user()?->id)->response(fn () => response()->json([
                'status' => 'error',
                'message' => 'You are placing orders too quickly. Please wait a moment.',
            ], 429));
        });

        RateLimiter::for('reviews', function (Request $request) {
            return Limit::perMinute(5)->by('review:user:' . $request->user()?->id)->response(fn () => response()->json([
                'status' => 'error',
                'message' => 'You are submitting reviews too quickly. Please slow down.',
            ], 429));
        });

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
