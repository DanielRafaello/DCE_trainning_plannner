<?php

declare(strict_types=1);

namespace App\Action\TodoList;

use App\Responder\TodoList\TodoListResponder;
use App\Services\Contracts\TodoList\TodoListServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * DELETE /todo-lists/{id} — delete a list (FR-TODO-03).
 *
 * Auth:     JWT.
 *
 * Response: 204 (no body)
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           404 not.found.todo_list — also for another user's list.
 */
final readonly class DeleteTodoListAction
{
    public function __construct(
        private TodoListServiceInterface $todoListService,
        private TodoListResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->todoListService->delete((int) $request->getAttribute('user_id'), (int) $args['id']);

        return $this->responder->deleted($response);
    }
}
