<?php

declare(strict_types=1);

namespace App\Responder\Auth;

use App\Domain\User;
use App\Infrastructure\AuthCookies;
use App\Responder\JsonResponder;
use App\Responder\User\MeResponder;
use Psr\Http\Message\ResponseInterface as Response;

/** Tokens go out as HttpOnly cookies (AuthCookies), the body carries only the user. */
final class AuthResponder extends JsonResponder
{
    public function __construct(
        private readonly MeResponder $users,
        private readonly AuthCookies $cookies,
    ) {
    }

    public function respond(Response $response, string $token, string $refreshToken, User $user, int $status): Response
    {
        $response = $this->cookies->withTokens($response, $token, $refreshToken);

        return $this->json($response, [
            'user' => $this->users->userData($user),
        ], $status);
    }
}
