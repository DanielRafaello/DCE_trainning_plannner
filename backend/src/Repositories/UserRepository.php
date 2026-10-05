<?php

namespace App\Repositories;

use App\Domain\Role;
use App\Domain\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findById(int $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT  id, email, name, role, created_at FROM users WHERE id = :id',
            ['id' => $id],
        );
        return $row !== false ? User::fromRow($row) : null;
    }

    public function existsByEmail(string $email): bool
    {
        return $this->connection->fetchOne(
            'SELECT 1 FROM users WHERE email = :email',
            ['email' => $email],
        ) !== false;
    }

    /** @return array<string, mixed>|null */
    public function findAuthRecordByEmail(string $email): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, name, role, password_hash, created_at FROM users WHERE email = ?',
            [$email],
        );

        return $row !== false ? $row : null;    }

    public function create(string $name, string $email, string $passwordHash, Role $role): User
    {
        $row = $this->connection->fetchAssociative(
            'INSERT INTO users ( name, email, password_hash, role)
             VALUES (?, ?, ?, ?)
             RETURNING id, email, name, role, created_at',
            [$name, $email, $passwordHash, $role->value],
        );

        return User::fromRow($row);
    }
}