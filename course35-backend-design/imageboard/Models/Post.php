<?php
declare(strict_types=1);

namespace Imageboard\Models;

class Post
{
    public function __construct(
        public ?int $postId = null,
        public ?int $replyToId = null,
        public ?string $subject = null,
        public string $content = "",
        public ?string $imagePath = null,
        public ?string $thumbPath = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    public function isThread(): bool
    {
        return $this->replyToId === null;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row["post_id"],
            $row["reply_to_id"] === null ? null : (int) $row["reply_to_id"],
            $row["subject"],
            $row["content"],
            $row["image_path"],
            $row["thumb_path"],
            $row["created_at"],
            $row["updated_at"]
        );
    }
}
