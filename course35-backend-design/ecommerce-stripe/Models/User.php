<?php
declare(strict_types=1);

namespace EcommerceStripe\Models;

use EcommerceStripe\Database\Database;

/** Customer/admin accounts. Each user owns a public wishlist token. */
class User
{
    public static function create(string $username, string $email, string $passwordHash, bool $isAdmin = false): int
    {
        Database::execute(
            "INSERT INTO users (username, email, password_hash, is_admin, wishlist_token) VALUES (?, ?, ?, ?, ?)",
            [$username, $email, $passwordHash, $isAdmin ? 1 : 0, bin2hex(random_bytes(16))]
        );
        return Database::lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public static function findByWishlistToken(string $token): ?array
    {
        return Database::fetchOne("SELECT * FROM users WHERE wishlist_token = ?", [$token]);
    }
}
