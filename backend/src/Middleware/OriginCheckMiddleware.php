<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\generalExceptions\ForbiddenException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * CSRF protection for the cookie-based auth (AuthCookies).
 *
 * The browser attaches the auth cookies automatically, so a page on another origin could make the
 * user's browser send a state-changing request. SameSite=Lax/Strict already stops cross-SITE requests;
 * this check also stops cross-ORIGIN ones on the same site (e.g. another app on localhost:xxxx).
 *
 * Browsers always send Origin on POST/PUT/PATCH/DELETE. A request without Origin is not from a browser
 * (curl, Postman) and cannot carry a victim's cookies, so it is let through.
 */
final readonly class OriginCheckMiddleware implements MiddlewareInterface
{
    private const array SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private string $allowedOrigin,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');

        if (
            !in_array(strtoupper($request->getMethod()), self::SAFE_METHODS, true)
            && $origin !== ''
            && rtrim($origin, '/') !== rtrim($this->allowedOrigin, '/')
        ) {
            throw new ForbiddenException('security.origin_not_allowed');
        }

        return $handler->handle($request);
    }
}
