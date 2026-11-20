<?php
declare(strict_types=1);

// Imageboard Webapp: anonymous, thread-based image board.
// Run locally with: php -S localhost:8000 -t public public/index.php

spl_autoload_register(function (string $class): void {
    $prefix = "Imageboard\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

// Serve stored images (kept outside the public folder in a real deploy,
// this keeps the local demo single-command).
if (preg_match("#^/storage/([A-Za-z0-9._-]+)$#", parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) ?: "", $m)) {
    $file = dirname(__DIR__) . "/storage/" . $m[1];
    if (is_file($file)) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
        header("Content-Type: " . $mime);
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit("Not found");
}

use Imageboard\Database\PostDAOImpl;
use Imageboard\Helpers\ImageUploader;
use Imageboard\Models\Post;

$dao = new PostDAOImpl();
$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

function e(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function page(string $body): void
{
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Imageboard</title>
        <style>
            body{font-family:sans-serif;margin:2em}
            .thread{border:1px solid #ccc;border-radius:8px;padding:1em;margin-bottom:1.5em}
            .reply{border-top:1px dashed #ccc;padding:0.5em 0 0.5em 2em}
            .error{color:#b00020}
            img.thumb{max-width:150px;max-height:150px}
        </style>
    </head>
    <body>
        <h1><a href="/">Imageboard</a></h1>
        {$body}
    </body>
    </html>
    HTML;
}

function renderPost(Post $post, bool $isReply): string
{
    $class = $isReply ? "reply" : "";
    $subject = $post->subject !== null && $post->subject !== "" ? "<h3>" . e($post->subject) . "</h3>" : "";
    $image = "";
    if ($post->imagePath !== null) {
        $thumb = e($post->thumbPath ?? $post->imagePath);
        $full = e($post->imagePath);
        $image = "<p><a href=\"/storage/{$full}\"><img class=\"thumb\" src=\"/storage/{$thumb}\" alt=\"post image\"></a></p>";
    }
    return "<div class=\"{$class}\">{$subject}{$image}<p>" . nl2br(e($post->content)) . "</p><small>#" . $post->postId . " · " . e($post->createdAt) . "</small></div>";
}

// Create a thread or a reply.
if ($path === "/post" && $method === "POST") {
    $replyToId = isset($_POST["reply_to_id"]) && $_POST["reply_to_id"] !== "" ? (int) $_POST["reply_to_id"] : null;
    $subject = isset($_POST["subject"]) ? trim((string) $_POST["subject"]) : null;
    $content = trim((string) ($_POST["content"] ?? ""));

    $errors = [];
    if ($content === "") {
        $errors[] = "Content must not be empty.";
    } elseif (mb_strlen($content) > 4000) {
        $errors[] = "Content is too long (max 4000 characters).";
    }
    if ($subject !== null && mb_strlen($subject) > 255) {
        $errors[] = "Subject is too long (max 255 characters).";
    }
    if ($replyToId !== null && $dao->getById($replyToId) === null) {
        $errors[] = "Thread not found.";
    }

    $imagePath = null;
    $thumbPath = null;
    $hasImage = isset($_FILES["image"]) && ($_FILES["image"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($errors === [] && $hasImage) {
        try {
            // The file name is hashed from the would-be post id + timestamp,
            // so store it with a provisional id (0) and rename by content hash.
            $stored = ImageUploader::store($_FILES["image"], 0);
            $imagePath = $stored["image"];
            $thumbPath = $stored["thumb"];
        } catch (InvalidArgumentException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
    if ($replyToId === null && !$hasImage && $errors === []) {
        $errors[] = "A new thread requires an image.";
    }

    if ($errors !== []) {
        $list = implode("</li><li>", array_map(fn ($msg) => e($msg), $errors));
        $back = $replyToId === null ? "/" : "/thread?id=" . $replyToId;
        page("<p class=\"error\">Post failed:</p><ul><li>{$list}</li></ul><p><a href=\"{$back}\">Back</a></p>");
        exit;
    }

    $post = new Post(null, $replyToId, $replyToId === null ? $subject : null, $content, $imagePath, $thumbPath);
    $dao->create($post);
    $target = $replyToId === null ? "/thread?id=" . $post->postId : "/thread?id=" . $replyToId;
    header("Location: " . $target, true, 303);
    exit;
}

// Thread view: the thread plus all of its replies.
if ($path === "/thread" && $method === "GET") {
    $id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
    $thread = $dao->getById($id);
    if ($thread === null || !$thread->isThread()) {
        http_response_code(404);
        page("<p>Thread not found.</p>");
        exit;
    }
    $body = "<div class=\"thread\">" . renderPost($thread, false);
    foreach ($dao->getReplies($thread) as $reply) {
        $body .= renderPost($reply, true);
    }
    $body .= <<<HTML
        </div>
        <h2>Reply</h2>
        <form action="/post" method="post" enctype="multipart/form-data">
            <input type="hidden" name="reply_to_id" value="{$thread->postId}">
            <p><textarea name="content" rows="4" cols="60" required maxlength="4000"></textarea></p>
            <p><input type="file" name="image" accept="image/jpeg,image/png,image/gif"> (optional)</p>
            <p><button type="submit">Post reply</button></p>
        </form>
    HTML;
    page($body);
    exit;
}

// Board index: threads with their latest 5 replies, plus the new-thread form.
if ($path === "/" && $method === "GET") {
    $body = <<<HTML
        <h2>Start a new thread</h2>
        <form action="/post" method="post" enctype="multipart/form-data">
            <p><input type="text" name="subject" placeholder="Subject (optional)" maxlength="255" size="60"></p>
            <p><textarea name="content" rows="4" cols="60" required maxlength="4000" placeholder="Content"></textarea></p>
            <p><input type="file" name="image" accept="image/jpeg,image/png,image/gif" required> (required for new threads)</p>
            <p><button type="submit">Create thread</button></p>
        </form>
        <h2>Threads</h2>
    HTML;
    $threads = $dao->getAllThreads();
    if ($threads === []) {
        $body .= "<p>No threads yet. Run <code>php Database/seed.php</code> for dummy data.</p>";
    }
    foreach ($threads as $thread) {
        $body .= "<div class=\"thread\">" . renderPost($thread, false);
        $replies = $dao->getReplies($thread);
        foreach (array_slice($replies, -5) as $reply) {
            $body .= renderPost($reply, true);
        }
        $count = count($replies);
        $body .= "<p><a href=\"/thread?id={$thread->postId}\">View thread ({$count} replies)</a></p></div>";
    }
    page($body);
    exit;
}

http_response_code(404);
page("<p>Page not found.</p>");
