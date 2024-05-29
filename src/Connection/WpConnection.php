<?php

namespace OffbeatWP\Eloquent\Connection;

use Illuminate\Database\MySqlConnection;
use wpdb;

final class WpConnection extends MySqlConnection
{
    private wpdb $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        parent::__construct(
            new WpPdo($this),
            DB_NAME ?? null,
            $wpdb->prefix
        );
    }

    public function getWpdb(): wpdb
    {
        return $this->wpdb;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $query = $this->applyBindings($query, $bindings);

            return $this->getResults($query);
        });
    }

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        $results = $this->select($query, $bindings, $useReadPdo);

        foreach($results as $result) {
            yield $result;
        }
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $this->exec($this->applyBindings($query, $bindings));

            return true;
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            return $this->exec($this->applyBindings($query, $bindings));
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            return (bool) $this->exec($query);
        });
    }

    public function getResults($query) {
        return $this->getWpdb()->get_results($query);
    }

    public function exec($query) {
        return $this->getWpdb()->query($query);
    }

    /** Bind values to their parameters in the given query. */
    public function applyBindings(string $query, array $bindings) : string
    {
        if (!$bindings) {
            return $query;
        }

        $bindings = $this->prepareBindings($bindings);

        $wpBindings = [];

        $bindingIndex = 0;
        $wpQuery = preg_replace_callback('/\?|:[a-zA-Z0-9_-]+/', function ($match) use ($bindings, &$bindingIndex, &$wpBindings) {
            if (str_starts_with($match[0], ':')) {
                $bindingKey = str_replace(':', '', $match[0]);
            } else {
                $bindingKey = $bindingIndex;
                $bindingIndex++;
            }

            $value = $bindings[$bindingKey] ?? null;

            $wpBindings[] = $value;

            if (is_int($value)) {
                return '%d';
            }

            if (is_float($value)) {
                return '%f';
            }

            return '%s';
        }, $query);

        return $this->getWpdb()->prepare($wpQuery, $wpBindings);
    }
}
