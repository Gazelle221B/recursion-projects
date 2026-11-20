<?php
declare(strict_types=1);

// Email Verification System: registration with signed-URL email
// verification, as specified in Recursion course 35, lesson 1306.
// Run locally with: php -S localhost:8000 -t public public/index.php

spl_autoload_register(function (string $class): void {
    $prefix = "EmailVerification\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use EmailVerification\Database\Database;
use EmailVerification\Helpers\Mailer;
use EmailVerification\Helpers\SignedUrl;

session_start();

$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$baseUrl = ($_SERVER["REQUEST_SCHEME"] ?? "http") . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost");

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
        <title>Email Verification System</title>
        <style>body{font-family:sans-serif;margin:2em}.error{color:#b00020}.ok{color:#0a7a28}</style>
    </head>
    <body>
        <h1><a href="/">Email Verification System</a></h1>
        {$body}
    </body>
    </html>
    HTML;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION["flash"][$key] = $value;
        return null;
    }
    $stored = $_SESSION["flash"][$key] ?? null;
    unset($_SESSION["flash"][$key]);
    return $stored;
}

function currentUser(): ?array
{
    $id = $_SESSION["user_id"] ?? null;
    if ($id === null) {
        return null;
    }
    return Database::fetchOne("SELECT * FROM users WHERE id = ?", [(int) $id]);
}

function sendVerification(array $user, string $baseUrl): void
{
    $url = SignedUrl::verificationUrl($baseUrl, (int) $user["id"], $user["email"], 1800); // 30 minutes
    Mailer::sendVerificationEmail($user["email"], $user["username"], $url);
}

// --- Home -------------------------------------------------------------
if ($path === "/" && $method === "GET") {
    $user = currentUser();
    if ($user === null) {
        header("Location: /login", true, 303);
        exit;
    }
    // Unverified users are redirected to the resend page (requirement 4).
    if ($user["email_verified_at"] === null) {
        header("Location: /verify/resend", true, 303);
        exit;
    }
    $name = e($user["username"]);
    page("<p>Welcome, {$name}. Your email is verified.</p><p><a href=\"/logout\">Logout</a></p>");
    exit;
}

// --- Registration -----------------------------------------------------
if ($path === "/register" && $method === "GET") {
    $error = flash("error");
    $errorHtml = $error !== null ? "<p class=\"error\">" . e($error) . "</p>" : "";
    page(<<<HTML
        <h2>Register</h2>
        {$errorHtml}
        <form action="/form/register" method="post">
            <p><input type="text" name="username" placeholder="Username" required></p>
            <p><input type="email" name="email" placeholder="Email" required></p>
            <p><input type="password" name="password" placeholder="Password" required></p>
            <p><input type="password" name="confirm_password" placeholder="Confirm password" required></p>
            <p><button type="submit">Register</button></p>
        </form>
        <p><a href="/login">Login</a></p>
    HTML);
    exit;
}

if ($path === "/form/register" && $method === "POST") {
    $username = trim((string) ($_POST["username"] ?? ""));
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $confirm = (string) ($_POST["confirm_password"] ?? "");

    $error = null;
    if ($username === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid username and email.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
        $error = "Password must be at least 8 characters and include upper/lowercase letters, a number and a special character.";
    } elseif (Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]) !== null) {
        $error = "An account with this email already exists.";
    }

    if ($error !== null) {
        flash("error", $error);
        header("Location: /register", true, 303);
        exit;
    }

    Database::execute(
        "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)",
        [$username, $email, password_hash($password, PASSWORD_DEFAULT)]
    );
    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [Database::lastInsertId()]);
    $_SESSION["user_id"] = (int) $user["id"];
    session_regenerate_id(true);
    sendVerification($user, $baseUrl); // requirement 3
    header("Location: /verify/resend", true, 303);
    exit;
}

// --- Login / logout ---------------------------------------------------
if ($path === "/login" && $method === "GET") {
    $error = flash("error");
    $ok = flash("ok");
    $messages = ($error !== null ? "<p class=\"error\">" . e($error) . "</p>" : "")
        . ($ok !== null ? "<p class=\"ok\">" . e($ok) . "</p>" : "");
    page(<<<HTML
        <h2>Login</h2>
        {$messages}
        <form action="/form/login" method="post">
            <p><input type="email" name="email" placeholder="Email" required></p>
            <p><input type="password" name="password" placeholder="Password" required></p>
            <p><button type="submit">Login</button></p>
        </form>
        <p><a href="/register">Register</a></p>
    HTML);
    exit;
}

