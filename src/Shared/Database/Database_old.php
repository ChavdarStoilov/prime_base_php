<?php

namespace App\Shared\Database;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    public function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';
        $options = $config['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $host = $config['host'] ?? '';
            $user = $config['user'] ?? '';
            $db = $config['db'] ?? '';
            $password = $config['password'] ?? '';
            $port = $config['port'] ?? '3306';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
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
            throw new \RuntimeException('Database not initialized.');
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
        if ($limit) $sql .= " LIMIT " . (int)$limit;

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
        $setParts = [];

        // Използваме префикс 'u_' за SET параметрите
        foreach ($data as $column => $value) {
            $paramName = "u_" . str_replace('.', '_', $column);
            $setParts[] = "`$column` = :$paramName";
            $bindings[$paramName] = $value;
        }

        $whereSql = $this->buildWhere($where, $bindings);

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            $whereSql
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    private function buildWhere(array $where, array &$bindings): string
    {
        if (empty($where)) return '1=1';

        // Ако е прост масив ['id' => 5]
        if (!isset($where['AND']) && !isset($where['OR'])) {
            $conditions = [];
            foreach ($where as $column => $value) {
                $paramName = "w_" . str_replace('.', '_', $column) . "_" . count($bindings);
                $conditions[] = "`$column` = :$paramName";
                $bindings[$paramName] = $value;
            }
            return implode(' AND ', $conditions);
        }

        $groups = [];
        foreach (['AND', 'OR'] as $logic) {
            if (!isset($where[$logic])) continue;

            $conds = [];
            foreach ($where[$logic] as [$col, $op, $val]) {
                $paramName = "w_" . str_replace('.', '_', $col) . "_" . count($bindings);

                if (strtoupper($op) === 'IN' && is_array($val)) {
                    $inParts = [];
                    foreach ($val as $i => $v) {
                        $pName = $paramName . "_$i";
                        $inParts[] = ":$pName";
                        $bindings[$pName] = $v;
                    }
                    $conds[] = "`$col` IN (" . implode(',', $inParts) . ")";
                } else {
                    $conds[] = "`$col` $op :$paramName";
                    $bindings[$paramName] = $val;
                }
            }
            if ($conds) $groups[] = "(" . implode(" $logic ", $conds) . ")";
        }

        return implode(' AND ', $groups);
    }

    public function begin(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollback(): void { $this->pdo->rollBack(); }
}