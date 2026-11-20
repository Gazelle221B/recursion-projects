<?php
declare(strict_types=1);

namespace EcommerceStripe\Models;

use EcommerceStripe\Database\Database;

/**
 * Products are created ONLY in the Stripe Dashboard and synced into
 * this table as drafts by the webhook (see upsertFromStripe). The
 * admin completes the editorial fields (slug, description, title,
 * category, tags, release date, creator, SEO tags) and publishes
 * the product with the release button.
 */
class Product
{
    public static function findById(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne("SELECT * FROM products WHERE slug = ?", [$slug]);
    }

    public static function findByStripeId(string $stripeProductId): ?array
    {
        return Database::fetchOne("SELECT * FROM products WHERE stripe_product_id = ?", [$stripeProductId]);
    }

    /**
     * Creates or updates the local copy of a Stripe product from a
     * webhook event object. Expected object shape (as sent by Stripe,
     * and used by the documented demo payloads):
     *   {id, name, description, payment_link,
     *    default_price: {id, unit_amount, currency},
     *    metadata: {type, category, inventory}}
     * On create, editorial fields are prefilled from Stripe and the
     * status is 'draft'. On update, only Stripe-owned fields (price,
     * payment link, inventory) are synced, so admin edits survive.
     */
    public static function upsertFromStripe(array $object): int
    {
        $stripeId = (string) ($object["id"] ?? "");
        if ($stripeId === "") {
            throw new \InvalidArgumentException("Stripe product id is missing.");
        }
        $price = is_array($object["default_price"] ?? null) ? $object["default_price"] : [];
        $metadata = is_array($object["metadata"] ?? null) ? $object["metadata"] : [];
        $existing = self::findByStripeId($stripeId);

        if ($existing === null) {
            Database::execute(
                "INSERT INTO products (stripe_product_id, stripe_price_id, payment_link, title, description,
                                        type, category, price_cents, currency, inventory, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')",
                [
                    $stripeId,
                    $price["id"] ?? null,
                    $object["payment_link"] ?? null,
                    (string) ($object["name"] ?? ""),
                    $object["description"] ?? null,
                    ($metadata["type"] ?? "physical") === "digital" ? "digital" : "physical",
                    $metadata["category"] ?? null,
                    (int) ($price["unit_amount"] ?? 0),
                    (string) ($price["currency"] ?? "jpy"),
                    (int) ($metadata["inventory"] ?? 0),
                ]
            );
            return Database::lastInsertId();
        }

        Database::execute(
            "UPDATE products SET stripe_price_id = ?, payment_link = ?, price_cents = ?, currency = ?, inventory = ? WHERE id = ?",
            [
                $price["id"] ?? $existing["stripe_price_id"],
                $object["payment_link"] ?? $existing["payment_link"],
                (int) ($price["unit_amount"] ?? $existing["price_cents"]),
                (string) ($price["currency"] ?? $existing["currency"]),
                isset($metadata["inventory"]) ? (int) $metadata["inventory"] : (int) $existing["inventory"],
                (int) $existing["id"],
            ]
        );
        return (int) $existing["id"];
    }

    /** Removes the local product (used for product.deleted and admin deletes). */
    public static function deleteLocal(int $id): void
    {
        Database::execute("DELETE FROM favorites WHERE product_id = ?", [$id]);
        Database::execute("DELETE FROM reviews WHERE product_id = ?", [$id]);
        Database::execute("UPDATE order_items SET product_id = NULL WHERE product_id = ?", [$id]);
        Database::execute("DELETE FROM products WHERE id = ?", [$id]);
    }

    /** Saves the admin-completed editorial fields. */
    public static function updateEditorial(int $id, array $fields): void
    {
        Database::execute(
            "UPDATE products SET title = ?, slug = ?, description = ?, type = ?, category = ?, tags = ?,
                    release_date = ?, creator = ?, seo_tags = ?, inventory = ?, digital_file = ? WHERE id = ?",
            [
                $fields["title"], $fields["slug"], $fields["description"], $fields["type"],
                $fields["category"], $fields["tags"], $fields["release_date"], $fields["creator"],
                $fields["seo_tags"], $fields["inventory"], $fields["digital_file"], $id,
            ]
        );
    }

