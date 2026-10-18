<?php

namespace App\Repositories\Contracts;

interface RefreshTokenRepositoryInterface
{
    public function create(int $userId, string $tokenHash, string $expiresAt): void;

    /**
     * @deprecated use findByHash
     * A non-revoked, non-expired token's owning user id, or null.
     */
    public function findValidUserId(string $tokenHash): ?int;

    public function revoke(string $tokenHash): void;
    public function deleteExpiredAndRevoked(): int;

    public function findByHash(string $tokenHash): ?array;

    /**
     * delete all token for specific user
     * USE IF USER TRIES TO USE OLD REFRESH TOKEN EXPIRED ALREADY -> hacker maybe tried to use refresh token of someone else to get new tokens
     * USE IN CASE OF CHANGE OF PASSWORD
     */
    public function revokeAllForUser(int $userId): void;

}