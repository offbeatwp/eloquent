<?php

namespace OffbeatWP\Eloquent\Connection;

use PDO;
use PDOException;

final class WpPdo extends PDO
{
    protected WpConnection $wpConnection;
    protected bool $in_transaction;

    public function __construct($wpConnection)
    {
        $this->wpConnection = $wpConnection;
    }

    public function beginTransaction(): bool
    {
        if ($this->in_transaction) {
            throw new PDOException('Failed to start transaction. Transaction is already started.');
        }
        $this->in_transaction = true;
        return $this->wpConnection->unprepared('START TRANSACTION');
    }

    public function commit(): bool
    {
        if (!$this->in_transaction) {
            throw new PDOException('There is no active transaction to commit');
        }
        $this->in_transaction = false;
        return $this->wpConnection->unprepared('COMMIT');
    }

    public function rollBack(): bool
    {
        if (!$this->in_transaction) {
            throw new PDOException('There is no active transaction to rollback');
        }
        $this->in_transaction = false;
        return $this->wpConnection->unprepared('ROLLBACK');
    }

    public function inTransaction(): bool
    {
        return $this->in_transaction;
    }

    public function exec(string $statement): false|int
    {
        $result = $this->wpConnection->unprepared($statement);

        if ($result === false) {
            return false;
        }

        return (int)$result;
    }

    public function lastInsertId(?string $name = null): string
    {
        return (string)$this->wpConnection->getWpdb()->insert_id;
    }

    public function errorCode(): ?string
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
