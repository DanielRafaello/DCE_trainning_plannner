<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\generalExceptions\UnauthorizedException;
use App\Services\JWT\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Verifies the Authorization: Bearer <token> header and exposes the claims
 * as request attributes (user_id, company_id, role) for downstream Actions.
 */
class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwt,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');

        if (!str_starts_with($header, 'Bearer ')) {
            throw new UnauthorizedException('Missing bearer token.');
        }

        $claims = $this->jwt->verify(substr($header, 7));

        // add user id and role to the request
        $request = $request
            ->withAttribute('user_id', $claims['sub'])
            ->withAttribute('role', $claims['role']);

        return $handler->handle($request);
    }
}
