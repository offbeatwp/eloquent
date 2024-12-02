<?php

namespace OffbeatWP\Eloquent\Connection;

use PDO;
use PDOException;

final class WpPdo extends PDO
{
    private readonly WpConnection $wpConnection;
    private bool $inTransation = false;

    public function __construct(WpConnection $wpConnection)
    {
        $this->wpConnection = $wpConnection;
    }

    public function beginTransaction(): bool
    {
        if ($this->inTransation) {
            throw new PDOException('Failed to start transaction. Transaction is already started.');
        }

        $this->inTransation = true;
        return $this->wpConnection->unprepared('START TRANSACTION');
    }

    public function commit(): bool
    {
        if (!$this->inTransation) {
            throw new PDOException('There is no active transaction to commit');
        }

        $this->inTransation = false;
        return $this->wpConnection->unprepared('COMMIT');
    }

    public function rollBack(): bool
    {
        if (!$this->inTransation) {
            throw new PDOException('There is no active transaction to rollback');
        }

        $this->inTransation = false;
        return $this->wpConnection->unprepared('ROLLBACK');
    }

    public function inTransaction(): bool
    {
        return $this->inTransation;
    }

    public function exec($statement): false|int
    {
        return $this->wpConnection->unprepared($statement);
    }

    public function lastInsertId($name = null): false|string
    {
        return $this->wpConnection->getWpdb()->insert_id;
    }

    public function errorCode(): null|string
    {
        return null;
    }

    public function errorInfo(): array
    {
        return [
            $this->wpConnection->getWpdb()->last_error
        ];
    }
}
