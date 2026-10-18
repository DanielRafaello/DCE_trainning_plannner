<?php

namespace App\Services\Auth;

use App\Domain\Role;
use App\Domain\User;
use App\Exceptions\generalExceptions\UnauthorizedException;
use App\Exceptions\Validation\ValidationException;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\Auth\AuthServiceInterface;
use App\Services\JWT\JwtService;
use DateTimeImmutable;
use DateTimeInterface;
use DI\NotFoundException;
use Doctrine\DBAL\Connection;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly UserRepositoryInterface $users,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly JwtService $jwt,
        private readonly int $refreshTtlSeconds,
    ) {
    }

    /**
     * @return array{token: string, refresh_token: string, user: User}
     */
    public function register(string $name, string $email, string $password): array
    {
        if ($this->users->existsByEmail($email)) {
            throw new ValidationException(['email' => 'auth.register.email_taken']);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $user = $this->users->create($name, $email, $passwordHash, Role::USER);

        return [
          ...$this->issueTokensFor($user),
          'user' => $user,
        ];
    }

    public function login(string $email, string $password): array
    {
        $record = $this->users->findAuthRecordByEmail($email);

        if ($record === null || !password_verify($password, (string) $record['password_hash'])) {
            throw new UnauthorizedException('not.found.user.invalid.credentials');
        }

        $user = User::fromRow($record);

        return [
            ...$this->issueTokensFor($user),
            'user' => $user
        ];
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function refresh(string $refreshToken): array
    {
        $tokenHash = $this->hashToken($refreshToken);
        $record    = $this->refreshTokens->findByHash($tokenHash);

        if ($record === null) { //token does not exist
            throw new UnauthorizedException('invalid.refresh.token');
        }

        $userId = (int) $record['user_id'];

        // SECURITY INTERVENTION: The token exists but was previously invalidated!
        // Someone is trying to use it again (either an attacker or a client-side network error).
        if ($record['revoked_at'] !== null) {

            // invalidate all valid token of one user = logout from all devices
            $this->refreshTokens->revokeAllForUser($userId);
            throw new UnauthorizedException('security.token_compromised.relogin_required');
        }
        # token experied?
        if (new DateTimeImmutable($record['expires_at']) <= new DateTimeImmutable()) {
            throw new UnauthorizedException('invalid.expired.refresh.token');
        }

        $user    = $this->users->findById($userId) ?? throw new UnauthorizedException('not.found.user');

        # basic scenario - token is valid. Create new one and revoke old one.
        $this->refreshTokens->revoke($tokenHash);

        return [...$this->issueTokensFor($user), 'user' => $user];
    }

    public function logout(string $refreshToken): void
    {
       $this->refreshTokens->revoke($this->hashToken($refreshToken)); // tokens are hashed in database
    }

    public function currentUser(int $userId): User
    {
        return $this->users->findById($userId) ?? throw new NotFoundException('not.found.user');
    }

    /** @return array{token: string, refresh_token: string} */
    public function issueTokens(User $user): array
    {
        return $this->issueTokensFor($user);
    }

    /**
     * Issues a fresh access + refresh token pair for an already-resolved user —
     * for flows that authenticate a user outside login/register (e.g. accepting
     * an employee invite) without duplicating token-issuance logic.
     *
     * @return array{token: string, refresh_token: string}
     */
    private function issueTokensFor(User $user): array
    {
        return [
            'token'         => $this->jwt->issue($user),
            'refresh_token' => $this->issueRefreshToken($user->id),
        ];
    }

    private function issueRefreshToken(int $userId): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable()->modify("+{$this->refreshTtlSeconds} seconds")->format(DateTimeInterface::ATOM);

        $this->refreshTokens->create($userId, $this->hashToken($rawToken), $expiresAt);

        return $rawToken;
    }

    /** Refresh tokens are high-entropy random values, not user-chosen secrets — sha256 is fine (no bcrypt needed). */
    private function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}