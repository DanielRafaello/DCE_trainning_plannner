<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Responder\User\MeResponder;
use App\Services\Contracts\Auth\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /me — the signed-in account.
 *
 * Auth:     JWT (JwtAuthMiddleware puts the token's user id into the
 *           request attribute "user_id").
 * Body:     none.
 *
 * Steps:
 *   1. AuthService::currentUser loads the user with that id.
 *
 * Response: 200 { "user": { "id", "name", "email", "role", "timezone", "created_at" } }
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           401 not.found.user — the account was deleted after the token was issued (JwtAuthMiddleware).
 *           403 auth.account_blocked
 */
final readonly class MeAction
{
    public function __construct(
        private AuthServiceInterface $authService,
        private MeResponder          $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $user    = $this->authService->currentUser((int) $request->getAttribute('user_id'));

        return $this->responder->respond($response, $user);
    }
}
