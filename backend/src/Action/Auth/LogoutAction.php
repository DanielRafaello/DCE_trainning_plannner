<?php

declare(strict_types=1);

namespace App\Action\Auth;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Auth\RefreshRequest;
use App\Responder\Auth\LogoutResponder;
use App\Services\Contracts\Auth\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /auth/logout — end one session.
 *
 * Auth:     none; the refresh token in the body identifies the session.
 * Body:     { "refreshToken" }
 *
 * Steps:
 *   1. Validate the body (RefreshRequest): refreshToken is present.
 *   2. AuthService::logout revokes that refresh token. An unknown or already
 *      revoked token is ignored, so the call is safe to repeat. Other sessions
 *      of the account are untouched, and the JWT access token keeps working
 *      until it expires.
 *
 * Response: 200 { "logged_out": true }
 * Errors:   422 Validation failed.
 */
final class LogoutAction
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
        private readonly LogoutResponder $responder,
    ) {
    }
    public function __invoke(Request $request, Response $response): Response
    {
        $form = new RefreshRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $this->authService->logout($form->safe()['refreshToken']);

        return $this->responder->loggedOut($response);
    }

}