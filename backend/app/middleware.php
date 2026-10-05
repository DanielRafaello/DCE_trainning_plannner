<?php

declare(strict_types=1);

use App\Middleware\CorsMiddleware;
use App\Middleware\HttpErrorHandler;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Doctrine\DBAL\Connection;
use Slim\App;

return function (App $app): void {

    $container = $app->getContainer();

    // ─── Security headers ──────────────────────────────────────────
    $app->add(new SecurityHeadersMiddleware());

    // ─── Error handler – single instance shared with the rate limiter ─────
    $errorHandler = new HttpErrorHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
    );

    // ─── Rate limiting ──────────────────────────────────────────
    $app->add(new RateLimitMiddleware(
        connection: $container->get(Connection::class),
        maxRequests: 50,
        windowSeconds: 60,
        httpErrorHandler: $errorHandler,
    ));

    // ─── Routing ───────────────────────────────────────────────────
    $app->addRoutingMiddleware();

    // ─── Body parsing (JSON request bodies) ─────────────────────────
    $app->addBodyParsingMiddleware();


    // ─── Error middleware – ALWAYS last ─────────────────────────────
    $errorMiddleware = $app->addErrorMiddleware(
        displayErrorDetails: ($_ENV['APP_ENV'] ?? 'production') !== 'production',
        logErrors: true,
        logErrorDetails: true,
    );
    $errorMiddleware->setDefaultErrorHandler($errorHandler);

    // ─── CORS – added last so it's the outermost layer, wrapping even ───
    // thrown exceptions the error middleware catches (an error response
    // still needs CORS headers or the browser hides it from the frontend's
    // fetch() entirely). Also short-circuits OPTIONS preflight before
    // routing/rate-limiting/auth ever run.
    $app->add($container->get(CorsMiddleware::class));

};
