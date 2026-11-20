<?php
declare(strict_types=1);

// Migration: creates the snippets table.
// Usage: php Database/migrate.php
// The SQL below is written for MySQL; when the DSN points to SQLite the
// script swaps in the SQLite dialect so local smoke tests work unchanged.

require_once __DIR__ . "/Database.php";

use SnippetService\Database\Database;

$driver = Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === "sqlite") {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS snippets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        content TEXT NOT NULL,
        language TEXT NOT NULL DEFAULT 'plaintext',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at TEXT NULL
    )
    SQL;
} else {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS snippets (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        slug VARCHAR(16) NOT NULL UNIQUE,
        content MEDIUMTEXT NOT NULL,
        language VARCHAR(32) NOT NULL DEFAULT 'plaintext',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL;
}

Database::execute($sql);
echo "Migration complete: snippets table is ready.\n";
