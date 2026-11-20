<?php
declare(strict_types=1);

// Migration: creates the SNS tables (users, follows, posts, likes,
// messages, notifications). Usage: php Database/migrate.php

require_once __DIR__ . "/Database.php";

use SocialNetworking\Database\Database;

$driver = Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === "sqlite") {
    $statements = [
        <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            display_name TEXT NOT NULL,
            age INTEGER NULL,
            residence TEXT NULL,
            hobbies TEXT NULL,
            bio TEXT NULL,
            photo_path TEXT NULL,
            is_influencer INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS follows (
            follower_id INTEGER NOT NULL,
            followee_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (follower_id, followee_id)
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            body TEXT NOT NULL,
            reply_to_id INTEGER NULL,
            media_path TEXT NULL,
            media_type TEXT NULL,
            status TEXT NOT NULL DEFAULT 'published',
            publish_at TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS likes (
            user_id INTEGER NOT NULL,
            post_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, post_id)
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            recipient_id INTEGER NOT NULL,
            body_ciphertext TEXT NOT NULL,
            iv TEXT NOT NULL,
            tag TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            actor_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            post_id INTEGER NULL,
            message_id INTEGER NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        "CREATE INDEX IF NOT EXISTS idx_posts_user ON posts (user_id)",
        "CREATE INDEX IF NOT EXISTS idx_posts_reply ON posts (reply_to_id)",
        "CREATE INDEX IF NOT EXISTS idx_posts_status ON posts (status, publish_at)",
        "CREATE INDEX IF NOT EXISTS idx_likes_post ON likes (post_id)",
        "CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications (user_id, is_read)",
        "CREATE INDEX IF NOT EXISTS idx_messages_pair ON messages (sender_id, recipient_id)",
    ];
} else {
    $statements = [
        <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            age INT NULL,
            residence VARCHAR(255) NULL,
            hobbies TEXT NULL,
            bio TEXT NULL,
            photo_path VARCHAR(255) NULL,
            is_influencer TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS follows (
            follower_id BIGINT NOT NULL,
            followee_id BIGINT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (follower_id, followee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS posts (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            body VARCHAR(255) NOT NULL,
            reply_to_id BIGINT NULL,
            media_path VARCHAR(255) NULL,
            media_type VARCHAR(20) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            publish_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_posts_user (user_id),
            INDEX idx_posts_reply (reply_to_id),
            INDEX idx_posts_status (status, publish_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS likes (
            user_id BIGINT NOT NULL,
            post_id BIGINT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, post_id),
            INDEX idx_likes_post (post_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS messages (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            sender_id BIGINT NOT NULL,
            recipient_id BIGINT NOT NULL,
            body_ciphertext TEXT NOT NULL,
            iv VARCHAR(64) NOT NULL,
            tag VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_messages_pair (sender_id, recipient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NOT NULL,
            actor_id BIGINT NOT NULL,
            type VARCHAR(20) NOT NULL,
            post_id BIGINT NULL,
            message_id BIGINT NULL,
            is_read TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user (user_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
    ];
}

foreach ($statements as $sql) {
    Database::execute($sql);
}
echo "Migration complete: users, follows, posts, likes, messages, notifications tables are ready.\n";
