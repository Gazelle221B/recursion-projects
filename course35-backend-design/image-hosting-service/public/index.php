<?php
declare(strict_types=1);

// Front controller for the Online Image Hosting Service.
// Run locally with: php -S localhost:8000 -t public public/index.php

spl_autoload_register(function (string $class): void {
    $prefix = "ImageHosting\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use ImageHosting\Models\Image;

$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$isApi = str_starts_with($path, "/api/");

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
}

function page(string $body): void
{
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Image Hosting Service</title>
        <style>body{font-family:sans-serif;margin:2em}.error{color:#b00020}img{max-width:90%}</style>
    </head>
    <body>
        <h1><a href="/">Image Hosting Service</a></h1>
        {$body}
    </body>
    </html>
    HTML;
}

if ($path === "/" && $method === "GET") {
    page(<<<HTML
        <form id="uploadForm" enctype="multipart/form-data">
            <p><input type="file" name="image" accept="image/jpeg,image/png,image/gif" required></p>
            <p><button type="submit">Upload</button></p>
        </form>
        <div id="result"></div>
        <script>
            document.getElementById("uploadForm").addEventListener("submit", async (e) => {
                e.preventDefault();
                const result = document.getElementById("result");
                const res = await fetch("/api/upload", { method: "POST", body: new FormData(e.target) });
                const data = await res.json();
                if (!res.ok) {
                    result.innerHTML = '<p class="error">' + data.error + "</p>";
                    return;
                }
                result.innerHTML =
                    '<p>Image URL: <a href="' + data.imageUrl + '">' + data.imageUrl + "</a></p>" +
                    '<p>Delete URL: <a href="' + data.deleteUrl + '">' + data.deleteUrl + "</a></p>" +
                    '<p><img src="' + data.imageUrl + '" alt="uploaded image"></p>';
            });
        </script>
    HTML);
    exit;
}

if ($path === "/api/upload" && $method === "POST") {
    try {
        $image = Image::store($_FILES["image"] ?? [], $_SERVER["REMOTE_ADDR"] ?? "");
        $base = ($_SERVER["REQUEST_SCHEME"] ?? "http") . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost");
        jsonResponse([
            "imageUrl" => $base . "/" . $image->mediaType . "/" . $image->slug,
            "deleteUrl" => $base . "/delete/" . $image->deleteSlug,
        ], 201);
    } catch (InvalidArgumentException $e) {
        jsonResponse(["error" => $e->getMessage()], 422);
    } catch (Throwable $e) {
        jsonResponse(["error" => "Server error. Please try again later."], 500);
    }
    exit;
}

// View: /{media-type}/{slug}
if (preg_match("#^/(jpg|png|gif)/([A-Za-z0-9]+)$#", $path, $m) && $method === "GET") {
    $image = Image::findBySlug($m[2]);
    if ($image === null || !is_file($image->filePath())) {
        http_response_code(404);
        page("<p>Image not found.</p>");
        exit;
    }
    // The HTML view counts as a view and shows the counter; the raw file
    // itself is served from /raw/{slug} so it can be embedded anywhere.
    $image->registerView();
    $safeSlug = htmlspecialchars($image->slug, ENT_QUOTES, "UTF-8");
    page(<<<HTML
        <p><img src="/raw/{$safeSlug}" alt="hosted image"></p>
        <p>Views: {$image->viewCount}</p>
    HTML);
    exit;
}

if (preg_match("#^/raw/([A-Za-z0-9]+)$#", $path, $m) && $method === "GET") {
    $image = Image::findBySlug($m[1]);
    if ($image === null || !is_file($image->filePath())) {
        http_response_code(404);
        exit("Not found");
    }
    header("Content-Type: " . $image->mime);
    header("Content-Length: " . filesize($image->filePath()));
    readfile($image->filePath());
    exit;
}

// Delete: /delete/{delete-slug}
if (preg_match("#^/delete/([A-Za-z0-9]+)$#", $path, $m) && $method === "GET") {
    $image = Image::findByDeleteSlug($m[1]);
    if ($image === null) {
        http_response_code(404);
        page("<p>Image not found (it may already be deleted).</p>");
        exit;
    }
    $image->delete();
    page("<p>Image deleted.</p>");
    exit;
}

http_response_code(404);
if ($isApi) {
    jsonResponse(["error" => "Not found."], 404);
} else {
    page("<p>Page not found.</p>");
}
