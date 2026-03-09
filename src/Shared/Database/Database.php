<?php

namespace App\Shared\Database;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    public function __construct(array $config)
    {
        try {
            $host = $config['host'] ?? '';
            $user = $config['user'] ?? '';
            $port = $config['port'] ?? '3306';
            $db = $config['db'] ?? '';
            $password = $config['password'] ?? '';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Задължително за сигурност
            ];

            $this->pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function init(array $config): void
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
    }

    public static function get(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Database not initialized.');
        }
        return self::$instance;
    }

    public function select(string $from, array $where = [], $select = '*', string $order = '', ?int $limit = null): array
    {
        $bindings = [];
        $whereSql = $this->buildWhere($where, $bindings);
        $columns = is_array($select) ? implode(', ', $select) : $select;

        $sql = "SELECT $columns FROM $from WHERE $whereSql";
        if ($order) $sql .= " $order";
        if ($limit !== null) $sql .= " LIMIT " . (int)$limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): int|string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $bindings = [];
        $set = [];

        foreach ($data as $column => $value) {
            // Уникален префикс за SET частта, за да няма дублиране с WHERE
            $param = 'u_' . str_replace('.', '_', $column);
            $set[] = "`$column` = :$param";
            $bindings[$param] = $value;
        }

        $whereSql = $this->buildWhere($where, $bindings);
        $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $whereSql";

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
        if (empty($where)) {
            throw new \InvalidArgumentException(
                "Delete operation must have conditions to prevent accidental full table truncation."
            );
        }

        $bindings = [];
        $whereSql = $this->buildWhere($where, $bindings);

        if (empty($whereSql)) {
            throw new \RuntimeException("Failed to build a valid WHERE clause for the delete operation.");
        }

        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $whereSql);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    private function buildWhere(array $where, array &$bindings): string
    {
        if (empty($where)) return '1=1';

        // Проверка дали масивът е прост: ['uuid' => 'value']
        if (!isset($where['AND']) && !isset($where['OR'])) {
            $conditions = [];
            foreach ($where as $column => $value) {
                // Превръщаме r.token в r_token за име на параметър
                $safeCol = str_replace('.', '_', $column);
                $param = 'w_' . $safeCol . '_' . count($bindings);
                $conditions[] = "$column = :$param";
                $bindings[$param] = $value;
            }
            return implode(' AND ', $conditions);
        }

        $groups = [];
        foreach (['AND', 'OR'] as $logic) {
            if (!isset($where[$logic])) continue;

            $conds = [];
            foreach ($where[$logic] as $item) {
                [$column, $operator, $value] = $item;
                $safeCol = str_replace('.', '_', $column);
                $param = 'w_' . $safeCol . '_' . count($bindings);

                if (strtoupper($operator) === 'IN' && is_array($value)) {
                    $inParts = [];
                    foreach ($value as $i => $v) {
                        $pName = $param . '_i' . $i;
                        $inParts[] = ":$pName";
                        $bindings[$pName] = $v;
                    }
                    $conds[] = "$column IN (" . implode(',', $inParts) . ")";
                } else {
                    $conds[] = "$column $operator :$param";
                    $bindings[$param] = $value;
                }
            }
            if ($conds) $groups[] = "(" . implode(" $logic ", $conds) . ")";
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
