<?php
declare(strict_types=1);

namespace EcommerceStripe\Helpers;

use EcommerceStripe\Database\Database;
use RuntimeException;

/**
 * Thin Stripe abstraction layer.
 *
 * DEMO MODE: when STRIPE_SECRET_KEY is empty (or starts with
 * "sk_demo"), no real HTTP calls are made:
 *  - createCheckoutSession() stores a local session row and returns
 *    a local demo checkout URL (/checkout/demo/{sessionId}).
 *  - deleteProduct() queues the deletion in stripe_sync_queue, so
 *    the "sync to Stripe" intent is visible in the admin dashboard.
 *  - constructEvent() accepts unsigned test payloads when
 *    STRIPE_WEBHOOK_SECRET is also empty.
 *
 * With real test keys set (per environment: local / staging /
 * production each has its own .env, keys and webhook endpoint),
 * checkout sessions are created through the Stripe API and webhook
 * signatures are verified with hash_hmac + hash_equals, exactly
 * like Stripe's official verification scheme.
 */
class StripeClient
{
    public static function demoMode(): bool
    {
        $key = Database::env("STRIPE_SECRET_KEY");
        return $key === null || $key === "" || str_starts_with((string) $key, "sk_demo");
    }

    /**
     * Creates a Checkout session for an order.
     *
     * @param array $lineItems list of ['name'=>..., 'unit_amount'=>int, 'currency'=>..., 'quantity'=>int]
     * @return array{id: string, url: string}
     */
    public static function createCheckoutSession(
        int $orderId,
        array $lineItems,
        string $customerEmail,
        string $successUrl,
        string $cancelUrl
    ): array {
        if (self::demoMode()) {
            $sessionId = "cs_demo_" . bin2hex(random_bytes(12));
            Database::execute(
                "INSERT INTO checkout_sessions (session_id, order_id, payload, status) VALUES (?, ?, ?, 'open')",
                [$sessionId, $orderId, json_encode([
                    "line_items" => $lineItems,
                    "customer_email" => $customerEmail,
                    "success_url" => $successUrl,
                    "cancel_url" => $cancelUrl,
                ])]
            );
            return ["id" => $sessionId, "url" => "/checkout/demo/" . $sessionId];
        }

        // Real mode: POST to the Stripe API (test keys).
        $params = [
            "mode" => "payment",
            "customer_email" => $customerEmail,
            "success_url" => $successUrl,
            "cancel_url" => $cancelUrl,
            "metadata[order_id]" => (string) $orderId,
        ];
        foreach ($lineItems as $i => $item) {
            $params["line_items[{$i}][quantity]"] = (string) $item["quantity"];
            $params["line_items[{$i}][price_data][currency]"] = $item["currency"];
            $params["line_items[{$i}][price_data][unit_amount]"] = (string) $item["unit_amount"];
            $params["line_items[{$i}][price_data][product_data][name]"] = $item["name"];
        }
        $response = self::apiRequest("POST", "https://api.stripe.com/v1/checkout/sessions", $params);
        if (!isset($response["id"], $response["url"])) {
            throw new RuntimeException("Stripe did not return a checkout session.");
        }
        Database::execute(
            "INSERT INTO checkout_sessions (session_id, order_id, payload, status) VALUES (?, ?, ?, 'open')",
            [$response["id"], $orderId, json_encode($response)]
        );
        return ["id" => $response["id"], "url" => $response["url"]];
    }

    /** Deletes a product on the Stripe side (queued in demo mode). */
    public static function deleteProduct(string $stripeProductId): void
    {
        if (self::demoMode()) {
            Database::execute(
                "INSERT INTO stripe_sync_queue (action, stripe_product_id, payload) VALUES ('product.delete', ?, ?)",
                [$stripeProductId, json_encode(["queued_in_demo_mode" => true])]
            );
            return;
        }
        self::apiRequest("DELETE", "https://api.stripe.com/v1/products/" . urlencode($stripeProductId), []);
    }

    /**
     * Verifies and decodes a webhook payload.
     * With STRIPE_WEBHOOK_SECRET set, the Stripe-Signature header
     * (t=...,v1=...) is checked: hash_hmac('sha256', "{t}.{payload}",
     * secret) compared with hash_equals, with a 5-minute tolerance.
     * Without a secret (demo mode), the payload is accepted as a
     * documented test payload.
     *
     * @return array the decoded event
     */
    public static function constructEvent(string $payload, ?string $signatureHeader): array
    {
        $secret = Database::env("STRIPE_WEBHOOK_SECRET");
        if (is_string($secret) && $secret !== "") {
            if ($signatureHeader === null || $signatureHeader === "") {
                throw new RuntimeException("Missing Stripe-Signature header.");
            }
            $timestamp = null;
            $signatures = [];
            foreach (explode(",", $signatureHeader) as $part) {
                [$k, $v] = array_pad(explode("=", trim($part), 2), 2, "");
                if ($k === "t") {
                    $timestamp = (int) $v;
                } elseif ($k === "v1") {
                    $signatures[] = $v;
                }
            }
            if ($timestamp === null || $signatures === [] || abs(time() - $timestamp) > 300) {
                throw new RuntimeException("Invalid or stale Stripe-Signature header.");
            }
            $expected = hash_hmac("sha256", $timestamp . "." . $payload, $secret);
            $valid = false;
            foreach ($signatures as $signature) {
                if (hash_equals($expected, $signature)) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                throw new RuntimeException("Webhook signature verification failed.");
            }
        }
        $event = json_decode($payload, true);
        if (!is_array($event) || !isset($event["type"])) {
            throw new RuntimeException("Invalid webhook payload.");
        }
        return $event;
    }

    /** Minimal HTTPS form request against the Stripe API (real mode only). */
    private static function apiRequest(string $method, string $url, array $params): array
    {
        $context = stream_context_create([
            "http" => [
                "method" => $method,
                "header" => "Authorization: Bearer " . Database::env("STRIPE_SECRET_KEY") . "\r\n"
                    . "Content-Type: application/x-www-form-urlencoded\r\n",
                "content" => http_build_query($params),
                "ignore_errors" => true,
                "timeout" => 30,
            ],
        ]);
        $body = file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RuntimeException("Could not reach the Stripe API.");
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || isset($decoded["error"])) {
            throw new RuntimeException("Stripe API error: " . ($decoded["error"]["message"] ?? "unknown"));
        }
        return $decoded;
    }
}
