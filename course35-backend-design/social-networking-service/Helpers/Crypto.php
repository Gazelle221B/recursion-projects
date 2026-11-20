<?php
declare(strict_types=1);

namespace SocialNetworking\Helpers;

use SocialNetworking\Database\Database;

/**
 * Encrypts direct messages at rest with OpenSSL AES-256-GCM.
 * The key comes from DM_ENCRYPTION_KEY in .env (a base64-encoded
 * 32-byte key; any other string is hashed with SHA-256 to 32 bytes).
 * Ciphertext, IV and authentication tag are stored base64-encoded
 * and the plaintext is recovered when a conversation is read.
 */
class Crypto
{
    private const CIPHER = "aes-256-gcm";

    private static function key(): string
    {
        $configured = Database::env("DM_ENCRYPTION_KEY");
        if (is_string($configured) && $configured !== "") {
            $raw = base64_decode($configured, true);
            if ($raw !== false && strlen($raw) === 32) {
                return $raw;
            }
            return hash("sha256", $configured, true);
        }
        // Development fallback only — always set DM_ENCRYPTION_KEY in .env.
        return hash("sha256", "dev-only-change-me", true);
    }

    /** @return array{ciphertext: string, iv: string, tag: string} base64 values */
    public static function encrypt(string $plaintext): array
    {
        $iv = random_bytes(12);
        $tag = "";
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new \RuntimeException("Encryption failed.");
        }
        return [
            "ciphertext" => base64_encode($ciphertext),
            "iv" => base64_encode($iv),
            "tag" => base64_encode($tag),
        ];
    }

    public static function decrypt(string $ciphertextB64, string $ivB64, string $tagB64): string
    {
        $plaintext = openssl_decrypt(
            base64_decode($ciphertextB64),
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            base64_decode($ivB64),
            base64_decode($tagB64)
        );
        return $plaintext === false ? "" : $plaintext;
    }
}
