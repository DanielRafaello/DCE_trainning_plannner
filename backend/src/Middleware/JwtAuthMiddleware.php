<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\generalExceptions\ForbiddenException;
use App\Exceptions\generalExceptions\UnauthorizedException;
use App\Infrastructure\AuthCookies;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\JWT\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Verifies the JWT from the HttpOnly access_token cookie (AuthCookies) and exposes the user
 * as request attributes (user_id, role) for downstream Actions.
 *
 * The user is loaded from the database on every request (one primary-key lookup), so that
 * a deleted or blocked account (FR-ADM-02) and a role change take effect immediately,
 * not only when the access token expires. The role attribute is the CURRENT role, not the token's.
 */
readonly class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwt,
        private UserRepositoryInterface $users,
        private AuthCookies $cookies,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $token = $this->cookies->accessToken($request)
            ?? throw new UnauthorizedException('Missing access token.');

        $claims = $this->jwt->verify($token);

        $user = $this->users->findById((int) $claims['sub'])
            ?? throw new UnauthorizedException('not.found.user'); // account deleted after the token was issued

        if ($user->isBlocked()) {
            throw new ForbiddenException('auth.account_blocked');
        }

        // add user id and role to the request
        $request = $request
            ->withAttribute('user_id', $user->id)
            ->withAttribute('role', $user->role->value);

        return $handler->handle($request);
    }
}