    public static function publish(int $id): void
    {
        Database::execute("UPDATE products SET status = 'published' WHERE id = ?", [$id]);
    }

    public static function byStatus(string $status): array
    {
        return Database::fetchAll("SELECT * FROM products WHERE status = ? ORDER BY created_at DESC", [$status]);
    }

    /** Published products with storefront search + filters applied. */
    public static function searchPublished(array $filters): array
    {
        $where = ["status = 'published'"];
        $params = [];
        if (($filters["q"] ?? "") !== "") {
            $where[] = "(title LIKE ? OR description LIKE ?)";
            $like = "%" . $filters["q"] . "%";
            $params[] = $like;
            $params[] = $like;
        }
        if (($filters["category"] ?? "") !== "") {
            $where[] = "category = ?";
            $params[] = $filters["category"];
        }
        if (($filters["creator"] ?? "") !== "") {
            $where[] = "creator = ?";
            $params[] = $filters["creator"];
        }
        if (($filters["from"] ?? "") !== "") {
            $where[] = "release_date >= ?";
            $params[] = $filters["from"];
        }
        if (($filters["to"] ?? "") !== "") {
            $where[] = "release_date <= ?";
            $params[] = $filters["to"];
        }
        $sql = "SELECT * FROM products WHERE " . implode(" AND ", $where)
            . " ORDER BY release_date DESC, created_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function distinctCategories(): array
    {
        $rows = Database::fetchAll(
            "SELECT DISTINCT category FROM products WHERE status = 'published' AND category IS NOT NULL AND category != '' ORDER BY category"
        );
        return array_map(static fn (array $r): string => (string) $r["category"], $rows);
    }

    public static function distinctCreators(): array
    {
        $rows = Database::fetchAll(
            "SELECT DISTINCT creator FROM products WHERE status = 'published' AND creator IS NOT NULL AND creator != '' ORDER BY creator"
        );
        return array_map(static fn (array $r): string => (string) $r["creator"], $rows);
    }

    // --- Reviews ---------------------------------------------------------

    public static function addReview(int $productId, int $userId, int $rating, ?string $comment): void
    {
        $existing = Database::fetchOne(
            "SELECT id FROM reviews WHERE product_id = ? AND user_id = ?",
            [$productId, $userId]
        );
        if ($existing !== null) {
            Database::execute(
                "UPDATE reviews SET rating = ?, comment = ? WHERE id = ?",
                [$rating, $comment, (int) $existing["id"]]
            );
            return;
        }
        Database::execute(
            "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)",
            [$productId, $userId, $rating, $comment]
        );
    }

    public static function reviews(int $productId): array
    {
        return Database::fetchAll(
            "SELECT r.*, u.username FROM reviews r JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? ORDER BY r.created_at DESC",
            [$productId]
        );
    }

    public static function averageRating(int $productId): ?float
    {
        $row = Database::fetchOne("SELECT AVG(rating) AS a FROM reviews WHERE product_id = ?", [$productId]);
        return $row === null || $row["a"] === null ? null : (float) $row["a"];
    }

    // --- Favorites / wishlist ---------------------------------------------

    public static function toggleFavorite(int $userId, int $productId): bool
    {
        $existing = Database::fetchOne(
            "SELECT user_id FROM favorites WHERE user_id = ? AND product_id = ?",
            [$userId, $productId]
        );
        if ($existing !== null) {
            Database::execute("DELETE FROM favorites WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
            return false;
        }
        Database::execute("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)", [$userId, $productId]);
        return true;
    }

    public static function isFavorite(int $userId, int $productId): bool
    {
        return Database::fetchOne(
            "SELECT user_id FROM favorites WHERE user_id = ? AND product_id = ?",
            [$userId, $productId]
        ) !== null;
    }

    public static function favoritesOf(int $userId): array
    {
        return Database::fetchAll(
            "SELECT p.* FROM favorites f JOIN products p ON p.id = f.product_id
             WHERE f.user_id = ? ORDER BY f.created_at DESC",
            [$userId]
        );
    }
}
