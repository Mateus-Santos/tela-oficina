<?php

use App\Http\Middleware\AdminAccess;
use App\Http\Middleware\CheckIfUserIsBlocked;
use App\Http\Middleware\PermitionAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        function (Middleware $middleware): void {

            $middleware->trustProxies(
                at: '*',
                headers:
                    Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_AWS_ELB
            );

            $middleware->redirectGuestsTo(
                fn (Request $request): string =>
                    route('erro-autenticacao')
            );

            $middleware->redirectUsersTo(
                '/home'
            );

            $middleware->web(
                append: [
                    CheckIfUserIsBlocked::class,
                ]
            );

            $middleware->throttleApi();

            $middleware->alias([
                'check.blocked' =>
                    CheckIfUserIsBlocked::class,

                'admin' =>
                    AdminAccess::class,

                'permition.colaborator' =>
                    PermitionAccess::class,
            ]);
        }
    )
    ->withExceptions(
        function (Exceptions $exceptions): void {
        }
    )
    ->create();
