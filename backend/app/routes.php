<?php

declare(strict_types=1);


// ─── Routes ───────────────────────────────────────────────────
use App\Action\Auth\LoginAction;
use App\Action\Auth\LogoutAction;
use App\Action\Auth\RefreshAction;
use App\Action\Auth\RegisterAction;
use App\Action\Category\ListCategoriesAction;
use App\Action\DayNote\GetDayNoteAction;
use App\Action\DayNote\SaveDayNoteAction;
use App\Action\Subcategory\CreateSubcategoryAction;
use App\Action\Subcategory\DeleteSubcategoryAction;
use App\Action\Subcategory\ListSubcategoriesAction;
use App\Action\Subcategory\UpdateSubcategoryAction;
use App\Action\TodoList\CreateTodoListAction;
use App\Action\TodoList\DeleteTodoListAction;
use App\Action\TodoList\GetTodoListAction;
use App\Action\TodoList\ListTodoListsAction;
use App\Action\TodoList\UpdateTodoListAction;
use App\Action\User\ChangePasswordAction;
use App\Action\User\DeleteAccountAction;
use App\Action\User\MeAction;
use App\Action\User\UpdateProfileAction;
use App\Middleware\JwtAuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {

    // ─── Auth ─────────────────────────────────────────────────────
    $app->post('/auth/register', RegisterAction::class);
    $app->post('/auth/login', LoginAction::class);
    $app->post('/auth/refresh', RefreshAction::class);
    $app->post('/auth/logout', LogoutAction::class);


    // ─── Own account (JWT) ─────────────────────────
    // JwtAuthMiddleware is added last -> runs first (outermost), so it
    $app->group('/me', function (RouteCollectorProxy $group): void {

        $group->get('', MeAction::class);
        $group->patch('', UpdateProfileAction::class);
        $group->delete('', DeleteAccountAction::class);
        $group->put('/password', ChangePasswordAction::class);
    })->add(JwtAuthMiddleware::class);

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

    // ─── To-do lists (JWT) ─────────────────────────
    $app->group('/todo-lists', function (RouteCollectorProxy $group): void {

        $group->get('', ListTodoListsAction::class);
        $group->post('', CreateTodoListAction::class);
        $group->get('/{id:[0-9]+}', GetTodoListAction::class);
        $group->patch('/{id:[0-9]+}', UpdateTodoListAction::class);
        $group->delete('/{id:[0-9]+}', DeleteTodoListAction::class);
    })->add(JwtAuthMiddleware::class);

    // ─── Day notes (JWT) ─────────────────────────
    // the pattern only checks the shape, DayNoteService rejects impossible dates (2026-02-30)
    $app->group('/days/{date:[0-9]{4}-[0-9]{2}-[0-9]{2}}', function (RouteCollectorProxy $group): void {

        $group->get('/note', GetDayNoteAction::class);
        $group->put('/note', SaveDayNoteAction::class);
    })->add(JwtAuthMiddleware::class);

};