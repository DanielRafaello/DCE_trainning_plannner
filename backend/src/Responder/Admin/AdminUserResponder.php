<?php

declare(strict_types=1);

namespace App\Responder\Admin;

use App\Domain\User;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Account data only – never events, notes or to-do lists (FR-ADM-03).
 */
final class AdminUserResponder extends JsonResponder
{
    /** @param array{items: list<User>, total: int, page: int, per_page: int} $page */
    public function list(Response $response, array $page): Response
    {
        return $this->json($response, [
            'users'    => array_map($this->user(...), $page['items']),
            'total'    => $page['total'],
            'page'     => $page['page'],
            'per_page' => $page['per_page'],
        ]);
    }

    public function one(Response $response, User $user): Response
    {
        return $this->json($response, ['user' => $this->user($user)]);
    }

    /** @return array<string, mixed> */
    private function user(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role->value,
            'timezone'   => $user->timezone,
            'blocked_at' => $user->blockedAt,
            'created_at' => $user->createdAt,
        ];
    }
}
