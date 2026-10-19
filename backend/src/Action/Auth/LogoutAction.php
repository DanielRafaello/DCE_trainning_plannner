<?php

declare(strict_types=1);

namespace App\Action\Auth;

use App\Infrastructure\AuthCookies;
use App\Responder\Auth\LogoutResponder;
use App\Services\Contracts\Auth\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /auth/logout — end one session.
 *
 * Auth:     none; the refresh_token cookie identifies the session.
 * Body:     none.
 *
 * Steps:
 *   1. If a refresh_token cookie is present, AuthService::logout revokes that
 *      refresh token. An unknown or already revoked token is ignored, so the
 *      call is safe to repeat. Other sessions of the account are untouched.
 *   2. Both auth cookies are deleted, so the access token is gone from this
 *      browser too (the JWT itself stays valid until it expires).
 *
 * Response: 200 { "logged_out": true } + Set-Cookie (deleting both cookies)
 */
final class LogoutAction
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
        private readonly LogoutResponder $responder,
        private readonly AuthCookies $cookies,
    ) {
    }
    public function __invoke(Request $request, Response $response): Response
    {
        $refreshToken = $this->cookies->refreshToken($request);

        if ($refreshToken !== null) {
            $this->authService->logout($refreshToken);
        }

        return $this->responder->loggedOut($response);
    }

}
