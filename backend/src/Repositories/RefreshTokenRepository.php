<?php

namespace App\Repositories;

use App\Repositories\Contracts\RefreshTokenRepositoryInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }


    public function create(int $userId, string $tokenHash, string $expiresAt): void
    {
        $this->connection->executeStatement(
            'INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
            [$userId, $tokenHash, $expiresAt],
        );
    }

    # revoke at means the token was revoke due to for example logout from the device by user
    public function findValidUserId(string $tokenHash): ?int
    {
        $userId = $this->connection->fetchOne(
            'SELECT user_id FROM refresh_tokens
             WHERE token_hash = ? AND revoked_at IS NULL AND expires_at > now()',
            [$tokenHash],
        );

        return $userId !== false ? (int) $userId : null;
    }

    public function revoke(string $tokenHash): void
    {
        $this->connection->executeStatement(
            'UPDATE refresh_tokens SET revoked_at = now() WHERE token_hash = ? AND revoked_at IS NULL',
            [$tokenHash],
        );
    }

    // Deletes everything that has already expired OR everything that has been cancelled (revoked)
    // TODO SETUP CRON
    public function deleteExpiredAndRevoked(): int
    {
        return (int) $this->connection->executeStatement(
            'DELETE FROM refresh_tokens 
         WHERE expires_at <= now() OR revoked_at IS NOT NULL'
        );
    }

    public function findByHash(string $tokenHash): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT user_id, revoked_at, expires_at FROM refresh_tokens WHERE token_hash = ?',
            [$tokenHash]
        );

        return $row !== false ? $row : null;
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->connection->executeStatement(
            'UPDATE refresh_tokens SET revoked_at = now() WHERE user_id = ? AND revoked_at IS NULL',
            [$userId]
        );
    }
}