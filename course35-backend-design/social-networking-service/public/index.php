<?php
declare(strict_types=1);

// Social Networking Service: a Twitter-like SNS built on a small
// hand-rolled micro-framework (front controller + routes below),
// as specified in Recursion course 35, lesson 1307.
// Run locally with: php -S localhost:8000 -t public public/index.php

spl_autoload_register(function (string $class): void {
    $prefix = "SocialNetworking\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use SocialNetworking\Helpers\Media;
use SocialNetworking\Models\Message;
use SocialNetworking\Models\Notification;
use SocialNetworking\Models\Post;
use SocialNetworking\Models\User;

session_start();

$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

function e(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function currentUser(): ?array
{
    $id = $_SESSION["user_id"] ?? null;
    return $id === null ? null : User::findById((int) $id);
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

function redirect(string $to): void
{
    header("Location: {$to}", true, 303);
    exit;
}

function back(): void
{
    redirect($_SERVER["HTTP_REFERER"] ?? "/");
}

function page(string $title, string $body, ?array $user): void
{
    $nav = "";
    if ($user !== null) {
        $unread = Notification::unreadCount((int) $user["id"]);
        $badge = $unread > 0 ? " ({$unread})" : "";
        $username = e($user["username"]);
        $nav = <<<HTML
        <nav>
            <a href="/">Home</a> |
            <a href="/notifications">Notifications{$badge}</a> |
            <a href="/messages">Messages</a> |
            <a href="/profile/{$username}">Profile</a> |
            <a href="/logout">Logout</a>
        </nav>
        HTML;
    }
    $error = flash("error");
    $ok = flash("ok");
    $messages = ($error !== null ? "<p class=\"error\">" . e($error) . "</p>" : "")
        . ($ok !== null ? "<p class=\"ok\">" . e($ok) . "</p>" : "");
    $titleHtml = e($title);
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>{$titleHtml} - SNS</title>
        <style>
            body{font-family:sans-serif;margin:2em;max-width:760px}
            .error{color:#b00020}.ok{color:#0a7a28}
            .post{border:1px solid #ccc;border-radius:8px;padding:1em;margin:1em 0}
            .meta{color:#666;font-size:.85em}
            .avatar{width:40px;height:40px;border-radius:50%;vertical-align:middle;object-fit:cover}
            .reply{margin-left:2em}
            img.media,video.media{max-width:100%;border-radius:8px}
            .tab{font-weight:normal}.tab.active{font-weight:bold}
            textarea{width:100%}
        </style>
    </head>
    <body>
        <h1><a href="/">Social Networking Service</a></h1>
        {$nav}
        {$messages}
        {$body}
    </body>
    </html>
    HTML;
}

function avatarHtml(array $user, string $key = "photo_path"): string
{
    $photo = $user[$key] ?? null;
    if ($photo === null || $photo === "") {
        return "";
    }
    return "<img class=\"avatar\" src=\"/media/" . e($photo) . "\" alt=\"\">";
}

function postCardHtml(array $post, array $viewer, bool $withActions = true): string
{
    $id = (int) $post["id"];
    $author = e($post["display_name"]);
    $username = e($post["username"]);
    $body = nl2br(e($post["body"]));
    $created = e($post["created_at"]);
    $likeCount = Post::likeCount($id);
    $replyCount = Post::replyCount($id);
    $avatar = avatarHtml($post, "author_photo");
    $media = "";
    if (!empty($post["media_path"])) {
        $src = "/media/" . e($post["media_path"]);
        $media = $post["media_type"] === "video"
            ? "<p><video class=\"media\" src=\"{$src}\" controls></video></p>"
            : "<p><img class=\"media\" src=\"{$src}\" alt=\"\"></p>";
    }
    $actions = "";
    if ($withActions) {
        $liked = Post::likedBy((int) $viewer["id"], $id) ? "Unlike" : "Like";
        $delete = (int) $post["user_id"] === (int) $viewer["id"]
            ? "<form method=\"post\" action=\"/form/post/{$id}/delete\" style=\"display:inline\" onsubmit=\"return confirm('Delete this post?')\"><button type=\"submit\">Delete</button></form>"
            : "";
        $actions = <<<HTML
        <form method="post" action="/form/post/{$id}/like" style="display:inline"><button type="submit">{$liked} ({$likeCount})</button></form>
        <a href="/post/{$id}">Replies ({$replyCount})</a>
        {$delete}
        HTML;
    }
    return <<<HTML
    <div class="post">
        <p>{$avatar} <strong>{$author}</strong> <a href="/profile/{$username}">@{$username}</a>
        <span class="meta">{$created}</span></p>
        <p>{$body}</p>
        {$media}
        {$actions}
    </div>
    HTML;
}

/** Renders a reply tree recursively with indentation. */
function replyTreeHtml(array $post, array $viewer, int $depth): string
{
    $html = "<div class=\"" . ($depth > 0 ? "reply" : "") . "\">" . postCardHtml($post, $viewer) . "</div>";
    if ($depth < 10) {
        foreach (Post::replies((int) $post["id"]) as $reply) {
            $html .= replyTreeHtml($reply, $viewer, $depth + 1);
        }
    }
    return $html;
}

function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        redirect("/login");
    }
    return $user;
}

// --- Media files --------------------------------------------------------
if (preg_match("#^/media/(.+)$#", $path, $m) && $method === "GET") {
    Media::serve(urldecode($m[1]));
    exit;
}

// --- Registration -------------------------------------------------------
if ($path === "/register" && $method === "GET") {
    page("Register", <<<HTML
        <h2>Register</h2>
        <form action="/form/register" method="post">
            <p><input type="text" name="username" placeholder="Username" required></p>
            <p><input type="text" name="display_name" placeholder="Display name"></p>
            <p><input type="email" name="email" placeholder="Email" required></p>
            <p><input type="password" name="password" placeholder="Password" required></p>
            <p><input type="password" name="confirm_password" placeholder="Confirm password" required></p>
            <p><button type="submit">Register</button></p>
        </form>
        <p><a href="/login">Login</a></p>
    HTML, null);
    exit;
}

if ($path === "/form/register" && $method === "POST") {
    $username = trim((string) ($_POST["username"] ?? ""));
    $displayName = trim((string) ($_POST["display_name"] ?? "")) ?: $username;
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $confirm = (string) ($_POST["confirm_password"] ?? "");

    $error = null;
    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $error = "Username must be 3-50 letters, digits or underscores.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
        $error = "Password must be at least 8 characters and include upper/lowercase letters, a number and a special character.";
    } elseif (User::findByUsername($username) !== null) {
        $error = "That username is already taken.";
    } elseif (User::findByEmail($email) !== null) {
        $error = "An account with this email already exists.";
    }
    if ($error !== null) {
        flash("error", $error);
        redirect("/register");
    }
    $id = User::create($username, $email, password_hash($password, PASSWORD_DEFAULT), $displayName);
    $_SESSION["user_id"] = $id;
    session_regenerate_id(true);
    redirect("/");
}

// --- Login / logout -----------------------------------------------------
if ($path === "/login" && $method === "GET") {
    page("Login", <<<HTML
        <h2>Login</h2>
        <form action="/form/login" method="post">
            <p><input type="email" name="email" placeholder="Email" required></p>
            <p><input type="password" name="password" placeholder="Password" required></p>
            <p><button type="submit">Login</button></p>
        </form>
        <p><a href="/register">Register</a></p>
    HTML, null);
    exit;
}

if ($path === "/form/login" && $method === "POST") {
    $email = trim((string) ($_POST["email"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $user = User::findByEmail($email);
    if ($user === null || !password_verify($password, $user["password_hash"])) {
        flash("error", "Failed to login, wrong email and/or password.");
        redirect("/login");
    }
    $_SESSION["user_id"] = (int) $user["id"];
    session_regenerate_id(true);
    redirect("/");
}

if ($path === "/logout" && $method === "GET") {
    $_SESSION = [];
    session_destroy();
    redirect("/login");
}

// --- Home timeline (Trending / Following tabs, infinite scroll) ---------
if ($path === "/" && $method === "GET") {
    $user = requireLogin();
    $body = <<<HTML
        <h2>New post</h2>
        <form action="/form/post" method="post" enctype="multipart/form-data">
            <p><textarea name="body" rows="3" maxlength="255" placeholder="What is happening? (max 255 characters)" required></textarea></p>
            <p>Media (image up to 5 MB, video up to 20 MB): <input type="file" name="media"></p>
            <p>Schedule (optional): <input type="datetime-local" name="publish_at">
               <span class="meta">A future time keeps the post hidden until publish_scheduled.php runs.</span></p>
            <p><button type="submit">Post</button></p>
        </form>
        <h2>Timeline</h2>
        <p>
            <a href="#" class="tab active" data-tab="following">Following</a> |
            <a href="#" class="tab" data-tab="trending">Trending</a>
        </p>
        <div id="feed"></div>
        <p id="feed-status" class="meta"></p>
        <script>
        const feed = document.getElementById('feed');
        const status = document.getElementById('feed-status');
        let tab = 'following', offset = 0, loading = false, done = false;
        function esc(s){const d=document.createElement('div');d.textContent=s??'';return d.innerHTML;}
        function card(p){
            const media = p.media_path
                ? (p.media_type === 'video'
                    ? '<p><video class="media" src="/media/' + esc(p.media_path) + '" controls></video></p>'
                    : '<p><img class="media" src="/media/' + esc(p.media_path) + '" alt=""></p>') : '';
            const avatar = p.author_photo ? '<img class="avatar" src="/media/' + esc(p.author_photo) + '" alt="">' : '';
            return '<div class="post"><p>' + avatar + ' <strong>' + esc(p.display_name) + '</strong> ' +
                '<a href="/profile/' + esc(p.username) + '">@' + esc(p.username) + '</a> ' +
                '<span class="meta">' + esc(p.created_at) + '</span></p><p>' + esc(p.body).replace(/\\n/g,'<br>') + '</p>' +
                media + '<p><a href="/post/' + p.id + '">Open thread</a> · Likes: ' + p.like_count + ' · Replies: ' + p.reply_count + '</p></div>';
        }
        async function load(){
            if (loading || done) return;
            loading = true; status.textContent = 'Loading...';
            const res = await fetch('/api/timeline?tab=' + tab + '&offset=' + offset);
            const data = await res.json();
            data.posts.forEach(p => feed.insertAdjacentHTML('beforeend', card(p)));
            offset = data.next_offset;
            if (!data.has_more) { done = true; status.textContent = data.posts.length ? 'No more posts.' : status.textContent; }
            else status.textContent = '';
            if (offset === 20 && data.posts.length === 0) status.textContent = 'No posts yet.';
            loading = false;
        }
        window.addEventListener('scroll', () => {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 300) load();
        });
        document.querySelectorAll('.tab').forEach(a => a.addEventListener('click', ev => {
            ev.preventDefault();
            document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
            a.classList.add('active');
            tab = a.dataset.tab; offset = 0; done = false; feed.innerHTML = '';
            load();
        }));
        load();
        </script>
    HTML;
    page("Home", $body, $user);
    exit;
}

if ($path === "/api/timeline" && $method === "GET") {
    $user = currentUser();
    if ($user === null) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Login required."]);
        exit;
    }
    $tab = ($_GET["tab"] ?? "following") === "trending" ? "trending" : "following";
    $offset = max(0, (int) ($_GET["offset"] ?? 0));
    $posts = Post::timeline((int) $user["id"], $tab, $offset);
    $out = [];
    foreach ($posts as $post) {
        $out[] = [
            "id" => (int) $post["id"],
            "body" => $post["body"],
            "username" => $post["username"],
            "display_name" => $post["display_name"],
            "author_photo" => $post["author_photo"],
            "media_path" => $post["media_path"],
            "media_type" => $post["media_type"],
            "like_count" => (int) $post["like_count"],
            "reply_count" => Post::replyCount((int) $post["id"]),
            "created_at" => $post["created_at"],
        ];
    }
    header("Content-Type: application/json");
    echo json_encode([
        "posts" => $out,
        "next_offset" => $offset + count($out),
        "has_more" => count($out) === Post::PAGE_SIZE,
    ]);
    exit;
}

// --- Posts ---------------------------------------------------------------
if ($path === "/form/post" && $method === "POST") {
    $user = requireLogin();
    $body = trim((string) ($_POST["body"] ?? ""));
    if ($body === "" || mb_strlen($body) > Post::MAX_BODY_LENGTH) {
        flash("error", "A post needs 1-255 characters.");
        redirect("/");
    }
    $mediaPath = null;
    $mediaType = null;
    try {
        $media = Media::store($_FILES["media"] ?? ["error" => UPLOAD_ERR_NO_FILE]);
        if ($media !== null) {
            $mediaPath = $media["path"];
            $mediaType = $media["type"];
        }
    } catch (RuntimeException $ex) {
        flash("error", $ex->getMessage());
        redirect("/");
    }
    $publishAt = trim((string) ($_POST["publish_at"] ?? ""));
    $publishAt = $publishAt === "" ? null : str_replace("T", " ", $publishAt) . ":00";
    $id = Post::create((int) $user["id"], $body, null, $mediaPath, $mediaType, $publishAt);
    flash("ok", ($publishAt !== null && strtotime($publishAt) > time()) ? "Post scheduled." : "Posted.");
    redirect("/");
}

if (preg_match("#^/post/(\d+)$#", $path, $m) && $method === "GET") {
    $user = requireLogin();
    $post = Post::findById((int) $m[1]);
    if ($post === null || $post["status"] !== "published") {
        http_response_code(404);
        page("Not found", "<p>Post not found.</p>", $user);
        exit;
    }
    $id = (int) $post["id"];
    $tree = replyTreeHtml($post, $user, 0);
    $body = <<<HTML
        {$tree}
        <h3>Reply</h3>
        <form action="/form/post/{$id}/reply" method="post" enctype="multipart/form-data">
            <p><textarea name="body" rows="3" maxlength="255" required></textarea></p>
            <p>Media: <input type="file" name="media"></p>
            <p><button type="submit">Reply</button></p>
        </form>
    HTML;
    page("Post", $body, $user);
    exit;
}

if (preg_match("#^/form/post/(\d+)/reply$#", $path, $m) && $method === "POST") {
    $user = requireLogin();
    $parent = Post::findById((int) $m[1]);
    if ($parent === null) {
        http_response_code(404);
        page("Not found", "<p>Post not found.</p>", $user);
        exit;
    }
    $body = trim((string) ($_POST["body"] ?? ""));
    if ($body === "" || mb_strlen($body) > Post::MAX_BODY_LENGTH) {
        flash("error", "A reply needs 1-255 characters.");
        redirect("/post/" . (int) $parent["id"]);
    }
    $mediaPath = null;
    $mediaType = null;
    try {
        $media = Media::store($_FILES["media"] ?? ["error" => UPLOAD_ERR_NO_FILE]);
        if ($media !== null) {
            $mediaPath = $media["path"];
            $mediaType = $media["type"];
        }
    } catch (RuntimeException $ex) {
        flash("error", $ex->getMessage());
        redirect("/post/" . (int) $parent["id"]);
    }
    Post::create((int) $user["id"], $body, (int) $parent["id"], $mediaPath, $mediaType, null);
    redirect("/post/" . (int) $parent["id"]);
}

if (preg_match("#^/form/post/(\d+)/like$#", $path, $m) && $method === "POST") {
    $user = requireLogin();
    $post = Post::findById((int) $m[1]);
    if ($post !== null) {
        Post::toggleLike((int) $user["id"], (int) $post["id"]);
    }
    back();
}

if (preg_match("#^/form/post/(\d+)/delete$#", $path, $m) && $method === "POST") {
    $user = requireLogin();
    $post = Post::findById((int) $m[1]);
    if ($post === null || (int) $post["user_id"] !== (int) $user["id"]) {
        flash("error", "You can only delete your own posts.");
        redirect("/");
    }
    Post::deleteWithDescendants((int) $post["id"]);
    flash("ok", "Post deleted.");
    redirect("/");
}

// --- Profiles & follows ---------------------------------------------------
if (preg_match("#^/profile/([A-Za-z0-9_]+)$#", $path, $m) && $method === "GET") {
    $user = requireLogin();
    $profile = User::findByUsername($m[1]);
    if ($profile === null) {
        http_response_code(404);
        page("Not found", "<p>User not found.</p>", $user);
        exit;
    }
    $isSelf = (int) $profile["id"] === (int) $user["id"];
    $followers = User::followerCount((int) $profile["id"]);
    $following = User::followingCount((int) $profile["id"]);
    $followForm = "";
    if ($isSelf) {
        $followForm = "<p><a href=\"/profile/edit\">Edit profile</a></p>";
    } else {
        $pid = (int) $profile["id"];
        $action = User::isFollowing((int) $user["id"], $pid) ? "unfollow" : "follow";
        $label = $action === "follow" ? "Follow" : "Unfollow";
        $followForm = <<<HTML
        <form method="post" action="/form/{$action}/{$pid}" style="display:inline"><button type="submit">{$label}</button></form>
        <a href="/messages/{$pid}">Send message</a>
        HTML;
    }
    $postsHtml = "";
    foreach (Post::byUser((int) $profile["id"]) as $post) {
        $postsHtml .= postCardHtml($post, $user);
    }
    if ($postsHtml === "") {
        $postsHtml = "<p class=\"meta\">No posts yet.</p>";
    }
    $avatar = avatarHtml($profile);
    $name = e($profile["display_name"]);
    $username = e($profile["username"]);
    $age = $profile["age"] !== null ? "<p>Age: " . (int) $profile["age"] . "</p>" : "";
    $residence = $profile["residence"] ? "<p>Residence: " . e($profile["residence"]) . "</p>" : "";
    $hobbies = $profile["hobbies"] ? "<p>Hobbies: " . e($profile["hobbies"]) . "</p>" : "";
    $bio = $profile["bio"] ? "<p>" . nl2br(e($profile["bio"])) . "</p>" : "";
    $body = <<<HTML
        <h2>{$avatar} {$name} <span class="meta">@{$username}</span></h2>
        {$bio}{$age}{$residence}{$hobbies}
        <p>{$followers} followers · {$following} following</p>
        {$followForm}
        <h3>Posts</h3>
        {$postsHtml}
    HTML;
    page($profile["display_name"], $body, $user);
    exit;
}

if ($path === "/profile/edit" && $method === "GET") {
    $user = requireLogin();
    $name = e($user["display_name"]);
    $age = $user["age"] !== null ? (int) $user["age"] : "";
    $residence = e($user["residence"]);
    $hobbies = e($user["hobbies"]);
    $bio = e($user["bio"]);
    $body = <<<HTML
        <h2>Edit profile</h2>
        <form action="/form/profile" method="post" enctype="multipart/form-data">
            <p>Display name: <input type="text" name="display_name" value="{$name}" required></p>
            <p>Age: <input type="number" name="age" min="0" max="150" value="{$age}"></p>
            <p>Residence: <input type="text" name="residence" value="{$residence}"></p>
            <p>Hobbies: <input type="text" name="hobbies" value="{$hobbies}"></p>
            <p>Bio:<br><textarea name="bio" rows="4">{$bio}</textarea></p>
            <p>Photo (image up to 5 MB): <input type="file" name="photo"></p>
            <p><button type="submit">Save</button></p>
        </form>
    HTML;
    page("Edit profile", $body, $user);
    exit;
}

if ($path === "/form/profile" && $method === "POST") {
    $user = requireLogin();
    $photoPath = $user["photo_path"];
    try {
        $media = Media::store($_FILES["photo"] ?? ["error" => UPLOAD_ERR_NO_FILE], true);
        if ($media !== null) {
            $photoPath = $media["path"];
        }
    } catch (RuntimeException $ex) {
        flash("error", $ex->getMessage());
        redirect("/profile/edit");
    }
    $ageRaw = trim((string) ($_POST["age"] ?? ""));
    User::updateProfile((int) $user["id"], [
        "display_name" => trim((string) ($_POST["display_name"] ?? "")) ?: $user["display_name"],
        "age" => $ageRaw === "" ? null : max(0, min(150, (int) $ageRaw)),
        "residence" => trim((string) ($_POST["residence"] ?? "")) ?: null,
        "hobbies" => trim((string) ($_POST["hobbies"] ?? "")) ?: null,
        "bio" => trim((string) ($_POST["bio"] ?? "")) ?: null,
        "photo_path" => $photoPath,
    ]);
    flash("ok", "Profile updated.");
    redirect("/profile/" . $user["username"]);
}

if (preg_match("#^/form/(follow|unfollow)/(\d+)$#", $path, $m) && $method === "POST") {
    $user = requireLogin();
    $target = User::findById((int) $m[2]);
    if ($target !== null) {
        if ($m[1] === "follow") {
            User::follow((int) $user["id"], (int) $target["id"]);
        } else {
            User::unfollow((int) $user["id"], (int) $target["id"]);
        }
    }
    back();
}

// --- Direct messages (encrypted at rest) ----------------------------------
if ($path === "/messages" && $method === "GET") {
    $user = requireLogin();
    $list = "";
    foreach (Message::conversationPartners((int) $user["id"]) as $partner) {
        if ($partner["user"] === null) {
            continue;
        }
        $pid = (int) $partner["user"]["id"];
        $name = e($partner["user"]["display_name"]);
        $preview = e(mb_strimwidth($partner["last_body"], 0, 60, "..."));
        $at = e($partner["last_at"]);
        $list .= "<p><a href=\"/messages/{$pid}\"><strong>{$name}</strong></a> <span class=\"meta\">{$at}</span><br>{$preview}</p>";
    }
    if ($list === "") {
        $list = "<p class=\"meta\">No conversations yet.</p>";
    }
    $newList = "";
    foreach (User::allExcept((int) $user["id"]) as $other) {
        $oid = (int) $other["id"];
        $name = e($other["display_name"]);
        $uname = e($other["username"]);
        $newList .= "<li><a href=\"/messages/{$oid}\">{$name} (@{$uname})</a></li>";
    }
    $body = <<<HTML
        <h2>Conversations</h2>
        {$list}
        <h3>Start a new conversation</h3>
        <ul>{$newList}</ul>
    HTML;
    page("Messages", $body, $user);
    exit;
}

if (preg_match("#^/messages/(\d+)$#", $path, $m) && $method === "GET") {
    $user = requireLogin();
    $other = User::findById((int) $m[1]);
    if ($other === null || (int) $other["id"] === (int) $user["id"]) {
        http_response_code(404);
        page("Not found", "<p>User not found.</p>", $user);
        exit;
    }
    Notification::markDmFromRead((int) $user["id"], (int) $other["id"]);
    $thread = "";
    foreach (Message::thread((int) $user["id"], (int) $other["id"]) as $message) {
        $who = (int) $message["sender_id"] === (int) $user["id"] ? "You" : e($other["display_name"]);
        $text = nl2br(e($message["body"]));
        $at = e($message["created_at"]);
        $thread .= "<p><strong>{$who}</strong> <span class=\"meta\">{$at}</span><br>{$text}</p>";
    }
    if ($thread === "") {
        $thread = "<p class=\"meta\">No messages yet. Say hello!</p>";
    }
    $oid = (int) $other["id"];
    $name = e($other["display_name"]);
    $body = <<<HTML
        <h2>Messages with {$name}</h2>
        {$thread}
        <form action="/form/message/{$oid}" method="post">
            <p><textarea name="body" rows="3" maxlength="1000" required></textarea></p>
            <p><button type="submit">Send</button></p>
        </form>
        <p class="meta">Messages are encrypted with AES-256-GCM before they are stored.</p>
    HTML;
    page("Conversation", $body, $user);
    exit;
}

if (preg_match("#^/form/message/(\d+)$#", $path, $m) && $method === "POST") {
    $user = requireLogin();
    $other = User::findById((int) $m[1]);
    $body = trim((string) ($_POST["body"] ?? ""));
    if ($other === null || $body === "" || mb_strlen($body) > Message::MAX_BODY_LENGTH) {
        flash("error", "A message needs 1-1000 characters.");
        redirect("/messages");
    }
    Message::send((int) $user["id"], (int) $other["id"], $body);
    redirect("/messages/" . (int) $other["id"]);
}

// --- Notifications ----------------------------------------------------------
if ($path === "/notifications" && $method === "GET") {
    $user = requireLogin();
    $items = "";
    foreach (Notification::forUser((int) $user["id"]) as $notification) {
        $nid = (int) $notification["id"];
        $text = e(Notification::describe($notification));
        $at = e($notification["created_at"]);
        $unread = (int) $notification["is_read"] === 0 ? " <strong>(new)</strong>" : "";
        $items .= "<p><a href=\"/notifications/{$nid}\">{$text}</a> <span class=\"meta\">{$at}</span>{$unread}</p>";
    }
    if ($items === "") {
        $items = "<p class=\"meta\">No notifications yet.</p>";
    }
    page("Notifications", "<h2>Notifications</h2>" . $items, $user);
    exit;
}

if (preg_match("#^/notifications/(\d+)$#", $path, $m) && $method === "GET") {
    $user = requireLogin();
    $notification = Notification::findById((int) $m[1]);
    if ($notification === null || (int) $notification["user_id"] !== (int) $user["id"]) {
        http_response_code(404);
        page("Not found", "<p>Notification not found.</p>", $user);
        exit;
    }
    Notification::markRead((int) $notification["id"]);
    redirect(Notification::targetUrl($notification));
}

http_response_code(404);
page("Not found", "<p>Page not found.</p>", currentUser());
