<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Domain\Role;
use App\Exceptions\generalExceptions\ForbiddenException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Lets only administrators through. Must run after JwtAuthMiddleware, which
 * puts the token's role into the request attribute "role".
 *
 * The role comes from the access token, so a change of a user's role in the
 * database takes effect when their token is next issued (at most 1 hour).
 */
final class AdminOnlyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getAttribute('role') !== Role::ADMIN->value) {
            throw new ForbiddenException('auth.forbidden.admin_only');
        }

        return $handler->handle($request);
    }
}
