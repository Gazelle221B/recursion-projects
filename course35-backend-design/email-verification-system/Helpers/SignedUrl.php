<?php
declare(strict_types=1);

namespace EmailVerification\Helpers;

use EmailVerification\Database\Database;

/**
 * Signed URLs as taught in the course: the URL (without the signature)
 * is signed with HMAC-SHA256 and verified with hash_equals. An
 * "expiration" query parameter bounds how long the link stays valid.
 */
class SignedUrl
{
    public static function sign(string $url): string
    {
        $secret = (string) (Database::env("SIGNATURE_SECRET_KEY") ?? "dev-secret-change-me");
        // The signature covers path + query only, so validation does not
        // depend on the scheme/host the link happens to be opened through.
        $parts = parse_url($url);
        $signed = ($parts["path"] ?? "") . (isset($parts["query"]) ? "?" . $parts["query"] : "");
        $signature = hash_hmac("sha256", $signed, $secret);
        $separator = str_contains($url, "?") ? "&" : "?";
        return $url . $separator . "signature=" . $signature;
    }

    /** Build the email-verification URL for a user, valid for $ttlSeconds. */
    public static function verificationUrl(string $baseUrl, int $userId, string $email, int $ttlSeconds = 1800): string
    {
        $params = [
            "id" => $userId,
            "user" => hash("sha256", $email),
            "expiration" => time() + $ttlSeconds,
        ];
        return self::sign($baseUrl . "/verify/email?" . http_build_query($params));
    }

    /**
     * Validate a full request URL that carries a signature parameter.
     * Returns "ok", "invalid" (signature mismatch / missing parts) or
     * "expired".
     */
    public static function validate(string $fullUrl): string
    {
        $parts = parse_url($fullUrl);
        parse_str($parts["query"] ?? "", $query);
        if (!isset($query["signature"])) {
            return "invalid";
        }
        $signature = (string) $query["signature"];
        unset($query["signature"]);
        $base = ($parts["path"] ?? "") . "?" . http_build_query($query);
        $secret = (string) (Database::env("SIGNATURE_SECRET_KEY") ?? "dev-secret-change-me");
        $expected = hash_hmac("sha256", $base, $secret);
        if (!hash_equals($expected, $signature)) {
            return "invalid";
        }
        if (isset($query["expiration"]) && (int) $query["expiration"] < time()) {
            return "expired";
        }
        return "ok";
    }
}
