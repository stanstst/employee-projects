<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e) {
            Log::info('ValidationException:' . $e->getMessage());
            Log::info('ValidationException Stack trace:' . $e->getTraceAsString());
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 400);
        });

        // Anything else gets a generic message; details are only logged, never returned.
        $exceptions->render(function (Throwable $e) {
            Log::info('Error:' . $e->getMessage());
            Log::info('Error Stack trace:' . $e->getTraceAsString());
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();

                return response()->json(
                    ['message' => $e->getMessage() ?: Response::$statusTexts[$status] ?? 'Error'],
                    $status,
                    $e->getHeaders(),
                );
            }

            return response()->json(['message' => 'Server Error'], 500);
        });
    })->create();
