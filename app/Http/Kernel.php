<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class Kernel extends HttpKernel
{
//    protected middleware = [
//        \Fruitcake\Cors\HandleCors::class,
//
//        ];

    protected $middlewareGroups = [
        'web' => [
//            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
             EnsureFrontendRequestsAreStateful::class,
            'throttle:api', //throttling middleware to limit the number of requests a client can make in a time.
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
    //specific routes or a group of route
    protected $routeMiddleware = [
        'auth' => Authenticate::class, //'auth' is the alias
    ];

}




