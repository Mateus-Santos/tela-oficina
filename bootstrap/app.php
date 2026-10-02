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
            /*
             * =====================================================
             * TRUSTED PROXIES
             * =====================================================
             *
             * Preserva o comportamento atual do
             * App\Http\Middleware\TrustProxies.
             */
            $middleware->trustProxies(
                at: '*',
                headers:
                    Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_AWS_ELB
            );

            /*
             * =====================================================
             * REDIRECT DE VISITANTE
             * =====================================================
             *
             * Preserva o comportamento atual do
             * App\Http\Middleware\Authenticate.
             */
            $middleware->redirectGuestsTo(
                fn (Request $request): string =>
                    route('erro-autenticacao')
            );

            /*
             * =====================================================
             * REDIRECT DE USUÁRIO AUTENTICADO
             * =====================================================
             *
             * Substitui gradualmente a dependência de
             * RouteServiceProvider::HOME.
             */
            $middleware->redirectUsersTo(
                '/home'
            );

            /*
             * =====================================================
             * GRUPO WEB
             * =====================================================
             *
             * Laravel 13 já fornece o grupo web padrão:
             *
             * - EncryptCookies
             * - AddQueuedCookiesToResponse
             * - StartSession
             * - ShareErrorsFromSession
             * - PreventRequestForgery
             * - SubstituteBindings
             *
             * Acrescentamos apenas a regra própria da aplicação.
             */
            $middleware->web(
                append: [
                    CheckIfUserIsBlocked::class,
                ]
            );

            /*
             * =====================================================
             * API
             * =====================================================
             *
             * Mantém o throttle utilizado pelo Kernel legado.
             */
            $middleware->throttleApi();

            /*
             * =====================================================
             * ALIASES CUSTOMIZADOS
             * =====================================================
             */
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
            /*
             * Nesta primeira etapa não alteramos ainda
             * o comportamento do Handler legado.
             *
             * A migração completa das exceptions será
             * feita em etapa própria.
             */
        }
    )
    ->create();
