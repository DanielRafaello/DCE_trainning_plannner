<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The password hash intentionally never lives on this entity — it only
 * ever exists inside the repository/service layer that authenticates it.
 */
final readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public Role $role,
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
            createdAt: $row['created_at'],
        );
    }
}
