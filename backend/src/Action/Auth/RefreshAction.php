<?php

declare(strict_types=1);

namespace App\Action\Auth;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Auth\RefreshRequest;
use App\Responder\Auth\AuthResponder;
use App\Services\Contracts\Auth\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /auth/refresh — exchange a refresh token for a new token pair.
 *
 * Auth:     none; the refresh token in the body is the credential.
 * Body:     { "refreshToken" }
 *
 * Steps:
 *   1. Validate the body (RefreshRequest): refreshToken is present.
 *   2. AuthService::refresh looks the token up by its hash, revokes it, and
 *      issues a new JWT access token plus a new refresh token (rotation).
 *      A token that was already revoked is treated as stolen: every refresh
 *      token of that account is revoked, so all devices must sign in again.
 *
 * Response: 200 { "token", "refresh_token", "user": { "id", "name", "email", "role" } }
 * Errors:   422 Validation failed.
 *           401 invalid.refresh.token — the token does not exist.
 *           401 security.token_compromised.relogin_required — the token was already used.
 *           401 invalid.expired.refresh.token — the token expired.
 *           401 not.found.user — the account no longer exists.
 */
final readonly class RefreshAction
{
    public function __construct(
        private AuthServiceInterface $authService,
        private AuthResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new RefreshRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $result = $this->authService->refresh($form->safe()['refreshToken']);

        return $this->responder->respond($response, $result['token'], $result['refresh_token'], $result['user'], 200);
    }
}
