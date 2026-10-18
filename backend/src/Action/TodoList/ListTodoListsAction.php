<?php

declare(strict_types=1);

namespace App\Action\TodoList;

use App\Responder\TodoList\TodoListResponder;
use App\Services\Contracts\TodoList\TodoListServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /to do-lists — overview of the user's lists without content (FR-TO DO-01, FR-TO DO-05).
 *
 * Auth:     JWT.
 *
 * Response: 200 { "todo_lists": [ { "id", "title", "updated_at" } ] } most recently edited first
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 */
final readonly class ListTodoListsAction
{
    public function __construct(
        private TodoListServiceInterface $todoListService,
        private TodoListResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        return $this->responder->list(
            $response,
            $this->todoListService->list((int) $request->getAttribute('user_id')),
        );
    }
}
