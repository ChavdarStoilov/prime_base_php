<?php

namespace App\Shared\Database;

use PDO;


class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * @param array $config
     */
    private function __construct(array $config)
    {
        $host = $config['host'] ?? '';
        $user = $config['user'] ?? '';
        $port = $config['port'] ?? '';
        $db = $config['db'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $password = $config['password'] ?? '';
        $options = $config['options'] ?? [];

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

        try {
            $this->pdo = new PDO($dsn, $user, $password, $options);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * @param array $config
     * @return void
     */
    public static function init(array $config): void
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
    }

    public static function get(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not initialized.');
        }

        return self::$instance;
    }

    /**
     * @param string $fromWithJoins
     * @param array $where
     * @param array|string $select
     * @param string $orderGroup
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function select(
        string       $fromWithJoins,
        array        $where = [],
        array|string $select = '*',
        string       $orderGroup = '',
        ?int         $limit = null,
        ?int         $offset = null
    ): array
    {
        $bindings = [];
        $whereSql = $this->buildWhere($where, $bindings);

        $sql = sprintf(
            'SELECT %s FROM %s',
            is_array($select) ? implode(', ', $select) : $select,
            $fromWithJoins
        );

        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        if ($orderGroup !== '') {
            $sql .= ' ' . $orderGroup;
        }

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        if ($offset !== null) {
            $sql .= ' OFFSET ' . (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetchAll();
    }


    /**
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int
     */
    public function update(string $table, array $data, array $where): int
    {
        $bindings = [];
        $set = [];

        foreach ($data as $col => $val) {
            $param = 's_' . $col;
            $set[] = "{$col} = :{$param}";
            $bindings[$param] = $val;
        }

        $whereSql = $this->buildWhere($where, $bindings);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $whereSql
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    /**
     * @param string $table
     * @param array $where
     * @return int
     */
    public function delete(string $table, array $where): int
    {
        $bindings = [];
        $whereSql = $this->buildWhere($where, $bindings);

        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $whereSql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    /**
     * @param string $fromWithJoins
     * @param array $where
     * @return int
     */
    public function count(string $fromWithJoins, array $where = []): int
    {
        $bindings = [];
        $whereSql = $this->buildWhere($where, $bindings);

        $sql = 'SELECT COUNT(*) as total FROM ' . $fromWithJoins;

        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param string $fromWithJoins
     * @param array $where
     * @return bool
     */
    public function exists(string $fromWithJoins, array $where = []): bool
    {
        return $this->count($fromWithJoins, $where) > 0;
    }

    /**
     * @param array $where
     * @param array $bindings
     * @return string
     */
    private function buildWhere(array $where, array &$bindings): string
    {
        if (empty($where)) {
            return '1=1';
        }

        $groups = [];

        if (!isset($where['AND']) && !isset($where['OR'])) {
            if (count($where) === 1) {
                $column = key($where);
                $value = current($where);
                $param = 'w_' . count($bindings);
                $bindings[$param] = $value;
                return "{$column} = :{$param}";
            }

            $conditions = [];
            foreach ($where as $column => $value) {
                $param = 'w_' . count($bindings);
                $bindings[$param] = $value;
                $conditions[] = "{$column} = :{$param}";
            }
            return '(' . implode(' AND ', $conditions) . ')';
        }

        foreach (['AND', 'OR'] as $logic) {
            if (!isset($where[$logic])) {
                continue;
            }

            $conditions = [];

            foreach ($where[$logic] as [$column, $operator, $value]) {
                $operator = strtoupper($operator);

                if ($operator === 'IN') {
                    $placeholders = [];
                    foreach ($value as $v) {
                        $param = 'w_' . count($bindings);
                        $placeholders[] = ':' . $param;
                        $bindings[$param] = $v;
                    }
                    $conditions[] = "{$column} IN (" . implode(',', $placeholders) . ")";
                } else {
                    $param = 'w_' . count($bindings);
                    $conditions[] = "{$column} {$operator} :{$param}";
                    $bindings[$param] = $value;
                }
            }

            if ($conditions) {
                $groups[] = '(' . implode(" {$logic} ", $conditions) . ')';
            }
        }

        return implode(' AND ', $groups);
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}
