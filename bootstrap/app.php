<?php

use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\NoCacheHeaders;
use App\Http\Middleware\SanitizeRequestLogging;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ThrottleWithPow;
use App\Http\Middleware\TrackHttpErrors;
use App\Http\Middleware\TrackPageView;
use App\Http\Middleware\TrackResponseTime;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(SanitizeRequestLogging::class);
        $middleware->prepend(ForceHttps::class);
        $middleware->append(SetLocale::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->appendToGroup('web', TrackPageView::class);
        $middleware->appendToGroup('web', TrackHttpErrors::class);
        $middleware->appendToGroup('web', TrackResponseTime::class);
        $middleware->appendToGroup('web', \Illuminate\Routing\Middleware\ThrottleRequests::class.':global');
        $middleware->appendToGroup('api', TrackHttpErrors::class);
        $middleware->appendToGroup('api', TrackResponseTime::class);
        $middleware->appendToGroup('api', \Illuminate\Routing\Middleware\ThrottleRequests::class.':global');

        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: SetLocale::class,
        );

        $middleware->encryptCookies(except: ['tz_offset']);

        $middleware->alias([
            'throttle.pow' => ThrottleWithPow::class,
            'no.cache' => NoCacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $e) {
            return response()->view('errors.minimal', [], 500);
        });
    })->create();
