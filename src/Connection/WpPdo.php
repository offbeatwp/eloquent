<?php

namespace OffbeatWP\Eloquent\Connection;

use PDO;
use PDOException;

class WpPdo extends PDO
{
    protected $wpConnection;
    protected $in_transaction;

    public function __construct($wpConnection)
    {
        $this->wpConnection = $wpConnection;
    }

    public function beginTransaction () {
        if($this->in_transaction){
            throw new PDOException("Failed to start transaction. Transaction is already started.");
        }
        $this->in_transaction=true;
        return $this->wpConnection->unprepared('START TRANSACTION');
    }

    public function commit () {
        if(!$this->in_transaction){
            throw new PDOException("There is no active transaction to commit");
        }
        $this->in_transaction=false;
        return $this->wpConnection->unprepared('COMMIT');
    }

    public function rollBack () {
        if(!$this->in_transaction){
            throw new PDOException("There is no active transaction to rollback");
        }
        $this->in_transaction=false;
        return $this->wpConnection->unprepared('ROLLBACK');
    }

    public function inTransaction () {
        return $this->in_transaction;
    }

    public function exec ($statement) {
        return $this->wpConnection->unprepared($statement);
    }

    public function lastInsertId($name=null)
    {
        return $this->wpConnection->getWpdb()->insert_id;
    }

    public function errorCode()
    {
        return null;
    }

    public function errorInfo()
    {
        return [
            $this->wpConnection->getWpdb()->last_error
        ];
    }
}
