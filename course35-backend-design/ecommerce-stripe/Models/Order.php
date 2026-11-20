<?php
declare(strict_types=1);

namespace EcommerceStripe\Models;

use EcommerceStripe\Database\Database;

/**
 * Orders. Lifecycle: pending (checkout created) -> paid by the
 * checkout.session.completed webhook (or the demo pay action) ->
 * 'processing' while a physical order waits for its tracking
 * number, then 'completed' once the admin enters it. Digital-only
 * orders go straight to 'completed' on payment.
 */
class Order
{
    /** @param array $items list of ['product_id'=>?int, 'title'=>string, 'price_cents'=>int, 'quantity'=>int] */
    public static function create(?int $userId, ?string $guestEmail, array $items, string $currency): int
    {
        $total = 0;
        foreach ($items as $item) {
            $total += (int) $item["price_cents"] * (int) $item["quantity"];
        }
        Database::execute(
            "INSERT INTO orders (user_id, guest_email, status, total_cents, currency) VALUES (?, ?, 'pending', ?, ?)",
            [$userId, $guestEmail, $total, $currency]
        );
        $orderId = Database::lastInsertId();
        foreach ($items as $item) {
            Database::execute(
                "INSERT INTO order_items (order_id, product_id, title, price_cents, quantity) VALUES (?, ?, ?, ?, ?)",
                [$orderId, $item["product_id"], $item["title"], (int) $item["price_cents"], (int) $item["quantity"]]
            );
        }
        return $orderId;
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$id]);
    }

    public static function findBySessionId(string $sessionId): ?array
    {
        return Database::fetchOne("SELECT * FROM orders WHERE stripe_checkout_session_id = ?", [$sessionId]);
    }

    public static function attachSession(int $orderId, string $sessionId): void
    {
        Database::execute("UPDATE orders SET stripe_checkout_session_id = ? WHERE id = ?", [$sessionId, $orderId]);
    }

    public static function items(int $orderId): array
    {
        return Database::fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$orderId]);
    }

    public static function forUser(int $userId): array
    {
        return Database::fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
    }

    public static function all(int $limit = 100): array
    {
        return Database::fetchAll("SELECT * FROM orders ORDER BY created_at DESC LIMIT ?", [$limit]);
    }

    public static function buyerEmail(array $order): ?string
    {
        if (!empty($order["guest_email"])) {
            return $order["guest_email"];
        }
        if (!empty($order["user_id"])) {
            $user = User::findById((int) $order["user_id"]);
            return $user["email"] ?? null;
        }
        return null;
    }

    /**
     * Marks an order paid after checkout.session.completed.
     * Decrements inventory and closes the local session row.
     * Idempotent: an already-paid order is returned unchanged.
     */
    public static function completeBySessionId(string $sessionId): ?array
    {
        $order = self::findBySessionId($sessionId);
        if ($order === null) {
            return null;
        }
        Database::execute("UPDATE checkout_sessions SET status = 'complete' WHERE session_id = ?", [$sessionId]);
        if ($order["status"] !== "pending") {
            return $order;
        }
        $items = self::items((int) $order["id"]);
        $hasPhysical = false;
        foreach ($items as $item) {
            if ($item["product_id"] !== null) {
                $product = Product::findById((int) $item["product_id"]);
                if ($product !== null) {
                    if ($product["type"] === "physical") {
                        $hasPhysical = true;
                    }
                    $newInventory = max(0, (int) $product["inventory"] - (int) $item["quantity"]);
                    Database::execute("UPDATE products SET inventory = ? WHERE id = ?", [$newInventory, (int) $product["id"]]);
                }
            }
        }
        $status = $hasPhysical ? "processing" : "completed";
        Database::execute("UPDATE orders SET status = ? WHERE id = ?", [$status, (int) $order["id"]]);
        return self::findById((int) $order["id"]);
    }

    /** Admin enters the tracking number: processing -> completed. */
    public static function setTracking(int $orderId, string $trackingNumber): void
    {
        Database::execute(
            "UPDATE orders SET tracking_number = ?, status = 'completed' WHERE id = ?",
            [$trackingNumber, $orderId]
        );
    }

    /** Whether the given buyer (user id and/or purchased-order session list) may download a product. */
    public static function userPurchasedProduct(?int $userId, array $sessionOrderIds, int $productId): bool
    {
        $orderIds = $sessionOrderIds;
        if ($userId !== null) {
            $rows = Database::fetchAll(
                "SELECT id FROM orders WHERE user_id = ? AND status IN ('processing', 'completed')",
                [$userId]
            );
            foreach ($rows as $row) {
                $orderIds[] = (int) $row["id"];
            }
        }
        if ($orderIds === []) {
            return false;
        }
        $placeholders = implode(",", array_fill(0, count($orderIds), "?"));
        $row = Database::fetchOne(
            "SELECT oi.id FROM order_items oi JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_id = ? AND oi.order_id IN ({$placeholders}) AND o.status IN ('processing', 'completed')",
            [$productId, ...array_values(array_unique($orderIds))]
        );
        return $row !== null;
    }
}
