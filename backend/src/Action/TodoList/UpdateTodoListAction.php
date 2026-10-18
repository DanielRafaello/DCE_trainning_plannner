<?php

declare(strict_types=1);

namespace App\Action\TodoList;

use App\Exceptions\Validation\ValidationException;
use App\Requests\TodoList\UpdateTodoListRequest;
use App\Responder\TodoList\TodoListResponder;
use App\Services\Contracts\TodoList\TodoListServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * PATCH /todo-lists/{id} — rename and/or replace the content (FR-TODO-03, FR-TODO-04).
 *
 * Auth:     JWT.
 * Body:     { "title"?, "content"? } — a missing (or null) field keeps the current value; "" clears the content
 *
 * Response: 200 { "todo_list": { "id", "title", "content", "created_at", "updated_at" } }
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           404 not.found.todo_list — also for another user's list.
 *           422 Validation failed. — title: todo_list.title_length; content: todo_list.content_too_long
 */
final readonly class UpdateTodoListAction
{
    public function __construct(
        private TodoListServiceInterface $todoListService,
        private TodoListResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $form = new UpdateTodoListRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data     = $form->safe();
        $todoList = $this->todoListService->update(
            userId:  (int) $request->getAttribute('user_id'),
            listId:  (int) $args['id'],
            title:   $data['title'] !== null ? (string) $data['title'] : null,
            content: $data['content'] !== null ? (string) $data['content'] : null,
        );

        return $this->responder->one($response, $todoList);
    }
}
