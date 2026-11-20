<?php
declare(strict_types=1);

// Migration: creates the e-commerce tables (users, products, orders,
// order_items, reviews, favorites, checkout_sessions, stripe_sync_queue).
// Usage: php Database/migrate.php

require_once __DIR__ . "/Database.php";

use EcommerceStripe\Database\Database;

$driver = Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === "sqlite") {
    $statements = [
        <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            is_admin INTEGER NOT NULL DEFAULT 0,
            wishlist_token TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            stripe_product_id TEXT NOT NULL UNIQUE,
            stripe_price_id TEXT NULL,
            payment_link TEXT NULL,
            title TEXT NOT NULL DEFAULT '',
            description TEXT NULL,
            slug TEXT NULL UNIQUE,
            type TEXT NOT NULL DEFAULT 'physical',
            category TEXT NULL,
            tags TEXT NULL,
            release_date TEXT NULL,
            creator TEXT NULL,
            seo_tags TEXT NULL,
            price_cents INTEGER NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT 'jpy',
            inventory INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'draft',
            digital_file TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            guest_email TEXT NULL,
            stripe_checkout_session_id TEXT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            total_cents INTEGER NOT NULL DEFAULT 0,
            currency TEXT NOT NULL DEFAULT 'jpy',
            tracking_number TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NULL,
            title TEXT NOT NULL,
            price_cents INTEGER NOT NULL,
            quantity INTEGER NOT NULL
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            rating INTEGER NOT NULL,
            comment TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (product_id, user_id)
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS favorites (
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, product_id)
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS checkout_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL UNIQUE,
            order_id INTEGER NOT NULL,
            payload TEXT NULL,
            status TEXT NOT NULL DEFAULT 'open',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS stripe_sync_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT NOT NULL,
            stripe_product_id TEXT NOT NULL,
            payload TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL,
        "CREATE INDEX IF NOT EXISTS idx_products_status ON products (status, category)",
        "CREATE INDEX IF NOT EXISTS idx_orders_user ON orders (user_id)",
        "CREATE INDEX IF NOT EXISTS idx_orders_session ON orders (stripe_checkout_session_id)",
        "CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items (order_id)",
    ];
} else {
    $statements = [
        <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_admin TINYINT NOT NULL DEFAULT 0,
            wishlist_token VARCHAR(64) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS products (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            stripe_product_id VARCHAR(255) NOT NULL UNIQUE,
            stripe_price_id VARCHAR(255) NULL,
            payment_link VARCHAR(255) NULL,
            title VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT NULL,
            slug VARCHAR(255) NULL UNIQUE,
            type VARCHAR(20) NOT NULL DEFAULT 'physical',
            category VARCHAR(100) NULL,
            tags VARCHAR(255) NULL,
            release_date DATE NULL,
            creator VARCHAR(255) NULL,
            seo_tags VARCHAR(255) NULL,
            price_cents INT NOT NULL DEFAULT 0,
            currency VARCHAR(10) NOT NULL DEFAULT 'jpy',
            inventory INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            digital_file VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_products_status (status, category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS orders (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id BIGINT NULL,
            guest_email VARCHAR(255) NULL,
            stripe_checkout_session_id VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            total_cents INT NOT NULL DEFAULT 0,
            currency VARCHAR(10) NOT NULL DEFAULT 'jpy',
            tracking_number VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_orders_user (user_id),
            INDEX idx_orders_session (stripe_checkout_session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            order_id BIGINT NOT NULL,
            product_id BIGINT NULL,
            title VARCHAR(255) NOT NULL,
            price_cents INT NOT NULL,
            quantity INT NOT NULL,
            INDEX idx_order_items_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS reviews (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            product_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            rating INT NOT NULL,
            comment TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_reviews_product_user (product_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS favorites (
            user_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS checkout_sessions (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL UNIQUE,
            order_id BIGINT NOT NULL,
            payload TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS stripe_sync_queue (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            action VARCHAR(50) NOT NULL,
            stripe_product_id VARCHAR(255) NOT NULL,
            payload TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL,
    ];
}

foreach ($statements as $sql) {
    Database::execute($sql);
}
echo "Migration complete: users, products, orders, order_items, reviews, favorites, checkout_sessions, stripe_sync_queue tables are ready.\n";
