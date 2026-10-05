<?php

declare(strict_types=1);

namespace App\Responder\Auth;

use App\Domain\User;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

class AuthResponder extends JsonResponder
{
    public function response(Response $response, string $token, string $refreshToken, User $user, int $status): Response
    {
        return $this->json($response, [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role->value,
            ],
        ], $status);
    }
}