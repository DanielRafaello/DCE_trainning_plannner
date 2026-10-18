<?php

namespace App\Repositories;

use App\Domain\Role;
use App\Domain\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class UserRepository implements UserRepositoryInterface
{
    private const string COLUMNS = 'id, email, name, role, timezone, blocked_at, created_at';

    // NULL search = no filter; otherwise substring match on name or email
    private const string SEARCH_CONDITION = '(CAST(:search AS TEXT) IS NULL OR name ILIKE :pattern OR email ILIKE :pattern)';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findById(int $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id',
            ['id' => $id],
        );
        return $row !== false ? User::fromRow($row) : null;
    }

    public function existsByEmail(string $email, ?int $exceptUserId = null): bool
    {
        return $this->connection->fetchOne(
            'SELECT 1 FROM users WHERE lower(email) = lower(:email) AND id IS DISTINCT FROM :except',
            ['email' => $email, 'except' => $exceptUserId],
        ) !== false;
    }

    /** @return array<string, mixed>|null */
    public function findAuthRecordByEmail(string $email): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT ' . self::COLUMNS . ', password_hash FROM users WHERE lower(email) = lower(?)',
            [$email],
        );

        return $row !== false ? $row : null;
    }

    public function findPasswordHash(int $id): ?string
    {
        $hash = $this->connection->fetchOne('SELECT password_hash FROM users WHERE id = ?', [$id]);

        return $hash !== false ? $hash : null;
    }

    public function create(string $name, string $email, string $passwordHash, Role $role): User
    {
        $row = $this->connection->fetchAssociative(
            'INSERT INTO users ( name, email, password_hash, role)
             VALUES (?, ?, ?, ?)
             RETURNING ' . self::COLUMNS,
            [$name, $email, $passwordHash, $role->value],
        );

        return User::fromRow($row);
    }

    public function updateProfile(int $id, string $name, string $email, string $timezone): ?User
    {
        $row = $this->connection->fetchAssociative(
            'UPDATE users SET name = ?, email = ?, timezone = ?, updated_at = now()
             WHERE id = ?
             RETURNING ' . self::COLUMNS,
            [$name, $email, $timezone, $id],
        );

        return $row !== false ? User::fromRow($row) : null;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->connection->executeStatement(
            'UPDATE users SET password_hash = ?, updated_at = now() WHERE id = ?',
            [$passwordHash, $id],
        );
    }

    public function delete(int $id): bool
    {
        return $this->connection->executeStatement('DELETE FROM users WHERE id = ?', [$id]) > 0;
    }

    /** @return list<User> */
    public function paginate(?string $search, int $limit, int $offset): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT ' . self::COLUMNS . ' FROM users
             WHERE ' . self::SEARCH_CONDITION . '
             ORDER BY id
             LIMIT :limit OFFSET :offset',
            [...$this->searchParams($search), 'limit' => $limit, 'offset' => $offset],
        );

        return array_map(User::fromRow(...), $rows);
    }

    public function count(?string $search): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT count(*) FROM users WHERE ' . self::SEARCH_CONDITION,
            $this->searchParams($search),
        );
    }

    public function updateRole(int $id, Role $role): ?User
    {
        $row = $this->connection->fetchAssociative(
            'UPDATE users SET role = ?, updated_at = now() WHERE id = ? RETURNING ' . self::COLUMNS,
            [$role->value, $id],
        );

        return $row !== false ? User::fromRow($row) : null;
    }

    public function setBlocked(int $id, bool $blocked): ?User
    {
        $row = $this->connection->fetchAssociative(
            'UPDATE users SET blocked_at = ' . ($blocked ? 'COALESCE(blocked_at, now())' : 'NULL') . ', updated_at = now()
             WHERE id = ?
             RETURNING ' . self::COLUMNS,
            [$id],
        );

        return $row !== false ? User::fromRow($row) : null;
    }

    /** @return array{search: ?string, pattern: ?string} */
    private function searchParams(?string $search): array
    {
        $search = $search !== null && trim($search) !== '' ? trim($search) : null;

        return [
            'search'  => $search,
            // escape LIKE wildcards so "50%" searches for the literal text
            'pattern' => $search !== null ? '%' . addcslashes($search, '%_\\') . '%' : null,
        ];
    }
}
