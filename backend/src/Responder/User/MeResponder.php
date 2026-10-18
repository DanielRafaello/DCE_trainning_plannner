<?php

namespace App\Responder\User;

use App\Domain\User;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class MeResponder extends JsonResponder
{
    public function respond(Response $response, User $user): Response
    {
        return $this->json($response, [
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role->value,
                'timezone'   => $user->timezone,
                'created_at' => $user->createdAt,
            ],
        ]);
    }

    public function deleted(Response $response): Response
    {
        return $response->withStatus(204);
    }
}