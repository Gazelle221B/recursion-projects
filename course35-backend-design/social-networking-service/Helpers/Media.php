<?php
declare(strict_types=1);

namespace SocialNetworking\Helpers;

use RuntimeException;

/**
 * Validates and stores uploaded media under storage/uploads/.
 * Images (JPEG/PNG/GIF) up to 5 MB, videos (MP4/WebM) up to 20 MB.
 * The real type is detected with finfo, never from the file name.
 */
class Media
{
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
    public const MAX_VIDEO_BYTES = 20 * 1024 * 1024;

    private const TYPES = [
        "image/jpeg" => ["ext" => "jpg", "kind" => "image"],
        "image/png" => ["ext" => "png", "kind" => "image"],
        "image/gif" => ["ext" => "gif", "kind" => "image"],
        "video/mp4" => ["ext" => "mp4", "kind" => "video"],
        "video/webm" => ["ext" => "webm", "kind" => "video"],
    ];

    public static function uploadDir(): string
    {
        $dir = dirname(__DIR__) . "/storage/uploads";
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /**
     * Stores an uploaded file from $_FILES.
     *
     * @return array{path: string, type: string}|null null when no file was sent
     * @throws RuntimeException on an invalid or oversized file
     */
    public static function store(array $file, bool $imagesOnly = false): ?array
    {
        if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException("The file upload failed.");
        }
        $mime = mime_content_type($file["tmp_name"]);
        $info = self::TYPES[$mime] ?? null;
        if ($info === null || ($imagesOnly && $info["kind"] !== "image")) {
            throw new RuntimeException("Only JPEG/PNG/GIF images and MP4/WebM videos are allowed.");
        }
        $limit = $info["kind"] === "video" ? self::MAX_VIDEO_BYTES : self::MAX_IMAGE_BYTES;
        if (($file["size"] ?? 0) > $limit) {
            throw new RuntimeException("The file is too large (images up to 5 MB, videos up to 20 MB).");
        }
        $name = bin2hex(random_bytes(16)) . "." . $info["ext"];
        if (!move_uploaded_file($file["tmp_name"], self::uploadDir() . "/" . $name)) {
            throw new RuntimeException("Could not store the uploaded file.");
        }
        return ["path" => $name, "type" => $info["kind"]];
    }

    /** Serves a stored file by its bare file name (path traversal safe). */
    public static function serve(string $name): void
    {
        $safe = basename($name);
        $full = self::uploadDir() . "/" . $safe;
        if (!is_file($full)) {
            http_response_code(404);
            echo "Not found.";
            return;
        }
        $mime = mime_content_type($full) ?: "application/octet-stream";
        header("Content-Type: " . $mime);
        header("Content-Length: " . filesize($full));
        readfile($full);
    }
}
