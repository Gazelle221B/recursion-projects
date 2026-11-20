<?php
declare(strict_types=1);

// Migration: creates the posts table (self-referencing threads/replies).
// Usage: php Database/migrate.php

require_once __DIR__ . "/Database.php";

use Imageboard\Database\Database;

$driver = Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === "sqlite") {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS posts (
        post_id INTEGER PRIMARY KEY AUTOINCREMENT,
        reply_to_id INTEGER NULL REFERENCES posts(post_id),
        subject TEXT NULL,
        content TEXT NOT NULL,
        image_path TEXT NULL,
        thumb_path TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
    SQL;
} else {
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS posts (
        post_id INT PRIMARY KEY AUTO_INCREMENT,
        reply_to_id INT NULL,
        subject VARCHAR(255) NULL,
        content TEXT NOT NULL,
        image_path VARCHAR(255) NULL,
        thumb_path VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (reply_to_id) REFERENCES posts(post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    SQL;
}

Database::execute($sql);
echo "Migration complete: posts table is ready.\n";
