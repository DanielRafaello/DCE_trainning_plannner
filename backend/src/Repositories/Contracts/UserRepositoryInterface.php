<?php

namespace App\Repositories\Contracts;

use App\Domain\Role;
use App\Domain\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function existsByEmail(string $email): bool;

    /**
     * Raw row incl. password_hash — for authentication only. Never expose
     * the hash outside the service method that verifies it.
     *
     * @return array<string, mixed>|null
     */
    public function findAuthRecordByEmail(string $email): ?array;

    public function create(string $name, string $email, string $passwordHash, Role $role): User;
}