<?php

namespace App\Repositories;

use App\Domain\Event;
use App\Domain\EventStatus;
use App\Repositories\Contracts\EventRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class EventRepository implements EventRepositoryInterface
{
    private const string COLUMNS = 'id, user_id, category_id, subcategory_id, title, description,
                                    start_at, end_at, status, created_at, updated_at';

    // ISO 8601 with offset – PostgreSQL converts it to UTC for timestamptz
    private const string DATE_FORMAT = 'Y-m-d H:i:sP';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findForUser(int $id, int $userId): ?Event
    {
        $row = $this->connection->fetchAssociative(
            'SELECT ' . self::COLUMNS . ' FROM events WHERE id = ? AND user_id = ?',
            [$id, $userId],
        );

        return $row !== false ? Event::fromRow($row) : null;
    }

    /**
     * @param list<EventStatus> $statuses
     * @return list<Event>
     */
    public function findInRange(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        array $statuses = [],
    ): array {
        // start_at > from - 24 h is implied by the 24 h limit, but lets the (user_id, start_at) index do the work
        $sql = 'SELECT ' . self::COLUMNS . ' FROM events
                WHERE user_id = :user
                  AND start_at < :to AND end_at > :from
                  AND start_at > CAST(:from AS TIMESTAMPTZ) - INTERVAL \'24 hours\'';
        $params = [
            'user' => $userId,
            'from' => $from->format(self::DATE_FORMAT),
            'to'   => $to->format(self::DATE_FORMAT),
        ];
        $types = [];

        if ($categoryId !== null) {
            $sql .= ' AND category_id = :category';
            $params['category'] = $categoryId;
        }
        if ($subcategoryId !== null) {
            $sql .= ' AND subcategory_id = :subcategory';
            $params['subcategory'] = $subcategoryId;
        }
        if ($statuses !== []) {
            $sql .= ' AND status IN (:statuses)';
            $params['statuses'] = array_map(static fn (EventStatus $s) => $s->value, $statuses);
            $types['statuses'] = ArrayParameterType::STRING;
        }

        $rows = $this->connection->fetchAllAssociative($sql . ' ORDER BY start_at, id', $params, $types);

        return array_map(Event::fromRow(...), $rows);
    }

    public function create(
        int $userId,
        int $categoryId,
        ?int $subcategoryId,
        string $title,
        ?string $description,
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
        EventStatus $status,
    ): Event {
        $row = $this->connection->fetchAssociative(
            'INSERT INTO events (user_id, category_id, subcategory_id, title, description, start_at, end_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             RETURNING ' . self::COLUMNS,
            [
                $userId, $categoryId, $subcategoryId, $title, $description,
                $startAt->format(self::DATE_FORMAT), $endAt->format(self::DATE_FORMAT), $status->value,
            ],
        );

        return Event::fromRow($row);
    }

    public function update(
        int $id,
        int $userId,
        int $categoryId,
        ?int $subcategoryId,
        string $title,
        ?string $description,
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
        EventStatus $status,
    ): ?Event {
        $row = $this->connection->fetchAssociative(
            'UPDATE events
             SET category_id = ?, subcategory_id = ?, title = ?, description = ?,
                 start_at = ?, end_at = ?, status = ?, updated_at = now()
             WHERE id = ? AND user_id = ?
             RETURNING ' . self::COLUMNS,
            [
                $categoryId, $subcategoryId, $title, $description,
                $startAt->format(self::DATE_FORMAT), $endAt->format(self::DATE_FORMAT), $status->value,
                $id, $userId,
            ],
        );

        return $row !== false ? Event::fromRow($row) : null;
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->connection->executeStatement(
            'DELETE FROM events WHERE id = ? AND user_id = ?',
            [$id, $userId],
        ) > 0;
    }
}
