<?php
declare(strict_types=1);

namespace SocialNetworking\Models;

use SocialNetworking\Database\Database;

/** User accounts, profiles and the follow graph. */
class User
{
    public static function create(string $username, string $email, string $passwordHash, string $displayName): int
    {
        Database::execute(
            "INSERT INTO users (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)",
            [$username, $email, $passwordHash, $displayName]
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

    public static function updateProfile(int $id, array $fields): void
    {
        Database::execute(
            "UPDATE users SET display_name = ?, age = ?, residence = ?, hobbies = ?, bio = ?, photo_path = ? WHERE id = ?",
            [
                $fields["display_name"],
                $fields["age"],
                $fields["residence"],
                $fields["hobbies"],
                $fields["bio"],
                $fields["photo_path"],
                $id,
            ]
        );
    }

    public static function follow(int $followerId, int $followeeId): bool
    {
        if ($followerId === $followeeId || self::isFollowing($followerId, $followeeId)) {
            return false;
        }
        Database::execute("INSERT INTO follows (follower_id, followee_id) VALUES (?, ?)", [$followerId, $followeeId]);
        Notification::create($followeeId, $followerId, "follow", null, null);
        return true;
    }

    public static function unfollow(int $followerId, int $followeeId): void
    {
        Database::execute("DELETE FROM follows WHERE follower_id = ? AND followee_id = ?", [$followerId, $followeeId]);
    }

    public static function isFollowing(int $followerId, int $followeeId): bool
    {
        return Database::fetchOne(
            "SELECT follower_id FROM follows WHERE follower_id = ? AND followee_id = ?",
            [$followerId, $followeeId]
        ) !== null;
    }

    public static function followerCount(int $id): int
    {
        $row = Database::fetchOne("SELECT COUNT(*) AS c FROM follows WHERE followee_id = ?", [$id]);
        return (int) ($row["c"] ?? 0);
    }

    public static function followingCount(int $id): int
    {
        $row = Database::fetchOne("SELECT COUNT(*) AS c FROM follows WHERE follower_id = ?", [$id]);
        return (int) ($row["c"] ?? 0);
    }

    /** All users except the given one (for starting new DM conversations). */
    public static function allExcept(int $id, int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT id, username, display_name, photo_path FROM users WHERE id != ? ORDER BY username LIMIT ?",
            [$id, $limit]
        );
    }
}
