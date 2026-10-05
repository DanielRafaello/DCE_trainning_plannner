<?php

namespace App\Services\JWT;

use App\Domain\User;
use App\Exceptions\generalExceptions\UnauthorizedException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtService
{
    private const ALGO = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds,
    ) {
    }

    public function issue(User $user): string
    {
        $now = time();

        return JWT::encode([
            'sub'        => $user->id,
            'role'       => $user->role->value,
            'iat'        => $now,
            'exp'        => $now + $this->ttlSeconds,
        ], $this->secret, self::ALGO);
    }


    /** @return array{sub: int, company_id: int, role: string} */
    public function verify(string $token): array
    {
        try {
            $claims = (array) JWT::decode($token, new Key($this->secret, self::ALGO));
        } catch (\Exception) {
            throw new UnauthorizedException('Invalid or expired token.');
        }

        return [
            'sub'        => (int) $claims['sub'],
            'role'       => (string) $claims['role'],
        ];
    }
}