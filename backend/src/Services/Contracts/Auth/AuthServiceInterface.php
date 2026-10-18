<?php

declare(strict_types=1);

namespace App\Services\Contracts\Auth;

use App\Domain\Company;
use App\Domain\User;

interface AuthServiceInterface
{
    /**
     * Creates a new company plus its owner user, and issues an access + refresh token pair.
     *
     * @return array{token: string, refresh_token: string, user: User}
     */
    public function register( string $name, string $email, string $password): array;

    /**
     * Verifies credentials and issues an access + refresh token pair.
     *
     * @return array{token: string, refresh_token: string, user: User}
     */
    public function login(string $email, string $password): array;

    /**
     * Exchanges a valid, non-revoked refresh token for a new access token.
     * Rotates the refresh token (the old one is revoked) so a stolen-but-unused
     * token can't be replayed once the legitimate client has refreshed.
     *
     * @return array{token: string, refresh_token: string, user: User}
     */
    public function refresh(string $refreshToken): array;

    /** Revokes a refresh token. Idempotent — logging out twice isn't an error. */
    public function logout(string $refreshToken): void;

    public function currentUser(int $userId): User;

    /**
     * Issues a fresh access + refresh token pair for an already authenticated user –
     * for flows outside login/register, e.g. after a password change (FR-AUTH-09).
     *
     * @return array{token: string, refresh_token: string}
     */
    public function issueTokens(User $user): array;

}
