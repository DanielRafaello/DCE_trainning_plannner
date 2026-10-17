<?php

declare(strict_types=1);

namespace App\Services\Contracts\Admin;

use App\Domain\Role;
use App\Domain\User;
use App\Exceptions\generalExceptions\ConflictException;
use App\Exceptions\generalExceptions\NotFoundException;

/**
 * User administration (FR-ADM-*). Only reachable through routes behind AdminOnlyMiddleware.
 *
 * Privacy (FR-ADM-03): an admin manages accounts only – this service never returns
 * anyone's events, notes or to-do lists, and there is no "log in as user".
 */
interface AdminUserServiceInterface
{
    /**
     * Users ordered by id, paginated (FR-ADM-01).
     * $search is a case-insensitive substring of name or email; empty/null = everyone.
     * $perPage is capped at 100.
     *
     * @return array{items: list<User>, total: int, page: int, per_page: int}
     */
    public function list(?string $search, int $page = 1, int $perPage = 20): array;

    /**
     * Changes the role of a user (FR-ADM-02).
     *
     * @param int $adminId the admin doing the change
     *
     * @throws NotFoundException
     * @throws ConflictException the admin tries to demote themselves, or it would leave the system without an admin
     */
    public function changeRole(int $adminId, int $userId, Role $role): User;

    /**
     * Blocks or unblocks a user (FR-ADM-02). Blocking also revokes all the user's refresh tokens,
     * so they are logged out once their access token expires; login and refresh then fail
     * (enforced in AuthServiceInterface). Blocking an already blocked user keeps the original date.
     *
     * @throws NotFoundException
     * @throws ConflictException the admin tries to block themselves
     */
    public function setBlocked(int $adminId, int $userId, bool $blocked): User;
}
