<?php

namespace OffbeatWP\Eloquent\Connection;

use PDO;
use PDOException;

class WpPdo extends PDO
{
    protected WpConnection $wpConnection;
    protected bool $in_transaction = false;

    public function __construct(WpConnection $wpConnection)
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
