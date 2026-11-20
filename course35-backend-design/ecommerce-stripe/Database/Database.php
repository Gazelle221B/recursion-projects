<?php
declare(strict_types=1);

namespace EcommerceStripe\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO database wrapper. DSN comes from .env — MySQL in production,
 * SQLite for local tests.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $env = self::env();
            $dsn = $env["DATABASE_DSN"] ?? "mysql:host=127.0.0.1;dbname=ecommerce_stripe;charset=utf8mb4";
            try {
                self::$pdo = new PDO($dsn, $env["DATABASE_USER"] ?? "root", $env["DATABASE_PASSWORD"] ?? "", [
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

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): int
    {
        return (int) self::connection()->lastInsertId();
    }

    public static function env(?string $key = null): mixed
    {
        static $env = null;
        if ($env === null) {
            $env = [];
            $path = dirname(__DIR__) . "/.env";
            if (is_file($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    if (str_starts_with(trim($line), "#") || !str_contains($line, "=")) {
                        continue;
                    }
                    [$k, $v] = explode("=", $line, 2);
                    $env[trim($k)] = trim(trim($v), "\"'");
                }
            }
        }
        return $key === null ? $env : ($env[$key] ?? null);
    }
}
