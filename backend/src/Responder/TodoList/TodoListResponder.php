<?php

declare(strict_types=1);

namespace App\Responder\TodoList;

use App\Domain\TodoList;
use App\Domain\TodoListSummary;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class TodoListResponder extends JsonResponder
{
    /** @param list<TodoListSummary> $summaries */
    public function list(Response $response, array $summaries): Response
    {
        return $this->json($response, [
            'todo_lists' => array_map(static fn (TodoListSummary $s): array => [
                'id'         => $s->id,
                'title'      => $s->title,
                'updated_at' => $s->updatedAt,
            ], $summaries),
        ]);
    }

    public function one(Response $response, TodoList $todoList, int $status = 200): Response
    {
        return $this->json($response, [
            'todo_list' => [
                'id'         => $todoList->id,
                'title'      => $todoList->title,
                'content'    => $todoList->content,
                'created_at' => $todoList->createdAt,
                'updated_at' => $todoList->updatedAt,
            ],
        ], $status);
    }

    public function deleted(Response $response): Response
    {
        return $response->withStatus(204);
    }
}
