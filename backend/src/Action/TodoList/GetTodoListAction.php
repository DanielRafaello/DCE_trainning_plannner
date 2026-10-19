<?php

declare(strict_types=1);

namespace App\Action\TodoList;

use App\Responder\TodoList\TodoListResponder;
use App\Services\Contracts\TodoList\TodoListServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /todo-lists/{id} — one list including its HTML content (FR-TODO-02).
 *
 * Auth:     JWT.
 *
 * Response: 200 { "todo_list": { "id", "title", "content", "created_at", "updated_at" } }
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           404 not.found.todo_list — also for another user's list.
 */
final readonly class GetTodoListAction
{
    public function __construct(
        private TodoListServiceInterface $todoListService,
        private TodoListResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $todoList = $this->todoListService->get((int) $request->getAttribute('user_id'), (int) $args['id']);

        return $this->responder->one($response, $todoList);
    }
}
