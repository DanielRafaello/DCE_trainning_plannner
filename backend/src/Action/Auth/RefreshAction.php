<?php

declare(strict_types=1);

namespace App\Action\Auth;

use App\Exceptions\generalExceptions\UnauthorizedException;
use App\Infrastructure\AuthCookies;
use App\Responder\Auth\AuthResponder;
use App\Services\Contracts\Auth\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /auth/refresh — exchange a refresh token for a new token pair.
 *
 * Auth:     none; the refresh_token cookie is the credential.
 * Body:     none.
 *
 * Steps:
 *   1. Read the refresh token from the refresh_token cookie.
 *   2. AuthService::refresh looks the token up by its hash, revokes it, and
 *      issues a new JWT access token plus a new refresh token (rotation).
 *      A token that was already revoked is treated as stolen: every refresh
 *      token of that account is revoked, so all devices must sign in again.
 *   3. Both new tokens are set as cookies.
 *
 * Response: 200 { "user": { "id", "name", "email", "role", "timezone", "created_at" } } + Set-Cookie
 * Errors:   401 missing.refresh.token — no refresh_token cookie.
 *           401 invalid.refresh.token — the token does not exist.
 *           401 security.token_compromised.relogin_required — the token was already used.
 *           401 invalid.expired.refresh.token — the token expired.
 *           401 not.found.user — the account no longer exists.
 *           403 auth.account_blocked — the account was blocked by an admin (FR-ADM-02).
 */
final readonly class RefreshAction
{
    public function __construct(
        private AuthServiceInterface $authService,
        private AuthResponder        $responder,
        private AuthCookies          $cookies,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $refreshToken = $this->cookies->refreshToken($request)
            ?? throw new UnauthorizedException('missing.refresh.token');

        $result = $this->authService->refresh($refreshToken);

        return $this->responder->respond($response, $result['token'], $result['refresh_token'], $result['user'], 200);
    }
}
