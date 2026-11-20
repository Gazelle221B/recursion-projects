<?php
declare(strict_types=1);

namespace Imageboard\Helpers;

/**
 * Validates an uploaded image, stores it under storage/ with a hashed
 * file name (post id + timestamp), and creates a 150x150 thumbnail.
 * The course suggests ImageMagick's `convert`; this implementation
 * tries `convert` first and falls back to the GD extension.
 */
class ImageUploader
{
    public const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    public const ALLOWED = ["image/jpeg" => "jpg", "image/png" => "png", "image/gif" => "gif"];

    public static function storageDir(): string
    {
        return dirname(__DIR__) . "/storage";
    }

    /**
     * @return array{image: string, thumb: string} stored file names
     */
    public static function store(array $file, int $postId): array
    {
        if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new \InvalidArgumentException("No image was uploaded.");
        }
        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException("Upload failed. Please try again.");
        }
        if (($file["size"] ?? 0) > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException("Image is too large (max 5 MB).");
        }
        $tmp = $file["tmp_name"] ?? "";
        $mime = is_file($tmp) ? (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) : false;
        if ($mime === false || !isset(self::ALLOWED[$mime])) {
            throw new \InvalidArgumentException("Unsupported image type. Only JPEG, PNG and GIF are allowed.");
        }
        $ext = self::ALLOWED[$mime];

        $dir = self::storageDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $base = hash("sha256", $postId . "|" . date("Y-m-d H:i:s") . "|" . random_bytes(8));
        $imageName = $base . "." . $ext;
        $thumbName = $base . "_150x150." . $ext;

        if (!move_uploaded_file($tmp, $dir . "/" . $imageName)) {
            throw new \RuntimeException("Failed to store the image.");
        }
        self::makeThumbnail($dir . "/" . $imageName, $dir . "/" . $thumbName, $mime);
        return ["image" => $imageName, "thumb" => $thumbName];
    }

    private static function makeThumbnail(string $src, string $dest, string $mime): void
    {
        // Preferred: ImageMagick (as in the course).
        $cmd = sprintf("convert %s -resize 150x150! %s 2>/dev/null", escapeshellarg($src), escapeshellarg($dest));
        exec($cmd, $out, $code);
        if ($code === 0 && is_file($dest)) {
            return;
        }
        // Fallback: GD extension.
        $image = match ($mime) {
            "image/jpeg" => imagecreatefromjpeg($src),
            "image/png" => imagecreatefrompng($src),
            "image/gif" => imagecreatefromgif($src),
            default => false,
        };
        if ($image === false) {
            copy($src, $dest);
            return;
        }
        $thumb = imagecreatetruecolor(150, 150);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, 150, 150, imagesx($image), imagesy($image));
        match ($mime) {
            "image/jpeg" => imagejpeg($thumb, $dest),
            "image/png" => imagepng($thumb, $dest),
            "image/gif" => imagegif($thumb, $dest),
        };
        imagedestroy($image);
        imagedestroy($thumb);
    }
}
