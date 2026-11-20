<?php
declare(strict_types=1);

namespace SocialNetworking\Models;

use SocialNetworking\Database\Database;

/**
 * Notifications for likes, replies, follows and DMs. Opening a
 * notification (GET /notifications/{id}) marks it as read and
 * redirects to where the activity happened.
 */
class Notification
{
    public static function create(int $userId, int $actorId, string $type, ?int $postId, ?int $messageId): void
    {
        Database::execute(
            "INSERT INTO notifications (user_id, actor_id, type, post_id, message_id) VALUES (?, ?, ?, ?, ?)",
            [$userId, $actorId, $type, $postId, $messageId]
        );
    }

    public static function forUser(int $userId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT n.*, u.username AS actor_username, u.display_name AS actor_name
             FROM notifications n JOIN users u ON u.id = n.actor_id
             WHERE n.user_id = ? ORDER BY n.created_at DESC, n.id DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM notifications WHERE id = ?", [$id]);
    }

    public static function unreadCount(int $userId): int
    {
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        return (int) ($row["c"] ?? 0);
    }

    public static function markRead(int $id): void
    {
        Database::execute("UPDATE notifications SET is_read = 1 WHERE id = ?", [$id]);
    }

    /** Marks DM notifications from one sender as read (conversation opened). */
    public static function markDmFromRead(int $userId, int $actorId): void
    {
        Database::execute(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND actor_id = ? AND type = 'dm'",
            [$userId, $actorId]
        );
    }

    /** Where a notification click should land. */
    public static function targetUrl(array $notification): string
    {
        $actor = User::findById((int) $notification["actor_id"]);
        return match ($notification["type"]) {
            "follow" => "/profile/" . ($actor["username"] ?? ""),
            "like", "reply" => "/post/" . (int) ($notification["post_id"] ?? 0),
            "dm" => "/messages/" . (int) $notification["actor_id"],
            default => "/",
        };
    }

    public static function describe(array $notification): string
    {
        $name = $notification["actor_name"] ?? "Someone";
        return match ($notification["type"]) {
            "like" => "{$name} liked your post.",
            "reply" => "{$name} replied to your post.",
            "follow" => "{$name} started following you.",
            "dm" => "{$name} sent you a message.",
            default => "{$name} interacted with you.",
        };
    }
}
