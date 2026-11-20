<?php
declare(strict_types=1);

// Seeder: creates an admin user, a demo customer, and three demo
// products. The products go through Product::upsertFromStripe() —
// exactly the code path the Stripe webhook uses — so they land in
// the DB as drafts, ready for the admin to complete and publish.
// Usage: php Database/seed.php

spl_autoload_register(function (string $class): void {
    $prefix = "EcommerceStripe\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = dirname(__DIR__) . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use EcommerceStripe\Models\Product;
use EcommerceStripe\Models\User;

if (User::findByEmail("admin@example.com") === null) {
    User::create("admin", "admin@example.com", password_hash("Admin123!", PASSWORD_DEFAULT), true);
}
if (User::findByEmail("customer@example.com") === null) {
    User::create("customer", "customer@example.com", password_hash("Customer1!", PASSWORD_DEFAULT));
}

$demoProducts = [
    [
        "id" => "prod_demo_keyboard",
        "name" => "Demo Mechanical Keyboard",
        "description" => "A hot-swappable mechanical keyboard (demo product).",
        "payment_link" => "https://buy.stripe.com/demo_keyboard",
        "default_price" => ["id" => "price_demo_keyboard", "unit_amount" => 12800, "currency" => "jpy"],
        "metadata" => ["type" => "physical", "category" => "Electronics", "inventory" => "10"],
    ],
    [
        "id" => "prod_demo_mouse",
        "name" => "Demo Wireless Mouse",
        "description" => "A silent wireless mouse (demo product, starts out of stock).",
        "payment_link" => "https://buy.stripe.com/demo_mouse",
        "default_price" => ["id" => "price_demo_mouse", "unit_amount" => 5980, "currency" => "jpy"],
        "metadata" => ["type" => "physical", "category" => "Electronics", "inventory" => "0"],
    ],
    [
        "id" => "prod_demo_cookbook",
        "name" => "Demo PHP Cookbook (Digital)",
        "description" => "A sample digital cookbook delivered as a zip download (demo product).",
        "payment_link" => "https://buy.stripe.com/demo_cookbook",
        "default_price" => ["id" => "price_demo_cookbook", "unit_amount" => 2980, "currency" => "jpy"],
        "metadata" => ["type" => "digital", "category" => "Books", "inventory" => "999"],
    ],
];

foreach ($demoProducts as $payload) {
    Product::upsertFromStripe($payload);
}

// The digital demo product's content file. The admin normally sets
// the file name in the product edit form; the seeder fills it in so
// the download flow works out of the box.
$dir = dirname(__DIR__) . "/storage/products";
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
file_put_contents($dir . "/demo-cookbook.txt", "Demo PHP Cookbook\n\nChapter 1: PDO and prepared statements.\n(This is demo content for the digital download flow.)\n");
$cookbook = Product::findByStripeId("prod_demo_cookbook");
if ($cookbook !== null && empty($cookbook["digital_file"])) {
    \EcommerceStripe\Database\Database::execute(
        "UPDATE products SET digital_file = 'demo-cookbook.txt' WHERE id = ?",
        [(int) $cookbook["id"]]
    );
}

echo "Seeded admin (admin@example.com / Admin123!), customer (customer@example.com / Customer1!)\n";
echo "and 3 demo products as drafts. Log in as admin to complete and publish them.\n";
