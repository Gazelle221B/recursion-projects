<?php
declare(strict_types=1);

// Retention cleanup: deletes images that have not been accessed for
// Image::RETENTION_DAYS days. Schedule it once a day with cron, e.g.:
//   0 3 * * * /usr/bin/php /path/to/image-hosting-service/cleanup.php

spl_autoload_register(function (string $class): void {
    $prefix = "ImageHosting\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = __DIR__ . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use ImageHosting\Database\Database;
use ImageHosting\Models\Image;

$threshold = date("Y-m-d H:i:s", time() - Image::RETENTION_DAYS * 86400);
$rows = Database::fetchAll("SELECT * FROM images WHERE last_accessed_at < ?", [$threshold]);

$deleted = 0;
foreach ($rows as $row) {
    $path = Image::storageDir() . "/" . $row["file_name"];
    if (is_file($path)) {
        unlink($path);
    }
    Database::execute("DELETE FROM images WHERE id = ?", [$row["id"]]);
    $deleted++;
}
echo "Cleanup complete: {$deleted} image(s) deleted (not accessed since {$threshold}).\n";
