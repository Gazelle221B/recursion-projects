<?php
declare(strict_types=1);

namespace Imageboard\Database;

use Imageboard\Models\Post;

class PostDAOImpl implements PostDAO
{
    public function create(Post $postData): bool
    {
        if ($postData->postId !== null && $this->getById($postData->postId) !== null) {
            throw new \Exception("Post with ID {$postData->postId} already exists.");
        }
        return $this->createOrUpdate($postData);
    }

    public function getById(int $id): ?Post
    {
        $row = Database::fetchOne("SELECT * FROM posts WHERE post_id = ?", [$id]);
        return $row === null ? null : Post::fromRow($row);
    }

    public function update(Post $postData): bool
    {
        if ($postData->postId === null || $this->getById($postData->postId) === null) {
            throw new \Exception("Post does not exist; cannot update.");
        }
        return $this->createOrUpdate($postData);
    }

    public function delete(int $id): bool
    {
        return Database::execute("DELETE FROM posts WHERE post_id = ?", [$id]) > 0;
    }

    public function createOrUpdate(Post $postData): bool
    {
        if ($postData->postId === null) {
            Database::execute(
                "INSERT INTO posts (reply_to_id, subject, content, image_path, thumb_path) VALUES (?, ?, ?, ?, ?)",
                [$postData->replyToId, $postData->subject, $postData->content, $postData->imagePath, $postData->thumbPath]
            );
            $postData->postId = Database::lastInsertId();
            return true;
        }
        return Database::execute(
            "UPDATE posts SET reply_to_id = ?, subject = ?, content = ?, image_path = ?, thumb_path = ?, updated_at = ? WHERE post_id = ?",
            [$postData->replyToId, $postData->subject, $postData->content, $postData->imagePath, $postData->thumbPath, date("Y-m-d H:i:s"), $postData->postId]
        ) > 0;
    }

    public function getAllThreads(): array
    {
        $rows = Database::fetchAll("SELECT * FROM posts WHERE reply_to_id IS NULL ORDER BY created_at DESC");
        return array_map(fn (array $row) => Post::fromRow($row), $rows);
    }

    public function getReplies(Post $postData): array
    {
        $rows = Database::fetchAll(
            "SELECT * FROM posts WHERE reply_to_id = ? ORDER BY created_at ASC",
            [$postData->postId]
        );
        return array_map(fn (array $row) => Post::fromRow($row), $rows);
    }
}
