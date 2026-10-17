<?php

declare(strict_types=1);


// ─── Routes ───────────────────────────────────────────────────
use App\Action\Auth\LoginAction;
use App\Action\Auth\LogoutAction;
use App\Action\Auth\RefreshAction;
use App\Action\Auth\RegisterAction;
use App\Action\User\MeAction;
use App\Middleware\JwtAuthMiddleware;
use Slim\App;

return function (App $app): void {

    // ─── Auth ─────────────────────────────────────────────────────
    $app->post('/auth/register', RegisterAction::class);
    $app->post('/auth/login', LoginAction::class);
    $app->post('/auth/refresh', RefreshAction::class);
    $app->post('/auth/logout', LogoutAction::class);


    // JwtAuthMiddleware is added last -> runs first (outermost), so it
    $app->get('/me', MeAction::class)
        ->add(JwtAuthMiddleware::class);

};