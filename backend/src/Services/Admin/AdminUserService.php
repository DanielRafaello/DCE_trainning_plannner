<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domain\Role;
use App\Domain\User;
use App\Exceptions\generalExceptions\ConflictException;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Repositories\Contracts\RefreshTokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\Admin\AdminUserServiceInterface;
use Doctrine\DBAL\Connection;

final readonly class AdminUserService implements AdminUserServiceInterface
{
    public const int MAX_PER_PAGE = 100;

    public function __construct(
        private Connection $connection,
        private UserRepositoryInterface $users,
        private RefreshTokenRepositoryInterface $refreshTokens,
    ) {
    }

    /** @return array{items: list<User>, total: int, page: int, per_page: int} */
    public function list(?string $search, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $search  = $search !== null && trim($search) !== '' ? trim($search) : null;
        $page    = max(1, $page);
        $perPage = min(max(1, $perPage), self::MAX_PER_PAGE);

        return [
            'items'    => $this->users->paginate($search, $perPage, ($page - 1) * $perPage),
            'total'    => $this->users->count($search),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    public function changeRole(int $adminId, int $userId, Role $role): User
    {
        if ($adminId === $userId && $role !== Role::ADMIN) {
            throw new ConflictException('admin.cannot_demote_self');
        }

        $user = $this->users->findById($userId) ?? throw new NotFoundException('not.found.user');

        // the token role of the caller may be outdated, so check the database as well
        if ($user->role === Role::ADMIN && $role !== Role::ADMIN && $this->users->countByRole(Role::ADMIN) <= 1) {
            throw new ConflictException('admin.last_admin');
        }

        return $this->users->updateRole($userId, $role) ?? throw new NotFoundException('not.found.user');
    }

    public function setBlocked(int $adminId, int $userId, bool $blocked): User
    {
        if ($adminId === $userId && $blocked) {
            throw new ConflictException('admin.cannot_block_self');
        }

        return $this->connection->transactional(function () use ($userId, $blocked): User {
            // COALESCE in the repository keeps the original blocked_at of an already blocked user
            $user = $this->users->setBlocked($userId, $blocked) ?? throw new NotFoundException('not.found.user');

            if ($blocked) {
                $this->refreshTokens->revokeAllForUser($userId);
            }

            return $user;
        });
    }
}
