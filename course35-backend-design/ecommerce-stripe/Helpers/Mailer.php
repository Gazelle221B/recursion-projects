<?php
declare(strict_types=1);

namespace EcommerceStripe\Helpers;

use EcommerceStripe\Database\Database;

/**
 * Sends the tracking-number email for physical orders. (Receipt and
 * invoice emails are handled by Stripe itself, per the course spec.)
 * Following the same pattern as the sibling projects, every message
 * is written to storage/outbox/ so the flow works without a mail
 * server, and PHP's mail() is used as well when MAIL_DELIVERY=mail.
 */
class Mailer
{
    public static function sendTrackingEmail(string $to, array $order, array $items, string $trackingNumber): void
    {
        $lines = [];
        foreach ($items as $item) {
            $lines[] = "- {$item["title"]} x{$item["quantity"]}";
        }
        $subject = "Your order #{$order["id"]} has been shipped";
        $body = "Thank you for your purchase.\n\n"
            . "Your order #{$order["id"]} is on its way.\n"
            . "Tracking number: {$trackingNumber}\n\n"
            . "Items:\n" . implode("\n", $lines) . "\n";

        $dir = dirname(__DIR__) . "/storage/outbox";
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . "/" . date("YmdHis") . "_tracking_order_" . (int) $order["id"] . ".txt";
        file_put_contents($file, "To: {$to}\nSubject: {$subject}\n\n{$body}");

        if (Database::env("MAIL_DELIVERY") === "mail") {
            @mail($to, $subject, $body, "From: no-reply@example.com");
        }
    }
}
