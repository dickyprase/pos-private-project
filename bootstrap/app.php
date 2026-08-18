<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->web(append: [EnsureUserIsActive::class]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e) => $request->is('api/*'));
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) return null;
            return response()->json(['success' => false, 'data' => null, 'message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        });
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) return null;
            return response()->json(['success' => false, 'data' => null, 'message' => 'Unauthenticated.', 'errors' => null], 401);
        });
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) return null;
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = $status >= 500 ? 'Server error.' : ($e->getMessage() ?: 'Request gagal.');
            return response()->json(['success' => false, 'data' => null, 'message' => $message, 'errors' => null], $status);
        });
    })->create();
