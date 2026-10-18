<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The password hash intentionally never lives on this entity — it only
 * ever exists inside the repository/service layer that authenticates it.
 */
final readonly class User
{
    /** Name length in characters – matches VARCHAR(100) on users.name */
    public const int NAME_MIN_LENGTH = 2;
    public const int NAME_MAX_LENGTH = 100;

    /** matches VARCHAR(255) on users.email */
    public const int EMAIL_MAX_LENGTH = 255;

    /** FR-AUTH-01 */
    public const int PASSWORD_MIN_LENGTH = 8;

    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public Role $role,
        public string $timezone,
        public ?string $blockedAt,
        public string $createdAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            email:     $row['email'],
            name:      $row['name'],
            role:      Role::from($row['role']),
            timezone:  $row['timezone'],
            blockedAt: $row['blocked_at'],
            createdAt: $row['created_at'],
        );
    }

    public function isBlocked(): bool
    {
        return $this->blockedAt !== null;
    }
}
