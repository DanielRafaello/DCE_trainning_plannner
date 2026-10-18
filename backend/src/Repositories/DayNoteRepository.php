<?php

namespace App\Repositories;

use App\Domain\DayNote;
use App\Repositories\Contracts\DayNoteRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class DayNoteRepository implements DayNoteRepositoryInterface
{
    private const string COLUMNS = 'id, user_id, date, content, created_at, updated_at';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByDate(int $userId, string $date): ?DayNote
    {
        $row = $this->connection->fetchAssociative(
            'SELECT ' . self::COLUMNS . ' FROM day_notes WHERE user_id = ? AND date = ?',
            [$userId, $date],
        );

        return $row !== false ? DayNote::fromRow($row) : null;
    }

    /** @return list<DayNote> */
    public function findInRange(int $userId, string $from, string $to): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT ' . self::COLUMNS . ' FROM day_notes
             WHERE user_id = ? AND date BETWEEN ? AND ?
             ORDER BY date',
            [$userId, $from, $to],
        );

        return array_map(DayNote::fromRow(...), $rows);
    }

    public function upsert(int $userId, string $date, string $content): DayNote
    {
        $row = $this->connection->fetchAssociative(
            'INSERT INTO day_notes (user_id, date, content) VALUES (?, ?, ?)
             ON CONFLICT (user_id, date) DO UPDATE SET content = EXCLUDED.content, updated_at = now()
             RETURNING ' . self::COLUMNS,
            [$userId, $date, $content],
        );

        return DayNote::fromRow($row);
    }

    public function delete(int $userId, string $date): bool
    {
        return $this->connection->executeStatement(
            'DELETE FROM day_notes WHERE user_id = ? AND date = ?',
            [$userId, $date],
        ) > 0;
    }
}
