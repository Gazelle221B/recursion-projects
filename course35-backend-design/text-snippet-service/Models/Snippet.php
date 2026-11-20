<?php
declare(strict_types=1);

namespace SnippetService\Models;

use SnippetService\Database\Database;

class Snippet
{
    public const MAX_CONTENT_LENGTH = 100000;
    public const LANGUAGES = ["plaintext", "php", "javascript", "python", "java", "html", "css", "sql", "json"];
    public const EXPIRY_OPTIONS = [
        "10min" => 600,
        "1hour" => 3600,
        "1day" => 86400,
        "never" => null,
    ];

    public function __construct(
        public ?int $id,
        public string $slug,
        public string $content,
        public string $language,
        public string $createdAt,
        public ?string $expiresAt,
    ) {
    }

    public static function generateSlug(string $content): string
    {
        // Hash-based unique string; retry with a random suffix on collision.
        $base = substr(hash("sha256", $content . microtime(true)), 0, 8);
        $slug = $base;
        $attempt = 0;
        while (self::findBySlug($slug) !== null) {
            $slug = $base . dechex($attempt++);
        }
        return $slug;
    }

    public static function create(string $content, string $language, ?int $expirySeconds): self
    {
        $slug = self::generateSlug($content);
        $expiresAt = $expirySeconds === null ? null : date("Y-m-d H:i:s", time() + $expirySeconds);
        Database::execute(
            "INSERT INTO snippets (slug, content, language, expires_at) VALUES (?, ?, ?, ?)",
            [$slug, $content, $language, $expiresAt]
        );
        $snippet = self::findBySlug($slug);
        if ($snippet === null) {
            throw new \RuntimeException("Failed to store the snippet.");
        }
        return $snippet;
    }

    public static function findBySlug(string $slug): ?self
    {
        $row = Database::fetchOne("SELECT * FROM snippets WHERE slug = ?", [$slug]);
        if ($row === null) {
            return null;
        }
        return new self(
            (int) $row["id"],
            $row["slug"],
            $row["content"],
            $row["language"],
            $row["created_at"],
            $row["expires_at"]
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && strtotime($this->expiresAt) <= time();
    }

    public function delete(): void
    {
        Database::execute("DELETE FROM snippets WHERE id = ?", [$this->id]);
    }
}
