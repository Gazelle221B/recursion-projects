<?php
declare(strict_types=1);

// Migration: creates the users table with an email_verified_at column.
// Usage: php Database/migrate.php

require_once __DIR__ . "/Database.php";

use EmailVerification\Database\Database;

$driver = Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === "sqlite") {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        email_verified_at TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
    SQL;
} else {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email_verified_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL;
}

Database::execute($sql);
echo "Migration complete: users table is ready.\n";
