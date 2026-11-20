<?php
declare(strict_types=1);

namespace SocialNetworking\Models;

use SocialNetworking\Database\Database;
use SocialNetworking\Helpers\Crypto;

/**
 * Direct messages. Bodies are stored only as AES-256-GCM ciphertext
 * (see Helpers\Crypto) and decrypted when a conversation is read.
 */
class Message
{
    public const MAX_BODY_LENGTH = 1000;

    public static function send(int $senderId, int $recipientId, string $body): int
    {
        $encrypted = Crypto::encrypt($body);
        Database::execute(
            "INSERT INTO messages (sender_id, recipient_id, body_ciphertext, iv, tag) VALUES (?, ?, ?, ?, ?)",
            [$senderId, $recipientId, $encrypted["ciphertext"], $encrypted["iv"], $encrypted["tag"]]
        );
        $id = Database::lastInsertId();
        if ($senderId !== $recipientId) {
            Notification::create($recipientId, $senderId, "dm", null, $id);
        }
        return $id;
    }

    /** Full conversation between two users, oldest first, bodies decrypted. */
    public static function thread(int $userA, int $userB, int $limit = 200): array
    {
        $rows = Database::fetchAll(
            "SELECT * FROM messages
             WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
             ORDER BY created_at ASC, id ASC LIMIT ?",
            [$userA, $userB, $userB, $userA, $limit]
        );
        foreach ($rows as &$row) {
            $row["body"] = Crypto::decrypt($row["body_ciphertext"], $row["iv"], $row["tag"]);
        }
        unset($row);
        return $rows;
    }

    /** Users the given user has exchanged messages with, most recent first. */
    public static function conversationPartners(int $userId): array
    {
        $rows = Database::fetchAll(
            "SELECT sender_id, recipient_id, body_ciphertext, iv, tag, created_at FROM messages
             WHERE sender_id = ? OR recipient_id = ? ORDER BY created_at DESC, id DESC",
            [$userId, $userId]
        );
        $partners = [];
        foreach ($rows as $row) {
            $other = (int) $row["sender_id"] === $userId ? (int) $row["recipient_id"] : (int) $row["sender_id"];
            if (!isset($partners[$other])) {
                $partners[$other] = [
                    "user" => User::findById($other),
                    "last_body" => Crypto::decrypt($row["body_ciphertext"], $row["iv"], $row["tag"]),
                    "last_at" => $row["created_at"],
                ];
            }
        }
        return $partners;
    }
}
