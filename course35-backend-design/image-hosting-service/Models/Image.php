<?php
declare(strict_types=1);

namespace ImageHosting\Models;

use ImageHosting\Database\Database;

class Image
{
    public const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
    public const MAX_UPLOADS_PER_HOUR = 20;
    public const RETENTION_DAYS = 30;
    public const ALLOWED = [
        "image/jpeg" => ["media" => "jpg", "ext" => "jpg"],
        "image/png" => ["media" => "png", "ext" => "png"],
        "image/gif" => ["media" => "gif", "ext" => "gif"],
    ];

    public function __construct(
        public ?int $id,
        public string $slug,
        public string $deleteSlug,
        public string $mediaType,
        public string $fileName,
        public string $mime,
        public int $viewCount,
        public string $lastAccessedAt,
        public string $createdAt,
    ) {
    }

    public static function storageDir(): string
    {
        return dirname(__DIR__) . "/storage";
    }

    public function filePath(): string
    {
        return self::storageDir() . "/" . $this->fileName;
    }

    public static function uploadsInLastHour(string $ip): int
    {
        $driver = Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $since = $driver === "sqlite"
            ? date("Y-m-d H:i:s", time() - 3600)
            : date("Y-m-d H:i:s", time() - 3600);
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM images WHERE ip_address = ? AND created_at >= ?",
            [$ip, $since]
        );
        return (int) ($row["c"] ?? 0);
    }

    /**
     * Validate and store an uploaded file. Returns the stored Image.
     * Throws InvalidArgumentException with a user-facing message.
     */
    public static function store(array $file, string $ip): self
    {
        if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException("Upload failed. Please try again.");
        }
        if (($file["size"] ?? 0) > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException("File is too large (max 10 MB).");
        }
        if (self::uploadsInLastHour($ip) >= self::MAX_UPLOADS_PER_HOUR) {
            throw new \InvalidArgumentException("Upload rate limit exceeded. Please wait and try again.");
        }

        $tmp = $file["tmp_name"] ?? "";
        $mime = is_file($tmp) ? (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) : false;
        if ($mime === false || !isset(self::ALLOWED[$mime])) {
            throw new \InvalidArgumentException("Unsupported image type. Only JPEG, PNG and GIF are allowed.");
        }
        $info = self::ALLOWED[$mime];

        $slug = substr(hash("sha256", random_bytes(16)), 0, 10);
        while (self::findBySlug($slug) !== null) {
            $slug = substr(hash("sha256", random_bytes(16)), 0, 10);
        }
        $deleteSlug = substr(hash("sha256", random_bytes(16) . $slug), 0, 20);
        $fileName = $slug . "." . $info["ext"];

        $dir = self::storageDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!move_uploaded_file($tmp, $dir . "/" . $fileName)) {
            throw new \RuntimeException("Failed to store the uploaded file.");
        }

        Database::execute(
            "INSERT INTO images (slug, delete_slug, media_type, file_name, mime, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
            [$slug, $deleteSlug, $info["media"], $fileName, $mime, $ip]
        );
        $image = self::findBySlug($slug);
        if ($image === null) {
            throw new \RuntimeException("Failed to register the image.");
        }
        return $image;
    }

    public static function findBySlug(string $slug): ?self
    {
        $row = Database::fetchOne("SELECT * FROM images WHERE slug = ?", [$slug]);
        return $row === null ? null : self::fromRow($row);
    }

    public static function findByDeleteSlug(string $deleteSlug): ?self
    {
        $row = Database::fetchOne("SELECT * FROM images WHERE delete_slug = ?", [$deleteSlug]);
        return $row === null ? null : self::fromRow($row);
    }

    public function registerView(): void
    {
        Database::execute(
            "UPDATE images SET view_count = view_count + 1, last_accessed_at = ? WHERE id = ?",
            [date("Y-m-d H:i:s"), $this->id]
        );
        $this->viewCount++;
    }

    public function delete(): void
    {
        if (is_file($this->filePath())) {
            unlink($this->filePath());
        }
        Database::execute("DELETE FROM images WHERE id = ?", [$this->id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            (int) $row["id"],
            $row["slug"],
            $row["delete_slug"],
            $row["media_type"],
            $row["file_name"],
            $row["mime"],
            (int) $row["view_count"],
            $row["last_accessed_at"],
            $row["created_at"]
        );
    }
}
