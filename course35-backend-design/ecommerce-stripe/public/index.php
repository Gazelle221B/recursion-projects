<?php
declare(strict_types=1);

// E-Commerce Webapp with Stripe Sync, as specified in Recursion
// course 35, lesson 1308. Products are created in the Stripe
// Dashboard and synced here as drafts via webhook; the admin
// completes and publishes them. Checkout runs through Stripe
// Checkout (or the built-in demo checkout when no keys are set).
// Run locally with: php -S localhost:8001 -t public public/index.php

spl_autoload_register(function (string $class): void {
    $prefix = "EcommerceStripe\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use EcommerceStripe\Helpers\Mailer;
use EcommerceStripe\Helpers\StripeClient;
use EcommerceStripe\Helpers\ZipWriter;
use EcommerceStripe\Models\Order;
use EcommerceStripe\Models\Product;
use EcommerceStripe\Models\User;

session_start();

$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$baseUrl = ($_SERVER["REQUEST_SCHEME"] ?? "http") . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost");

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

function money(int $cents, string $currency = "jpy"): string
{
    return strtoupper($currency) . " " . number_format($cents);
}

function stars(?float $average): string
{
    if ($average === null) {
        return "No ratings yet";
    }
    $full = (int) round($average);
    return str_repeat("★", $full) . str_repeat("☆", 5 - $full) . " (" . number_format($average, 1) . ")";
}

/** @return array<int, int> product id => quantity */
function cart(): array
{
    return $_SESSION["cart"] ?? [];
}

function page(string $title, string $body, ?array $user): void
{
    $cartCount = array_sum(cart());
    $links = "<a href=\"/\">Store</a> | <a href=\"/cart\">Cart ({$cartCount})</a>";
    if ($user !== null) {
        $links .= " | <a href=\"/wishlist\">Wishlist</a> | <a href=\"/orders\">Orders</a> | <a href=\"/account\">Account</a>";
        if ((int) $user["is_admin"] === 1) {
            $links .= " | <a href=\"/admin\">Admin</a>";
        }
        $links .= " | <a href=\"/logout\">Logout</a>";
    } else {
        $links .= " | <a href=\"/login\">Login</a> | <a href=\"/register\">Register</a>";
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
        <title>{$titleHtml} - E-Commerce</title>
        <style>
            body{font-family:sans-serif;margin:2em;max-width:900px}
            .error{color:#b00020}.ok{color:#0a7a28}
            .product{border:1px solid #ccc;border-radius:8px;padding:1em;margin:1em 0}
            .meta{color:#666;font-size:.85em}
            table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:.4em .8em}
            textarea{width:100%}
        </style>
    </head>
    <body>
        <h1><a href="/">E-Commerce Webapp</a></h1>
        <nav>{$links}</nav>
        {$messages}
        {$body}
    </body>
    </html>
    HTML;
}

function productCardHtml(array $product, ?array $user): string
{
    $slug = e($product["slug"] ?? "");
    $title = e($product["title"]);
    $price = money((int) $product["price_cents"], $product["currency"]);
    $category = e($product["category"] ?? "");
    $creator = e($product["creator"] ?? "");
    $rating = stars(Product::averageRating((int) $product["id"]));
    $stock = (int) $product["inventory"] > 0 ? "" : " <strong>Out of stock</strong>";
    $type = $product["type"] === "digital" ? "Digital" : "Physical";
    return <<<HTML
    <div class="product">
        <h3><a href="/product/{$slug}">{$title}</a></h3>
        <p>{$price} · {$type} · {$category} · by {$creator}{$stock}</p>
        <p>{$rating}</p>
    </div>
    HTML;
}

function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        redirect("/login");
    }
    return $user;
}

function requireAdmin(): array
{
    $user = requireLogin();
    if ((int) $user["is_admin"] !== 1) {
        http_response_code(403);
        page("Forbidden", "<p>Admin access only.</p>", $user);
        exit;
    }
    return $user;
}

// --- Stripe webhook -----------------------------------------------------
// Products are created only in the Stripe Dashboard; this endpoint
// syncs them into the local DB as drafts and settles orders.
if ($path === "/webhook/stripe" && $method === "POST") {
    $payload = (string) file_get_contents("php://input");
    try {
        $event = StripeClient::constructEvent($payload, $_SERVER["HTTP_STRIPE_SIGNATURE"] ?? null);
    } catch (RuntimeException $ex) {
        http_response_code(400);
        header("Content-Type: application/json");
        echo json_encode(["error" => $ex->getMessage()]);
        exit;
    }
    $object = $event["data"]["object"] ?? [];
    switch ($event["type"]) {
        case "product.created":
        case "product.updated":
            if (($object["active"] ?? true) === false) {
                $local = Product::findByStripeId((string) ($object["id"] ?? ""));
                if ($local !== null) {
                    Product::deleteLocal((int) $local["id"]);
                }
            } else {
                Product::upsertFromStripe($object);
            }
            break;
        case "product.deleted":
            $local = Product::findByStripeId((string) ($object["id"] ?? ""));
            if ($local !== null) {
                Product::deleteLocal((int) $local["id"]);
            }
            break;
        case "checkout.session.completed":
            if (isset($object["id"])) {
                Order::completeBySessionId((string) $object["id"]);
            }
            break;
    }
    header("Content-Type: application/json");
    echo json_encode(["received" => true]);
    exit;
}

// --- Registration / login -------------------------------------------------
if ($path === "/register" && $method === "GET") {
    page("Register", <<<HTML
        <h2>Register</h2>
        <form action="/form/register" method="post">
            <p><input type="text" name="username" placeholder="Username" required></p>
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
    $id = User::create($username, $email, password_hash($password, PASSWORD_DEFAULT));
    $_SESSION["user_id"] = $id;
    session_regenerate_id(true);
    redirect("/");
}

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

// --- Storefront -----------------------------------------------------------
if ($path === "/" && $method === "GET") {
    $user = currentUser();
    $filters = [
        "q" => trim((string) ($_GET["q"] ?? "")),
        "category" => (string) ($_GET["category"] ?? ""),
        "creator" => (string) ($_GET["creator"] ?? ""),
        "from" => (string) ($_GET["from"] ?? ""),
        "to" => (string) ($_GET["to"] ?? ""),
    ];
    $categoryOptions = "<option value=\"\">All categories</option>";
    foreach (Product::distinctCategories() as $category) {
        $selected = $filters["category"] === $category ? " selected" : "";
        $categoryOptions .= "<option value=\"" . e($category) . "\"{$selected}>" . e($category) . "</option>";
    }
    $creatorOptions = "<option value=\"\">All creators</option>";
    foreach (Product::distinctCreators() as $creator) {
        $selected = $filters["creator"] === $creator ? " selected" : "";
        $creatorOptions .= "<option value=\"" . e($creator) . "\"{$selected}>" . e($creator) . "</option>";
    }
    $cards = "";
    foreach (Product::searchPublished($filters) as $product) {
        $cards .= productCardHtml($product, $user);
    }
    if ($cards === "") {
        $cards = "<p class=\"meta\">No products match your search.</p>";
    }
    $q = e($filters["q"]);
    $from = e($filters["from"]);
    $to = e($filters["to"]);
    $body = <<<HTML
        <h2>Products</h2>
        <form method="get" action="/">
            <p>
                <input type="text" name="q" value="{$q}" placeholder="Search title or description">
                <select name="category">{$categoryOptions}</select>
                <select name="creator">{$creatorOptions}</select>
            </p>
            <p>Release date: <input type="date" name="from" value="{$from}"> to <input type="date" name="to" value="{$to}">
               <button type="submit">Search</button></p>
        </form>
        {$cards}
    HTML;
    page("Store", $body, $user);
    exit;
}

if (preg_match("#^/product/([A-Za-z0-9\-]+)$#", $path, $m) && $method === "GET") {
    $user = currentUser();
    $product = Product::findBySlug($m[1]);
    if ($product === null || $product["status"] !== "published") {
        http_response_code(404);
        page("Not found", "<p>Product not found.</p>", $user);
        exit;
    }
    $pid = (int) $product["id"];
    $title = e($product["title"]);
    $description = nl2br(e($product["description"] ?? ""));
    $price = money((int) $product["price_cents"], $product["currency"]);
    $paymentLink = $product["payment_link"] ? "<p><a href=\"" . e($product["payment_link"]) . "\">Stripe payment link</a></p>" : "";
    $inStock = (int) $product["inventory"] > 0;
    $cartForm = $inStock
        ? "<form method=\"post\" action=\"/form/cart/add/{$pid}\"><input type=\"number\" name=\"quantity\" value=\"1\" min=\"1\"> <button type=\"submit\">Add to cart</button></form>"
        : "<p><strong>Out of stock</strong> — this product cannot be purchased right now.</p>";
    $favForm = "";
    $reviewForm = "";
    if ($user !== null) {
        $favLabel = Product::isFavorite((int) $user["id"], $pid) ? "Remove from favorites" : "Add to favorites";
        $favForm = "<form method=\"post\" action=\"/form/product/{$pid}/favorite\"><button type=\"submit\">{$favLabel}</button></form>";
        $reviewForm = <<<HTML
        <h3>Write a review</h3>
        <form method="post" action="/form/product/{$pid}/review">
            <p>Rating:
                <select name="rating">
                    <option value="5">★★★★★</option><option value="4">★★★★</option>
                    <option value="3">★★★</option><option value="2">★★</option><option value="1">★</option>
                </select></p>
            <p><textarea name="comment" rows="3" placeholder="Comment (optional)"></textarea></p>
            <p><button type="submit">Post review</button></p>
        </form>
        HTML;
    }
    $download = "";
    if ($product["type"] === "digital"
        && Order::userPurchasedProduct($user !== null ? (int) $user["id"] : null, $_SESSION["purchased_order_ids"] ?? [], $pid)) {
        $download = "<p><a href=\"/download/{$pid}\">Download (zip)</a></p>";
    }
    $reviewsHtml = "";
    foreach (Product::reviews($pid) as $review) {
        $stars = str_repeat("★", (int) $review["rating"]) . str_repeat("☆", 5 - (int) $review["rating"]);
        $comment = $review["comment"] ? "<br>" . nl2br(e($review["comment"])) : "";
        $reviewsHtml .= "<p>{$stars} by " . e($review["username"]) . $comment . "</p>";
    }
    if ($reviewsHtml === "") {
        $reviewsHtml = "<p class=\"meta\">No reviews yet.</p>";
    }
    $rating = stars(Product::averageRating($pid));
    $meta = "<p class=\"meta\">Category: " . e($product["category"] ?? "") . " · Creator: " . e($product["creator"] ?? "")
        . " · Released: " . e($product["release_date"] ?? "") . " · Tags: " . e($product["tags"] ?? "") . "</p>";
    $body = <<<HTML
        <h2>{$title}</h2>
        {$meta}
        <p>{$price} · {$rating}</p>
        <p>{$description}</p>
        {$paymentLink}
        {$cartForm}
        {$favForm}
        {$download}
        <h3>Reviews</h3>
        {$reviewsHtml}
        {$reviewForm}
    HTML;
    page($product["title"], $body, $user);
    exit;
}

if (preg_match("#^/form/product/(\d+)/review$#", $path, $m) && $method === "POST") {
    $user = requireLogin();
    $product = Product::findById((int) $m[1]);
    if ($product === null) {
        http_response_code(404);
        page("Not found", "<p>Product not found.</p>", $user);
        exit;
    }
    $rating = max(1, min(5, (int) ($_POST["rating"] ?? 5)));
    $comment = trim((string) ($_POST["comment"] ?? ""));
    Product::addReview((int) $product["id"], (int) $user["id"], $rating, $comment === "" ? null : $comment);
    redirect("/product/" . $product["slug"]);
}

if (preg_match("#^/form/product/(\d+)/favorite$#", $path, $m) && $method === "POST") {
    $user = requireLogin();
    $product = Product::findById((int) $m[1]);
    if ($product !== null) {
        Product::toggleFavorite((int) $user["id"], (int) $product["id"]);
    }
    redirect($product !== null && $product["slug"] ? "/product/" . $product["slug"] : "/");
}

// --- Cart -------------------------------------------------------------------
if (preg_match("#^/form/cart/add/(\d+)$#", $path, $m) && $method === "POST") {
    $product = Product::findById((int) $m[1]);
    if ($product === null || $product["status"] !== "published") {
        flash("error", "Product not available.");
        redirect("/");
    }
    $quantity = max(1, (int) ($_POST["quantity"] ?? 1));
    $cart = cart();
    $newQuantity = ($cart[(int) $product["id"]] ?? 0) + $quantity;
    if ($newQuantity > (int) $product["inventory"]) {
        flash("error", "Not enough stock available.");
        redirect("/product/" . $product["slug"]);
    }
    $cart[(int) $product["id"]] = $newQuantity;
    $_SESSION["cart"] = $cart;
    redirect("/cart");
}

if (preg_match("#^/form/cart/update/(\d+)$#", $path, $m) && $method === "POST") {
    $cart = cart();
    $quantity = (int) ($_POST["quantity"] ?? 0);
    if ($quantity <= 0) {
        unset($cart[(int) $m[1]]);
    } else {
        $cart[(int) $m[1]] = $quantity;
    }
    $_SESSION["cart"] = $cart;
    redirect("/cart");
}

if (preg_match("#^/form/cart/remove/(\d+)$#", $path, $m) && $method === "POST") {
    $cart = cart();
    unset($cart[(int) $m[1]]);
    $_SESSION["cart"] = $cart;
    redirect("/cart");
}

if ($path === "/cart" && $method === "GET") {
    $user = currentUser();
    $rows = "";
    $total = 0;
    foreach (cart() as $productId => $quantity) {
        $product = Product::findById((int) $productId);
        if ($product === null) {
            continue;
        }
        $lineTotal = (int) $product["price_cents"] * $quantity;
        $total += $lineTotal;
        $title = e($product["title"]);
        $price = money((int) $product["price_cents"], $product["currency"]);
        $line = money($lineTotal, $product["currency"]);
        $rows .= <<<HTML
        <tr>
            <td>{$title}</td><td>{$price}</td>
            <td><form method="post" action="/form/cart/update/{$productId}"><input type="number" name="quantity" value="{$quantity}" min="0"> <button type="submit">Update</button></form></td>
            <td>{$line}</td>
            <td><form method="post" action="/form/cart/remove/{$productId}"><button type="submit">Remove</button></form></td>
        </tr>
        HTML;
    }
    if ($rows === "") {
        $body = "<h2>Cart</h2><p class=\"meta\">Your cart is empty.</p>";
    } else {
        $totalText = money($total);
        $body = <<<HTML
        <h2>Cart</h2>
        <table><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr>{$rows}</table>
        <p><strong>Total: {$totalText}</strong></p>
        <p><a href="/checkout">Proceed to checkout</a></p>
        HTML;
    }
    page("Cart", $body, $user);
    exit;
}

// --- Checkout -----------------------------------------------------------------
if ($path === "/checkout" && $method === "GET") {
    $user = currentUser();
    if (cart() === []) {
        flash("error", "Your cart is empty.");
        redirect("/cart");
    }
    $email = $user !== null ? e($user["email"]) : "";
    $note = $user === null ? "<p class=\"meta\">Guest checkout: your purchase is identified by the email you enter.</p>" : "";
    $body = <<<HTML
        <h2>Checkout</h2>
        {$note}
        <form method="post" action="/form/checkout">
            <p>Email: <input type="email" name="email" value="{$email}" required></p>
            <p><button type="submit">Pay with Stripe Checkout</button></p>
        </form>
        <p class="meta">Receipt and invoice emails are sent by Stripe. Physical orders get a
        tracking-number email from this store once shipped.</p>
    HTML;
    page("Checkout", $body, $user);
    exit;
}

if ($path === "/form/checkout" && $method === "POST") {
    $user = currentUser();
    $email = trim((string) ($_POST["email"] ?? ""));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash("error", "Please enter a valid email address.");
        redirect("/checkout");
    }
    $items = [];
    $currency = "jpy";
    foreach (cart() as $productId => $quantity) {
        $product = Product::findById((int) $productId);
        if ($product === null || $product["status"] !== "published") {
            continue;
        }
        if ($quantity > (int) $product["inventory"]) {
            flash("error", "Not enough stock for " . $product["title"] . ".");
            redirect("/cart");
        }
        $currency = $product["currency"];
        $items[] = [
            "product_id" => (int) $product["id"],
            "title" => $product["title"],
            "price_cents" => (int) $product["price_cents"],
            "quantity" => $quantity,
        ];
    }
    if ($items === []) {
        flash("error", "Your cart is empty.");
        redirect("/cart");
    }
    $orderId = Order::create($user !== null ? (int) $user["id"] : null, $user === null ? $email : null, $items, $currency);
    $lineItems = array_map(static fn (array $item): array => [
        "name" => $item["title"],
        "unit_amount" => $item["price_cents"],
        "currency" => $currency,
        "quantity" => $item["quantity"],
    ], $items);
    $session = StripeClient::createCheckoutSession(
        $orderId,
        $lineItems,
        $email,
        $baseUrl . "/checkout/success?order=" . $orderId,
        $baseUrl . "/cart"
    );
    Order::attachSession($orderId, $session["id"]);
    $_SESSION["cart"] = [];
    redirect($session["url"]);
}

if (preg_match("#^/checkout/demo/([A-Za-z0-9_]+)$#", $path, $m) && $method === "GET") {
    $user = currentUser();
    $order = Order::findBySessionId($m[1]);
    if ($order === null) {
        http_response_code(404);
        page("Not found", "<p>Checkout session not found.</p>", $user);
        exit;
    }
    $rows = "";
    foreach (Order::items((int) $order["id"]) as $item) {
        $rows .= "<tr><td>" . e($item["title"]) . "</td><td>" . (int) $item["quantity"] . "</td><td>"
            . money((int) $item["price_cents"] * (int) $item["quantity"], $order["currency"]) . "</td></tr>";
    }
    $total = money((int) $order["total_cents"], $order["currency"]);
    $sid = e($m[1]);
    $body = <<<HTML
        <h2>Demo Checkout</h2>
        <p class="meta">No Stripe keys are configured, so this local page simulates Stripe Checkout.
        With test keys set, you would be redirected to Stripe instead.</p>
        <table><tr><th>Item</th><th>Qty</th><th>Amount</th></tr>{$rows}</table>
        <p><strong>Total: {$total}</strong></p>
        <form method="post" action="/form/checkout/demo/{$sid}/pay">
            <p><button type="submit">Pay (demo)</button></p>
        </form>
    HTML;
    page("Demo Checkout", $body, $user);
    exit;
}

if (preg_match("#^/form/checkout/demo/([A-Za-z0-9_]+)/pay$#", $path, $m) && $method === "POST") {
    $order = Order::completeBySessionId($m[1]);
    if ($order === null) {
        http_response_code(404);
        page("Not found", "<p>Checkout session not found.</p>", currentUser());
        exit;
    }
    $_SESSION["purchased_order_ids"][] = (int) $order["id"];
    redirect("/checkout/success?order=" . (int) $order["id"]);
}

if ($path === "/checkout/success" && $method === "GET") {
    $user = currentUser();
    $order = Order::findById((int) ($_GET["order"] ?? 0));
    $allowed = $order !== null && (
        ($user !== null && (int) ($order["user_id"] ?? 0) === (int) $user["id"])
        || in_array((int) $order["id"], $_SESSION["purchased_order_ids"] ?? [], true)
    );
    if (!$allowed) {
        http_response_code(404);
        page("Not found", "<p>Order not found.</p>", $user);
        exit;
    }
    $rows = "";
    foreach (Order::items((int) $order["id"]) as $item) {
        $rows .= "<tr><td>" . e($item["title"]) . "</td><td>" . (int) $item["quantity"] . "</td><td>"
            . money((int) $item["price_cents"] * (int) $item["quantity"], $order["currency"]) . "</td></tr>";
    }
    $total = money((int) $order["total_cents"], $order["currency"]);
    $status = e($order["status"]);
    $body = <<<HTML
        <h2>Thank you for your purchase</h2>
        <p>Order #{$order["id"]} — status: {$status}</p>
        <table><tr><th>Item</th><th>Qty</th><th>Amount</th></tr>{$rows}</table>
        <p><strong>Total: {$total}</strong></p>
        <p class="meta">Digital products can be downloaded from their product pages.
        A receipt email is sent by Stripe.</p>
    HTML;
    page("Purchase complete", $body, $user);
    exit;
}

// --- Orders / account -----------------------------------------------------------
if ($path === "/orders" && $method === "GET") {
    $user = requireLogin();
    $rows = "";
    foreach (Order::forUser((int) $user["id"]) as $order) {
        $rows .= "<tr><td><a href=\"/orders/" . (int) $order["id"] . "\">#" . (int) $order["id"] . "</a></td>"
            . "<td>" . money((int) $order["total_cents"], $order["currency"]) . "</td>"
            . "<td>" . e($order["status"]) . "</td><td>" . e($order["created_at"]) . "</td></tr>";
    }
    if ($rows === "") {
        $rows = "<tr><td colspan=\"4\">No orders yet.</td></tr>";
    }
    page("Orders", "<h2>Purchase history</h2><table><tr><th>Order</th><th>Total</th><th>Status</th><th>Date</th></tr>{$rows}</table>", $user);
    exit;
}

if (preg_match("#^/orders/(\d+)$#", $path, $m) && $method === "GET") {
    $user = requireLogin();
    $order = Order::findById((int) $m[1]);
    if ($order === null || (int) ($order["user_id"] ?? 0) !== (int) $user["id"]) {
        http_response_code(404);
        page("Not found", "<p>Order not found.</p>", $user);
        exit;
    }
    $rows = "";
    foreach (Order::items((int) $order["id"]) as $item) {
        $download = "";
        if ($item["product_id"] !== null) {
            $product = Product::findById((int) $item["product_id"]);
            if ($product !== null && $product["type"] === "digital") {
                $download = " <a href=\"/download/" . (int) $product["id"] . "\">Download (zip)</a>";
            }
        }
        $rows .= "<tr><td>" . e($item["title"]) . $download . "</td><td>" . (int) $item["quantity"] . "</td><td>"
            . money((int) $item["price_cents"] * (int) $item["quantity"], $order["currency"]) . "</td></tr>";
    }
    $tracking = $order["tracking_number"] ? "<p>Tracking number: <strong>" . e($order["tracking_number"]) . "</strong></p>" : "";
    $total = money((int) $order["total_cents"], $order["currency"]);
    $status = e($order["status"]);
    $email = e(Order::buyerEmail($order) ?? "");
    $body = <<<HTML
        <h2>Receipt — Order #{$order["id"]}</h2>
        <p>Status: {$status} · Buyer: {$email}</p>
        {$tracking}
        <table><tr><th>Item</th><th>Qty</th><th>Amount</th></tr>{$rows}</table>
        <p><strong>Total: {$total}</strong></p>
    HTML;
    page("Receipt", $body, $user);
    exit;
}

if ($path === "/account" && $method === "GET") {
    $user = requireLogin();
    $username = e($user["username"]);
    $email = e($user["email"]);
    $role = (int) $user["is_admin"] === 1 ? "Admin" : "Customer";
    $shareUrl = $baseUrl . "/wishlist/" . e($user["wishlist_token"]);
    $body = <<<HTML
        <h2>Account</h2>
        <p>Username: {$username}<br>Email: {$email}<br>Role: {$role}</p>
        <p><a href="/orders">Purchase history & receipts</a> · <a href="/wishlist">Wishlist</a></p>
        <p>Public wishlist URL: <a href="{$shareUrl}">{$shareUrl}</a></p>
    HTML;
    page("Account", $body, $user);
    exit;
}

// --- Wishlist ---------------------------------------------------------------------
if ($path === "/wishlist" && $method === "GET") {
    $user = requireLogin();
    $cards = "";
    foreach (Product::favoritesOf((int) $user["id"]) as $product) {
        $cards .= productCardHtml($product, $user);
    }
    if ($cards === "") {
        $cards = "<p class=\"meta\">Your wishlist is empty. Add products with the favorites button.</p>";
    }
    $shareUrl = $baseUrl . "/wishlist/" . e($user["wishlist_token"]);
    $body = "<h2>My wishlist</h2><p>Share it publicly: <a href=\"{$shareUrl}\">{$shareUrl}</a></p>" . $cards;
    page("Wishlist", $body, $user);
    exit;
}

if (preg_match("#^/wishlist/([A-Za-z0-9]+)$#", $path, $m) && $method === "GET") {
    $viewer = currentUser();
    $owner = User::findByWishlistToken($m[1]);
    if ($owner === null) {
        http_response_code(404);
        page("Not found", "<p>Wishlist not found.</p>", $viewer);
        exit;
    }
    $cards = "";
    foreach (Product::favoritesOf((int) $owner["id"]) as $product) {
        if ($product["status"] === "published") {
            $cards .= productCardHtml($product, $viewer);
        }
    }
    if ($cards === "") {
        $cards = "<p class=\"meta\">This wishlist is empty.</p>";
    }
    page("Wishlist", "<h2>" . e($owner["username"]) . "'s wishlist</h2>" . $cards, $viewer);
    exit;
}

// --- Digital download (buyers only, zip generated on the fly) -----------------------
if (preg_match("#^/download/(\d+)$#", $path, $m) && $method === "GET") {
    $user = currentUser();
    $product = Product::findById((int) $m[1]);
    $allowed = $product !== null && $product["type"] === "digital"
        && Order::userPurchasedProduct($user !== null ? (int) $user["id"] : null, $_SESSION["purchased_order_ids"] ?? [], (int) $product["id"]);
    if (!$allowed || empty($product["digital_file"])) {
        http_response_code(403);
        page("Forbidden", "<p>This download is available only to customers who purchased the product.</p>", $user);
        exit;
    }
    $file = dirname(__DIR__) . "/storage/products/" . basename((string) $product["digital_file"]);
    if (!is_file($file)) {
        http_response_code(404);
        page("Not found", "<p>The product file is missing.</p>", $user);
        exit;
    }
    $zip = ZipWriter::build([basename($file) => (string) file_get_contents($file)]);
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=\"" . ($product["slug"] ?: "product") . ".zip\"");
    header("Content-Length: " . strlen($zip));
    echo $zip;
    exit;
}

// --- Admin ----------------------------------------------------------------------------
if ($path === "/admin" && $method === "GET") {
    $admin = requireAdmin();
    $section = static function (string $status) use ($admin): string {
        $rows = "";
        foreach (Product::byStatus($status) as $product) {
            $pid = (int) $product["id"];
            $publish = $status === "draft"
                ? "<form method=\"post\" action=\"/form/admin/products/{$pid}/publish\" style=\"display:inline\"><button type=\"submit\">Release (publish)</button></form>"
                : "";
            $rows .= "<tr><td>" . e($product["title"]) . "</td><td>" . e($product["stripe_product_id"]) . "</td>"
                . "<td>" . money((int) $product["price_cents"], $product["currency"]) . "</td>"
                . "<td>" . (int) $product["inventory"] . "</td>"
                . "<td><a href=\"/admin/products/{$pid}/edit\">Edit</a> {$publish} "
                . "<form method=\"post\" action=\"/form/admin/products/{$pid}/delete\" style=\"display:inline\" onsubmit=\"return confirm('Delete on both sides?')\"><button type=\"submit\">Delete</button></form></td></tr>";
        }
        return $rows === "" ? "<tr><td colspan=\"5\">None.</td></tr>" : $rows;
    };
    $drafts = $section("draft");
    $published = $section("published");
    $orderRows = "";
    foreach (Order::all() as $order) {
        $oid = (int) $order["id"];
        $buyer = e(Order::buyerEmail($order) ?? "-");
        $trackingForm = $order["status"] === "processing"
            ? "<form method=\"post\" action=\"/form/admin/orders/{$oid}/tracking\"><input type=\"text\" name=\"tracking_number\" placeholder=\"Tracking number\" required> <button type=\"submit\">Ship & complete</button></form>"
            : e($order["tracking_number"] ?? "");
        $orderRows .= "<tr><td>#{$oid}</td><td>{$buyer}</td><td>" . money((int) $order["total_cents"], $order["currency"])
            . "</td><td>" . e($order["status"]) . "</td><td>{$trackingForm}</td></tr>";
    }
    if ($orderRows === "") {
        $orderRows = "<tr><td colspan=\"5\">No orders yet.</td></tr>";
    }
    $queueRows = "";
    foreach (\EcommerceStripe\Database\Database::fetchAll("SELECT * FROM stripe_sync_queue ORDER BY created_at DESC LIMIT 20") as $row) {
        $queueRows .= "<tr><td>" . e($row["action"]) . "</td><td>" . e($row["stripe_product_id"]) . "</td><td>" . e($row["created_at"]) . "</td></tr>";
    }
    $queueSection = $queueRows === "" ? "" : "<h3>Stripe sync queue (demo mode)</h3><table><tr><th>Action</th><th>Stripe product</th><th>Queued at</th></tr>{$queueRows}</table>";
    $demoNote = StripeClient::demoMode() ? "<p class=\"meta\">Demo mode: no Stripe keys configured. Product deletions are queued locally instead of calling Stripe.</p>" : "";
    $body = <<<HTML
        <h2>Admin dashboard</h2>
        {$demoNote}
        <h3>Draft products (synced from Stripe)</h3>
        <table><tr><th>Title</th><th>Stripe ID</th><th>Price</th><th>Stock</th><th>Actions</th></tr>{$drafts}</table>
        <h3>Published products</h3>
        <table><tr><th>Title</th><th>Stripe ID</th><th>Price</th><th>Stock</th><th>Actions</th></tr>{$published}</table>
        <h3>Orders</h3>
        <table><tr><th>Order</th><th>Buyer</th><th>Total</th><th>Status</th><th>Tracking</th></tr>{$orderRows}</table>
        {$queueSection}
    HTML;
    page("Admin", $body, $admin);
    exit;
}

if (preg_match("#^/admin/products/(\d+)/edit$#", $path, $m) && $method === "GET") {
    $admin = requireAdmin();
    $product = Product::findById((int) $m[1]);
    if ($product === null) {
        http_response_code(404);
        page("Not found", "<p>Product not found.</p>", $admin);
        exit;
    }
    $pid = (int) $product["id"];
    $typePhysical = $product["type"] === "physical" ? " selected" : "";
    $typeDigital = $product["type"] === "digital" ? " selected" : "";
    $title = e($product["title"]);
    $slug = e($product["slug"] ?? "");
    $description = e($product["description"] ?? "");
    $category = e($product["category"] ?? "");
    $tags = e($product["tags"] ?? "");
    $release = e($product["release_date"] ?? "");
    $creator = e($product["creator"] ?? "");
    $seo = e($product["seo_tags"] ?? "");
    $inventory = (int) $product["inventory"];
    $digitalFile = e($product["digital_file"] ?? "");
    $price = money((int) $product["price_cents"], $product["currency"]);
    $body = <<<HTML
        <h2>Edit product</h2>
        <p class="meta">Stripe product: {$product["stripe_product_id"]} · Price (from Stripe): {$price}</p>
        <form method="post" action="/form/admin/products/{$pid}">
            <p>Title: <input type="text" name="title" value="{$title}" required></p>
            <p>URL slug: <input type="text" name="slug" value="{$slug}" placeholder="auto-generated from title when empty"></p>
            <p>Type: <select name="type"><option value="physical"{$typePhysical}>Physical</option><option value="digital"{$typeDigital}>Digital</option></select></p>
            <p>Description:<br><textarea name="description" rows="5">{$description}</textarea></p>
            <p>Category: <input type="text" name="category" value="{$category}"></p>
            <p>Tags: <input type="text" name="tags" value="{$tags}"></p>
            <p>Release date: <input type="date" name="release_date" value="{$release}"></p>
            <p>Creator: <input type="text" name="creator" value="{$creator}"></p>
            <p>SEO tags: <input type="text" name="seo_tags" value="{$seo}"></p>
            <p>Inventory: <input type="number" name="inventory" value="{$inventory}" min="0">
               <span class="meta">Normally set in the Stripe Dashboard and synced by webhook; editable here for local administration.</span></p>
            <p>Digital file (name of a file in storage/products/, digital products only):
               <input type="text" name="digital_file" value="{$digitalFile}"></p>
            <p><button type="submit">Save</button></p>
        </form>
    HTML;
    page("Edit product", $body, $admin);
    exit;
}

if (preg_match("#^/form/admin/products/(\d+)$#", $path, $m) && $method === "POST") {
    $admin = requireAdmin();
    $product = Product::findById((int) $m[1]);
    if ($product === null) {
        http_response_code(404);
        page("Not found", "<p>Product not found.</p>", $admin);
        exit;
    }
    $title = trim((string) ($_POST["title"] ?? ""));
    $slug = trim((string) ($_POST["slug"] ?? ""));
    if ($slug === "") {
        $slug = trim(preg_replace('/[^a-z0-9]+/', "-", strtolower($title)) ?? "", "-");
        if ($slug === "") {
            $slug = "product-" . (int) $product["id"];
        }
    }
    $clash = Product::findBySlug($slug);
    if ($clash !== null && (int) $clash["id"] !== (int) $product["id"]) {
        flash("error", "That slug is already used by another product.");
        redirect("/admin/products/" . (int) $product["id"] . "/edit");
    }
    Product::updateEditorial((int) $product["id"], [
        "title" => $title ?: $product["title"],
        "slug" => $slug,
        "description" => trim((string) ($_POST["description"] ?? "")) ?: null,
        "type" => ($_POST["type"] ?? "physical") === "digital" ? "digital" : "physical",
        "category" => trim((string) ($_POST["category"] ?? "")) ?: null,
        "tags" => trim((string) ($_POST["tags"] ?? "")) ?: null,
        "release_date" => trim((string) ($_POST["release_date"] ?? "")) ?: null,
        "creator" => trim((string) ($_POST["creator"] ?? "")) ?: null,
        "seo_tags" => trim((string) ($_POST["seo_tags"] ?? "")) ?: null,
        "inventory" => max(0, (int) ($_POST["inventory"] ?? 0)),
        "digital_file" => trim((string) ($_POST["digital_file"] ?? "")) ?: null,
    ]);
    flash("ok", "Product saved.");
    redirect("/admin");
}

if (preg_match("#^/form/admin/products/(\d+)/publish$#", $path, $m) && $method === "POST") {
    $admin = requireAdmin();
    $product = Product::findById((int) $m[1]);
    if ($product === null || $product["title"] === "" || empty($product["slug"])) {
        flash("error", "Complete the title and URL slug before publishing.");
        redirect("/admin");
    }
    Product::publish((int) $product["id"]);
    flash("ok", "Product published.");
    redirect("/admin");
}

if (preg_match("#^/form/admin/products/(\d+)/delete$#", $path, $m) && $method === "POST") {
    $admin = requireAdmin();
    $product = Product::findById((int) $m[1]);
    if ($product !== null) {
        // Deletion syncs to both sides: Stripe first (queued in demo
        // mode), then the local copy is removed.
        StripeClient::deleteProduct((string) $product["stripe_product_id"]);
        Product::deleteLocal((int) $product["id"]);
        flash("ok", "Product deleted on both sides.");
    }
    redirect("/admin");
}

if (preg_match("#^/form/admin/orders/(\d+)/tracking$#", $path, $m) && $method === "POST") {
    $admin = requireAdmin();
    $order = Order::findById((int) $m[1]);
    $tracking = trim((string) ($_POST["tracking_number"] ?? ""));
    if ($order === null || $tracking === "") {
        flash("error", "Tracking number is required.");
        redirect("/admin");
    }
    Order::setTracking((int) $order["id"], $tracking);
    $email = Order::buyerEmail($order);
    if ($email !== null) {
        Mailer::sendTrackingEmail($email, $order, Order::items((int) $order["id"]), $tracking);
    }
    flash("ok", "Order #{$order["id"]} marked as shipped/completed; tracking email written to the outbox.");
    redirect("/admin");
}

http_response_code(404);
page("Not found", "<p>Page not found.</p>", currentUser());
