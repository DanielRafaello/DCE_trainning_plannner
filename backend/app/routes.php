<?php

declare(strict_types=1);


// ─── Routes ───────────────────────────────────────────────────
use App\Action\Auth\LoginAction;
use App\Action\Auth\LogoutAction;
use App\Action\Auth\RefreshAction;
use App\Action\Auth\RegisterAction;
use App\Action\Category\ListCategoriesAction;
use App\Action\Subcategory\CreateSubcategoryAction;
use App\Action\Subcategory\DeleteSubcategoryAction;
use App\Action\Subcategory\ListSubcategoriesAction;
use App\Action\Subcategory\UpdateSubcategoryAction;
use App\Action\User\MeAction;
use App\Middleware\JwtAuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {

    // ─── Auth ─────────────────────────────────────────────────────
    $app->post('/auth/register', RegisterAction::class);
    $app->post('/auth/login', LoginAction::class);
    $app->post('/auth/refresh', RefreshAction::class);
    $app->post('/auth/logout', LogoutAction::class);


    // JwtAuthMiddleware is added last -> runs first (outermost), so it
    $app->get('/me', MeAction::class)
        ->add(JwtAuthMiddleware::class);

    // ─── Categories (JWT) ─────────────────────────
    $app->get('/categories', ListCategoriesAction::class)
        ->add(JwtAuthMiddleware::class);

    // ─── Subcategories (JWT) ─────────────────────────
    $app->group('/subcategories', function (RouteCollectorProxy $group): void {

        $group->get('', ListSubcategoriesAction::class);
        $group->post('', CreateSubcategoryAction::class);
        $group->patch('/{id:[0-9]+}', UpdateSubcategoryAction::class);
        $group->delete('/{id:[0-9]+}', DeleteSubcategoryAction::class);
    })->add(JwtAuthMiddleware::class);

};