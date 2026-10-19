<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\JwtFromCookie;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt.auth' => Authenticate::class,
            'jwt.refresh' => RefreshToken::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
        // The API always answers JSON: a missing Accept header must never turn a validation or auth
        // failure into a redirect (which fetch() follows to the SPA's index.html).
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
        $middleware->api(append: [
            HandleCors::class,
            JwtFromCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
        // With APP_DEBUG=false no exception text may reach the client (class names, SQL, model names, paths).
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if (! config('app.debug')) {
                return response()->json(['message' => 'Not Found'], 404);
            }
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! config('app.debug') && $e->getStatusCode() >= 500) {
                return response()->json(['message' => 'Server Error'], $e->getStatusCode());
            }
        });
    })->create();
