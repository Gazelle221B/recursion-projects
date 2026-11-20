<?php
declare(strict_types=1);

// Cron entry point for scheduled posts. Run every minute, e.g.:
//   * * * * * php /path/to/social-networking-service/publish_scheduled.php
// It flips every scheduled post whose publish_at has passed to
// 'published', so it appears in timelines and threads.

if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("CLI only.\n");
}

spl_autoload_register(function (string $class): void {
    $prefix = "SocialNetworking\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = __DIR__ . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use SocialNetworking\Models\Post;

$count = Post::publishDue();
echo date("Y-m-d H:i:s") . " Published {$count} scheduled post(s).\n";
