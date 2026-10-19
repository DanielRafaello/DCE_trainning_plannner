<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * The API is consumed by a separately-hosted Vue frontend (FRONTEND_URL), so
 * it needs real CORS headers, not same-origin defaults. Handles the OPTIONS
 * preflight directly (short-circuits before routing/auth) and stamps the
 * headers onto every other response.
 */
final readonly class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private string                   $allowedOrigin,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $this->withCorsHeaders($this->responseFactory->createResponse(204));
        }

        return $this->withCorsHeaders($handler->handle($request));
    }

    private function withCorsHeaders(Response $response): Response
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowedOrigin) # specific domain can read from this API
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS') # allowed methods
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization') # allowed headers - content-type for JSON, authorization for JWT
            ->withHeader('Access-Control-Allow-Credentials', 'true'); # allow cookies and auth headers to be sent - Access-Control-Allow-Origin has to be set for safetz
    }
}
