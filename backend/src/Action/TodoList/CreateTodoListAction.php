<?php

declare(strict_types=1);

namespace App\Action\TodoList;

use App\Exceptions\Validation\ValidationException;
use App\Requests\TodoList\CreateTodoListRequest;
use App\Responder\TodoList\TodoListResponder;
use App\Services\Contracts\TodoList\TodoListServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /to do-lists — create a list (FR-TODO-01, FR-TODO-03).
 *
 * Auth:     JWT.
 * Body:     { "title", "content"? } — content is HTML from the editor, sanitized before it is stored
 *
 * Response: 201 { "todo_list": { "id", "title", "content", "created_at", "updated_at" } }
 *           content is the sanitized HTML – the client should show this, not what it sent
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — title: todo_list.title_length; content: todo_list.content_too_long
 */
final readonly class CreateTodoListAction
{
    public function __construct(
        private TodoListServiceInterface $todoListService,
        private TodoListResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new CreateTodoListRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data     = $form->safe();
        $todoList = $this->todoListService->create(
            userId:  (int) $request->getAttribute('user_id'),
            title:   (string) $data['title'],
            content: (string) ($data['content'] ?? ''),
        );

        return $this->responder->one($response, $todoList, 201);
    }
}
