<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        return $response
            // Prevents the browser from MIME-sniffing the content type (e.g., executing JS from a .txt file).
            ->withHeader('X-Content-Type-Options', 'nosniff')
            // Controls how much referrer information is sent with requests.
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            // not neccessary in API backend - Disables access to sensitive browser features like camera, microphone, etc., by default.
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            // not neccessary in API backend - Ultimate API safeguard: Blocks the execution and downloading of any content from this address
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none';");

    }
}
