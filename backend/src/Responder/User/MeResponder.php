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
            'user' => $this->userData($user),
        ]);
    }

    public function deleted(Response $response): Response
    {
        return $response->withStatus(204);
    }

    /**
     * The signed-in user as JSON – the one shape used by /me and by every auth response
     * (AuthResponder), so the frontend gets the timezone right after login.
     *
     * @return array<string, mixed>
     */
    public function userData(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role->value,
            'timezone'   => $user->timezone,
            'created_at' => $user->createdAt,
        ];
    }
}
