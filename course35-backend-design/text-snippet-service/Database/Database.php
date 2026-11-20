<?php
declare(strict_types=1);

namespace SnippetService\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO-based database wrapper (the MySQLWrapper role in the course).
 * The DSN is read from the .env file so the same code runs against
 * MySQL in production and SQLite for local development/testing.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $env = self::loadEnv();
            $dsn = $env["DATABASE_DSN"] ?? "mysql:host=127.0.0.1;dbname=snippet_service;charset=utf8mb4";
            $user = $env["DATABASE_USER"] ?? "root";
            $password = $env["DATABASE_PASSWORD"] ?? "";
            try {
                self::$pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException("Database connection failed.", 0, $e);
            }
        }
        return self::$pdo;
    }

    /** Run a prepared query and return all rows. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Run a prepared query and return the first row, or null. */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Run a prepared write query and return the affected row count. */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::connection()->lastInsertId();
    }

    private static function loadEnv(): array
    {
        $env = [];
        $path = dirname(__DIR__) . "/.env";
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), "#") || !str_contains($line, "=")) {
                    continue;
                }
                [$key, $value] = explode("=", $line, 2);
                $env[trim($key)] = trim(trim($value), "\"'");
            }
        }
        return $env;
    }
}