if ($path === "/form/login" && $method === "POST") {
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $user = Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    if ($user === null || !password_verify($password, $user["password_hash"])) {
        flash("error", "Failed to login, wrong email and/or password.");
        header("Location: /login", true, 303);
        exit;
    }
    $_SESSION["user_id"] = (int) $user["id"];
    session_regenerate_id(true);
    header("Location: /", true, 303);
    exit;
}

if ($path === "/logout" && $method === "GET") {
    $_SESSION = [];
    session_destroy();
    header("Location: /login", true, 303);
    exit;
}

// --- Verification link (requirement 1 & 2) -----------------------------
if ($path === "/verify/email" && $method === "GET") {
    $fullUrl = $baseUrl . ($_SERVER["REQUEST_URI"] ?? "");
    $status = SignedUrl::validate($fullUrl);
    if ($status === "invalid") {
        page("<p class=\"error\">Invalid URL.</p>");
        exit;
    }
    if ($status === "expired") {
        page("<p class=\"error\">The URL has expired. <a href=\"/verify/resend\">Request a new one</a>.</p>");
        exit;
    }
    $user = currentUser();
    $id = (int) ($_GET["id"] ?? 0);
    $userHash = (string) ($_GET["user"] ?? "");
    if ($user === null || (int) $user["id"] !== $id || !hash_equals(hash("sha256", $user["email"]), $userHash)) {
        page("<p class=\"error\">The verification link does not match the signed-in user.</p>");
        exit;
    }
    Database::execute("UPDATE users SET email_verified_at = ? WHERE id = ?", [date("Y-m-d H:i:s"), $id]);
    flash("ok", "Your email has been verified.");
    page("<p class=\"ok\">Your email has been verified.</p><p><a href=\"/\">Continue</a></p>");
    exit;
}

// --- Resend / change email (requirement 4) -----------------------------
if ($path === "/verify/resend" && $method === "GET") {
    $user = currentUser();
    if ($user === null) {
        header("Location: /login", true, 303);
        exit;
    }
    if ($user["email_verified_at"] !== null) {
        header("Location: /", true, 303);
        exit;
    }
    $sent = flash("ok");
    $sentHtml = $sent !== null ? "<p class=\"ok\">" . e($sent) . "</p>" : "";
    $email = e($user["email"]);
    page(<<<HTML
        <h2>Verify your email</h2>
        {$sentHtml}
        <p>We sent a verification link to <strong>{$email}</strong>. The link expires in 30 minutes.</p>
        <form action="/verify/resend" method="post">
            <p><button type="submit">Send Verification Email</button></p>
        </form>
        <h3>Change email address</h3>
        <form action="/verify/change-email" method="post">
            <p><input type="email" name="email" placeholder="New email address" required></p>
            <p><button type="submit">Change email and send a new link</button></p>
        </form>
        <p><a href="/logout">Logout</a></p>
    HTML);
    exit;
}

if ($path === "/verify/resend" && $method === "POST") {
    $user = currentUser();
    if ($user === null) {
        header("Location: /login", true, 303);
        exit;
    }
    sendVerification($user, $baseUrl);
    flash("ok", "A new verification email has been sent.");
    header("Location: /verify/resend", true, 303);
    exit;
}

if ($path === "/verify/change-email" && $method === "POST") {
    $user = currentUser();
    if ($user === null) {
        header("Location: /login", true, 303);
        exit;
    }
    $email = trim((string) ($_POST["email"] ?? ""));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash("error", "Please enter a valid email address.");
        header("Location: /verify/resend", true, 303);
        exit;
    }
    if (Database::fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user["id"]]) !== null) {
        flash("error", "An account with this email already exists.");
        header("Location: /verify/resend", true, 303);
        exit;
    }
    Database::execute("UPDATE users SET email = ?, email_verified_at = NULL WHERE id = ?", [$email, $user["id"]]);
    $user["email"] = $email;
    sendVerification($user, $baseUrl);
    flash("ok", "Your email address was changed and a new verification email has been sent.");
    header("Location: /verify/resend", true, 303);
    exit;
}

http_response_code(404);
page("<p>Page not found.</p>");
