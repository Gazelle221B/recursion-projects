<?php
declare(strict_types=1);

// Migration: creates the images table.
// Usage: php Database/migrate.php

require_once __DIR__ . "/Database.php";

use ImageHosting\Database\Database;

$driver = Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === "sqlite") {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        delete_slug TEXT NOT NULL UNIQUE,
        media_type TEXT NOT NULL,
        file_name TEXT NOT NULL,
        mime TEXT NOT NULL,
        ip_address TEXT NOT NULL DEFAULT '',
        view_count INTEGER NOT NULL DEFAULT 0,
        last_accessed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
    SQL;
} else {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS images (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        slug VARCHAR(16) NOT NULL UNIQUE,
        delete_slug VARCHAR(32) NOT NULL UNIQUE,
        media_type VARCHAR(8) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        mime VARCHAR(64) NOT NULL,
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        view_count BIGINT NOT NULL DEFAULT 0,
        last_accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL;
}

Database::execute($sql);
echo "Migration complete: images table is ready.\n";
