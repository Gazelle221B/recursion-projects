<?php
declare(strict_types=1);

namespace SocialNetworking\Models;

use SocialNetworking\Database\Database;

/**
 * Posts: top-level posts and nested replies share one table
 * (reply_to_id IS NULL means a top-level post). Posts are never
 * edited; the author can delete them. A post whose publish_at lies
 * in the future is stored with status 'scheduled' and flipped to
 * 'published' by publish_scheduled.php (cron).
 */
class Post
{
    public const MAX_BODY_LENGTH = 255;
    public const PAGE_SIZE = 20;

    public static function create(
        int $userId,
        string $body,
        ?int $replyToId,
        ?string $mediaPath,
        ?string $mediaType,
        ?string $publishAt
    ): int {
        $status = ($publishAt !== null && strtotime($publishAt) > time()) ? "scheduled" : "published";
        Database::execute(
            "INSERT INTO posts (user_id, body, reply_to_id, media_path, media_type, status, publish_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$userId, $body, $replyToId, $mediaPath, $mediaType, $status, $publishAt]
        );
        $id = Database::lastInsertId();
        if ($replyToId !== null) {
            $parent = self::findById($replyToId);
            if ($parent !== null && (int) $parent["user_id"] !== $userId) {
                Notification::create((int) $parent["user_id"], $userId, "reply", $replyToId, null);
            }
        }
        return $id;
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT p.*, u.username, u.display_name, u.photo_path AS author_photo
             FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?",
            [$id]
        );
    }

    /** Deletes a post, all of its descendant replies, and their likes/notifications. */
    public static function deleteWithDescendants(int $id): void
    {
        $ids = [$id];
        $frontier = [$id];
        while ($frontier !== []) {
            $placeholders = implode(",", array_fill(0, count($frontier), "?"));
            $rows = Database::fetchAll("SELECT id FROM posts WHERE reply_to_id IN ({$placeholders})", $frontier);
            $frontier = array_map(static fn (array $r): int => (int) $r["id"], $rows);
            $ids = [...$ids, ...$frontier];
        }
        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        Database::execute("DELETE FROM likes WHERE post_id IN ({$placeholders})", $ids);
        Database::execute("DELETE FROM notifications WHERE post_id IN ({$placeholders})", $ids);
        Database::execute("DELETE FROM posts WHERE id IN ({$placeholders})", $ids);
    }

    /** Toggles a like. Returns true when the post is now liked by the user. */
    public static function toggleLike(int $userId, int $postId): bool
    {
        $existing = Database::fetchOne("SELECT user_id FROM likes WHERE user_id = ? AND post_id = ?", [$userId, $postId]);
        if ($existing !== null) {
            Database::execute("DELETE FROM likes WHERE user_id = ? AND post_id = ?", [$userId, $postId]);
            return false;
        }
        Database::execute("INSERT INTO likes (user_id, post_id) VALUES (?, ?)", [$userId, $postId]);
        $post = self::findById($postId);
        if ($post !== null && (int) $post["user_id"] !== $userId) {
            Notification::create((int) $post["user_id"], $userId, "like", $postId, null);
        }
        return true;
    }

    public static function likeCount(int $postId): int
    {
        $row = Database::fetchOne("SELECT COUNT(*) AS c FROM likes WHERE post_id = ?", [$postId]);
        return (int) ($row["c"] ?? 0);
    }

    public static function likedBy(int $userId, int $postId): bool
    {
        return Database::fetchOne("SELECT user_id FROM likes WHERE user_id = ? AND post_id = ?", [$userId, $postId]) !== null;
    }

    public static function replyCount(int $postId): int
    {
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM posts WHERE reply_to_id = ? AND status = 'published'",
            [$postId]
        );
        return (int) ($row["c"] ?? 0);
    }

    /** Direct published replies of a post, oldest first (thread order). */
    public static function replies(int $postId): array
    {
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name, u.photo_path AS author_photo
             FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.reply_to_id = ? AND p.status = 'published' ORDER BY p.created_at ASC, p.id ASC",
            [$postId]
        );
    }

    /** A user's published top-level posts, newest first. */
    public static function byUser(int $userId, int $limit = self::PAGE_SIZE): array
    {
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name, u.photo_path AS author_photo
             FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.user_id = ? AND p.reply_to_id IS NULL AND p.status = 'published'
             ORDER BY p.created_at DESC, p.id DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * One timeline page (20 posts) for the infinite scroll feed.
     * $tab = 'following': posts by followed users and the user themself.
     * $tab = 'trending': today's posts ordered by like count.
     */
    public static function timeline(int $userId, string $tab, int $offset): array
    {
        if ($tab === "trending") {
            $today = date("Y-m-d 00:00:00");
            return Database::fetchAll(
                "SELECT p.*, u.username, u.display_name, u.photo_path AS author_photo,
                        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count
                 FROM posts p JOIN users u ON u.id = p.user_id
                 WHERE p.reply_to_id IS NULL AND p.status = 'published' AND p.created_at >= ?
                 ORDER BY like_count DESC, p.created_at DESC, p.id DESC
                 LIMIT ? OFFSET ?",
                [$today, self::PAGE_SIZE, $offset]
            );
        }
        return Database::fetchAll(
            "SELECT p.*, u.username, u.display_name, u.photo_path AS author_photo,
                    (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count
             FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.reply_to_id IS NULL AND p.status = 'published'
               AND (p.user_id = ? OR p.user_id IN (SELECT followee_id FROM follows WHERE follower_id = ?))
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT ? OFFSET ?",
            [$userId, $userId, self::PAGE_SIZE, $offset]
        );
    }

    /** Publishes every scheduled post whose time has come. Returns the count. */
    public static function publishDue(): int
    {
        return Database::execute(
            "UPDATE posts SET status = 'published' WHERE status = 'scheduled' AND publish_at <= ?",
            [date("Y-m-d H:i:s")]
        );
    }
}
