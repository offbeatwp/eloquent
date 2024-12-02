<?php

namespace OffbeatWP\Eloquent\Connection;

use Generator;
use Illuminate\Database\MySqlConnection;
use wpdb;

class WpConnection extends MySqlConnection
{
    private readonly wpdb $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        parent::__construct(new WpPdo($this), DB_NAME ?? null, $wpdb->prefix);
    }

    public function getWpdb(): wpdb
    {
        return $this->wpdb;
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     */
    public function select($query, $bindings = [], $useReadPdo = true): array
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
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     */
    public function cursor($query, $bindings = [], $useReadPdo = true): Generator
    {
        foreach ($this->select($query, $bindings, $useReadPdo) as $result) {
            yield $result;
        }
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     */
    public function statement($query, $bindings = []): bool
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
     * @param string $query
     * @param array $bindings
     */
    public function affectingStatement($query, $bindings = []): int
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
     * @param string $query
     */
    public function unprepared($query): bool
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            return (bool)$this->exec($query);
        });
    }

    public function getResults(string $query): array
    {
        return $this->getWpdb()->get_results($query);
    }

    public function exec(string $query): bool|int
    {
        return $this->getWpdb()->query($query);
    }

    /**
     * Bind values to their parameters in the given query.
     */
    public function applyBindings(string $query, array $bindings): string
    {
        if (!$bindings) {
            return $query;
        }

        $bindings = $this->prepareBindings($bindings);

        $wpBindings = [];

        $bindingIndex = 0;
        $wpQuery = preg_replace_callback('/\?|:[a-zA-Z0-9_-]+/', static function ($match) use ($bindings, &$bindingIndex, &$wpBindings) {
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
