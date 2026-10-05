<?php

declare(strict_types=1);


// ─── Routes ───────────────────────────────────────────────────
use App\Middleware\JwtAuthMiddleware;

return function (App $app): void {

    // ─── Auth ─────────────────────────────────────────────────────
    $app->post('/auth/register', RegisterAction::class);
    $app->post('/auth/login', LoginAction::class);
    $app->post('/auth/refresh', RefreshAction::class);
    $app->post('/auth/logout', LogoutAction::class);


    // JwtAuthMiddleware is added last -> runs first (outermost), so it
    $app->get('/me', MeAction::class)
        //todo
        ->add(JwtAuthMiddleware::class);

};