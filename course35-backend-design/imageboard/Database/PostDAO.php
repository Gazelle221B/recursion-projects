<?php
declare(strict_types=1);

namespace Imageboard\Database;

use Imageboard\Models\Post;

interface PostDAO
{
    public function create(Post $postData): bool;
    public function getById(int $id): ?Post;
    public function update(Post $postData): bool;
    public function delete(int $id): bool;
    public function createOrUpdate(Post $postData): bool;
    /** Main threads: posts whose reply_to_id is null, newest first. */
    public function getAllThreads(): array;
    /** Replies to the given thread post, oldest first. */
    public function getReplies(Post $postData): array;
}
