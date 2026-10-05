<?php

declare(strict_types=1);

namespace App\Middleware;

use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * DB-based rate limiter
 * Counts request per IP per time-window. If IP crosses limit, return 429 too many request
 * IP adres hash (SHA-256)
 */
readonly class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Connection       $connection,
        private int              $maxRequests,
        private int              $windowSeconds,
        private HttpErrorHandler $httpErrorHandler,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $ip     = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $ipHash = hash('sha256', $ip);
        $now    = new \DateTimeImmutable();
        $windowStart = $now->modify("-{$this->windowSeconds} seconds")->format('Y-m-d H:i:s');

        // Number of request in current window
        $row = $this->connection->fetchAssociative(
            'SELECT id, requests FROM rate_limits
             WHERE ip_hash = ? AND window_start >= ?
             ORDER BY window_start DESC
             LIMIT 1',
            [$ipHash, $windowStart],
        );

        if ($row === false) {
            // New request - new record
            $this->connection->executeStatement(
                'INSERT INTO rate_limits (ip_hash, requests, window_start) VALUES (?, 1, ?)',
                [$ipHash, $now->format('Y-m-d H:i:s')],
            );
        } elseif ((int) $row['requests'] >= $this->maxRequests) {
            // Limit exceeded
            return $this->tooManyRequests($request);
        } else {
            // Increment
            $this->connection->executeStatement(
                'UPDATE rate_limits SET requests = requests + 1 WHERE id = ?',
                [$row['id']],
            );
        }

        // Occasionally clean up old records (1% chance so it doesn't happen on every request)
        if (random_int(1, 100) === 1) {
            $this->cleanup($windowStart);
        }

        return $handler->handle($request);
    }

    private function tooManyRequests(Request $request): Response
    {
        return $this->httpErrorHandler->buildResponse($request, 429, 'Too many requests. Please slow down.')
            ->withHeader('Retry-After', (string) $this->windowSeconds);
    }

    private function cleanup(string $windowStart): void
    {
        $this->connection->executeStatement(
            'DELETE FROM rate_limits WHERE window_start < ?',
            [$windowStart],
        );
    }
}
