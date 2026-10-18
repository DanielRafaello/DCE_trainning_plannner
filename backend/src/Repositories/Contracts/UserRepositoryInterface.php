<?php

namespace App\Repositories\Contracts;

use App\Domain\Role;
use App\Domain\User;

/**
 * Emails are compared case-insensitively everywhere (FR-AUTH-01, unique index on lower(email)).
 */
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    /** @param int|null $exceptUserId ignore this user – for "is the new email free?" when changing it */
    public function existsByEmail(string $email, ?int $exceptUserId = null): bool;

    /**
     * Raw row incl. password_hash — for authentication only. Never expose
     * the hash outside the service method that verifies it.
     *
     * @return array<string, mixed>|null
     */
    public function findAuthRecordByEmail(string $email): ?array;

    /** Password hash of a user – for "confirm with your current password" (FR-AUTH-09, FR-AUTH-10). */
    public function findPasswordHash(int $id): ?string;

    public function create(string $name, string $email, string $passwordHash, Role $role): User;

    /** FR-AUTH-08. Returns null when the user does not exist. */
    public function updateProfile(int $id, string $name, string $email, string $timezone): ?User;

    /** FR-AUTH-09 */
    public function updatePassword(int $id, string $passwordHash): void;

    /** FR-AUTH-10 – all user data is removed by ON DELETE CASCADE. Returns false when nothing was deleted. */
    public function delete(int $id): bool;

    // ─── Administration (FR-ADM-*) ────────────────────────────────

    /**
     * Users ordered by id, optionally filtered by a case-insensitive substring of name or email.
     *
     * @return list<User>
     */
    public function paginate(?string $search, int $limit, int $offset): array;

    /** Total for paginate() with the same search. */
    public function count(?string $search): int;

    /** Number of users with the role – e.g. "is this the last admin?" (FR-AUTH-10, FR-ADM-*). */
    public function countByRole(Role $role): int;

    public function updateRole(int $id, Role $role): ?User;

    /** Blocks (blocked_at = now()) or unblocks (blocked_at = NULL) a user. */
    public function setBlocked(int $id, bool $blocked): ?User;
}
