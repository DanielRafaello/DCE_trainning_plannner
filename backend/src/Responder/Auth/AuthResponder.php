<?php

declare(strict_types=1);

namespace App\Responder\Auth;

use App\Domain\User;
use App\Responder\JsonResponder;
use App\Responder\User\MeResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class AuthResponder extends JsonResponder
{
    public function __construct(
        private readonly MeResponder $users,
    ) {
    }

    public function respond(Response $response, string $token, string $refreshToken, User $user, int $status): Response
    {
        return $this->json($response, [
            'token'         => $token,
            'refresh_token' => $refreshToken,
            'user'          => $this->users->userData($user),
        ], $status);
    }
}
