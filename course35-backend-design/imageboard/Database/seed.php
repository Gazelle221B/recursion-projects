<?php
declare(strict_types=1);

// Seeder: inserts dummy threads and replies with a shared placeholder
// image (generated with GD). Usage: php Database/seed.php

spl_autoload_register(function (string $class): void {
    $prefix = "Imageboard\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use Imageboard\Database\PostDAOImpl;
use Imageboard\Models\Post;

$dao = new PostDAOImpl();

$storage = dirname(__DIR__) . "/storage";
if (!is_dir($storage)) {
    mkdir($storage, 0775, true);
}
$placeholder = $storage . "/seed_placeholder.png";
if (!is_file($placeholder)) {
    $img = imagecreatetruecolor(320, 240);
    $bg = imagecolorallocate($img, 70, 130, 180);
    imagefill($img, 0, 0, $bg);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagestring($img, 5, 90, 110, "seed image", $white);
    imagepng($img, $placeholder);
    imagedestroy($img);
    copy($placeholder, $storage . "/seed_placeholder_150x150.png");
}

$subjects = ["Welcome to the board", "Show your desk setup", "Best hiking trails?", "Weekend cooking thread", "Retro games appreciation"];
$bodies = [
    "This is a seeded opening post. Feel free to reply.",
    "Sharing a photo from last weekend. What do you think?",
    "Looking for recommendations, any favorites?",
    "Tried something new today and it worked out great.",
    "A quiet thread for slow discussion.",
];
$replyBodies = ["Nice post!", "I agree with this.", "Thanks for sharing.", "Interesting, tell me more.", "Same here."];

$count = 0;
foreach ($subjects as $i => $subject) {
    $thread = new Post(null, null, $subject, $bodies[$i], "seed_placeholder.png", "seed_placeholder_150x150.png");
    $dao->create($thread);
    $count++;
    $replies = ($i % 3) + 1;
    for ($r = 0; $r < $replies; $r++) {
        $dao->create(new Post(null, $thread->postId, null, $replyBodies[($i + $r) % count($replyBodies)]));
        $count++;
    }
}
echo "Seed complete: {$count} posts inserted.\n";
