<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Transports the auth tokens in HttpOnly cookies, so JavaScript on the frontend never sees them
 * (an XSS cannot steal them).
 *
 * - access_token  – the JWT, sent with every request (Path=/), lives as long as the JWT itself.
 * - refresh_token – sent ONLY to /auth/* (Path=/auth), so it does not travel with ordinary requests.
 *   SameSite=Strict, it is never needed on a cross-site navigation.
 *
 * Secure: browsers accept Secure cookies on http://localhost, so it can stay on in development too.
 */
final readonly class AuthCookies
{
    public const string ACCESS_TOKEN  = 'access_token';
    public const string REFRESH_TOKEN = 'refresh_token';

    private const string REFRESH_PATH = '/auth';

    public function __construct(
        private int  $accessTtlSeconds,
        private int  $refreshTtlSeconds,
        private bool $secure,
    ) {
    }

    public function withTokens(Response $response, string $accessToken, string $refreshToken): Response
    {
        return $response
            ->withAddedHeader('Set-Cookie', $this->cookie(self::ACCESS_TOKEN, $accessToken, '/', $this->accessTtlSeconds, 'Lax'))
            ->withAddedHeader('Set-Cookie', $this->cookie(self::REFRESH_TOKEN, $refreshToken, self::REFRESH_PATH, $this->refreshTtlSeconds, 'Strict'));
    }

    /** Max-Age=0 with the same name + path makes the browser delete the cookie. */
    public function withoutTokens(Response $response): Response
    {
        return $response
            ->withAddedHeader('Set-Cookie', $this->cookie(self::ACCESS_TOKEN, '', '/', 0, 'Lax'))
            ->withAddedHeader('Set-Cookie', $this->cookie(self::REFRESH_TOKEN, '', self::REFRESH_PATH, 0, 'Strict'));
    }

    public function accessToken(Request $request): ?string
    {
        return $this->read($request, self::ACCESS_TOKEN);
    }

    public function refreshToken(Request $request): ?string
    {
        return $this->read($request, self::REFRESH_TOKEN);
    }

    private function read(Request $request, string $name): ?string
    {
        $value = $request->getCookieParams()[$name] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function cookie(string $name, string $value, string $path, int $maxAge, string $sameSite): string
    {
        return sprintf(
            '%s=%s; Path=%s; Max-Age=%d; HttpOnly; SameSite=%s%s',
            $name,
            rawurlencode($value),
            $path,
            $maxAge,
            $sameSite,
            $this->secure ? '; Secure' : '',
        );
    }
}
