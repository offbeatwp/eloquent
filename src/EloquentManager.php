<?php

namespace OffbeatWP\Eloquent;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use OffbeatWP\Eloquent\Connection\WpConnection;

class EloquentManager
{
    public $booted = false;
    private $connection;

    public function boot()
    {
        $capsule = new Capsule();

        // Wordpress already makes an connection to the mysql database. This connection
        // utilizes the wpdb object to make the queries to the database/
        $capsule->addConnection([], 'wp');
        $this->connection = new WpConnection();
        $capsule->getDatabaseManager()->extend('wp', function () {
            return $this->connection;
        });

        $capsule->getDatabaseManager()->setDefaultConnection('wp');

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->booted = true;
    }

    public function getConnection(): WpConnection
    {
        return $this->connection;
    }

    public function __call($method, $arguments)
    {
        try {
            return self::callCapsuleMethod($method, $arguments);
        } catch(Exception $e) {

        }

        trigger_error('Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR);
    }

    public static function __callStatic($method, $arguments)
    {
        try {
            return self::callCapsuleMethod($method, $arguments);
        } catch(Exception $e) {

        }

        trigger_error('Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR);
    }

    public static function callCapsuleMethod($method, $arguments)
    {
        if (!offbeat('db')->isBooted()) {
            offbeat('db')->boot();
        }

        if (is_callable(Capsule::class, $method)) {
            return call_user_func_array([Capsule::class, $method], $arguments);
        }

        throw new Exception('No Capsule method');
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
