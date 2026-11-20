<?php
declare(strict_types=1);

// Front controller for the Text Snippet Sharing Service.
// Run locally with: php -S localhost:8000 -t public public/index.php

spl_autoload_register(function (string $class): void {
    $prefix = "SnippetService\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use SnippetService\Models\Snippet;

$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

function render(string $title, string $body): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, "UTF-8");
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>{$safeTitle}</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
        <style>body{font-family:sans-serif;margin:2em}textarea{width:100%}pre{border:1px solid #ccc;padding:1em;overflow:auto}.error{color:#b00020}</style>
    </head>
    <body>
        <h1><a href="/">Text Snippet Sharing Service</a></h1>
        {$body}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
        <script>hljs.highlightAll();</script>
    </body>
    </html>
    HTML;
}

if ($path === "/" && $method === "GET") {
    $languageOptions = "";
    foreach (Snippet::LANGUAGES as $lang) {
        $languageOptions .= sprintf('<option value="%s">%s</option>', $lang, $lang);
    }
    $expiryOptions = "";
    foreach (array_keys(Snippet::EXPIRY_OPTIONS) as $key) {
        $expiryOptions .= sprintf('<option value="%s">%s</option>', $key, $key);
    }
    render("Share a snippet", <<<HTML
        <form action="/create" method="post">
            <p><textarea name="content" rows="16" required maxlength="100000" placeholder="Paste text or code here..."></textarea></p>
            <p>
                <label>Language:
                    <select name="language">{$languageOptions}</select>
                </label>
                <label>Expires:
                    <select name="expiry">{$expiryOptions}</select>
                </label>
                <button type="submit">Share</button>
            </p>
        </form>
    HTML);
    exit;
}

if ($path === "/create" && $method === "POST") {
    $content = $_POST["content"] ?? "";
    $language = $_POST["language"] ?? "plaintext";
    $expiryKey = $_POST["expiry"] ?? "never";

    $errors = [];
    if (!is_string($content) || trim($content) === "") {
        $errors[] = "Content must not be empty.";
    } elseif (mb_strlen($content) > Snippet::MAX_CONTENT_LENGTH) {
        $errors[] = "Content is too large (max " . Snippet::MAX_CONTENT_LENGTH . " characters).";
    } elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content)) {
        $errors[] = "Content contains unsupported control characters.";
    }
    if (!in_array($language, Snippet::LANGUAGES, true)) {
        $errors[] = "Unsupported language.";
    }
    if (!array_key_exists($expiryKey, Snippet::EXPIRY_OPTIONS)) {
        $errors[] = "Invalid expiry option.";
    }

    if ($errors !== []) {
        $list = implode("</li><li>", array_map(fn ($e) => htmlspecialchars($e, ENT_QUOTES, "UTF-8"), $errors));
        render("Error", "<p class=\"error\">Submission failed:</p><ul><li>{$list}</li></ul><p><a href=\"/\">Back</a></p>");
        exit;
    }

    $snippet = Snippet::create($content, $language, Snippet::EXPIRY_OPTIONS[$expiryKey]);
    header("Location: /s/" . $snippet->slug, true, 303);
    exit;
}

if (preg_match("#^/s/([A-Za-z0-9]+)$#", $path, $m) && $method === "GET") {
    $snippet = Snippet::findBySlug($m[1]);
    if ($snippet === null) {
        http_response_code(404);
        render("Not found", "<p>Snippet not found.</p>");
        exit;
    }
    if ($snippet->isExpired()) {
        $snippet->delete();
        render("Expired Snippet", "<p>This snippet has expired and was deleted.</p>");
        exit;
    }
    $safeContent = htmlspecialchars($snippet->content, ENT_QUOTES, "UTF-8");
    $safeLang = htmlspecialchars($snippet->language, ENT_QUOTES, "UTF-8");
    $expires = $snippet->expiresAt === null ? "never" : htmlspecialchars($snippet->expiresAt, ENT_QUOTES, "UTF-8");
    render("Snippet " . $snippet->slug, <<<HTML
        <p>Language: {$safeLang} / Expires: {$expires}</p>
        <pre><code class="language-{$safeLang}">{$safeContent}</code></pre>
    HTML);
    exit;
}

http_response_code(404);
render("Not found", "<p>Page not found.</p>");
