<?php
declare(strict_types=1);

namespace EmailVerification\Helpers;

use EmailVerification\Database\Database;

/**
 * Sends the verification email. In production this is where PHPMailer
 * (installed via Composer) sends through an SMTP relay such as Gmail;
 * the course's sendVerificationEmail() is implemented here with the
 * same signature. So the app also runs locally with no mail server,
 * every message is written to storage/outbox/ as an .eml-style file,
 * and PHP's mail() is used as well when MAIL_DELIVERY=mail is set.
 */
class Mailer
{
    public static function sendVerificationEmail(string $to, string $username, string $verificationUrl): void
    {
        $subject = "Verify your email address";
        $body = "Hello {$username},\n\n"
            . "Please verify your email address by opening this link (valid for 30 minutes):\n\n"
            . $verificationUrl . "\n\n"
            . "If you did not register, please ignore this email.\n";

        $dir = dirname(__DIR__) . "/storage/outbox";
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . "/" . date("YmdHis") . "_" . substr(hash("sha256", $to . $verificationUrl), 0, 8) . ".txt";
        file_put_contents($file, "To: {$to}\nSubject: {$subject}\n\n{$body}");

        if (Database::env("MAIL_DELIVERY") === "mail") {
            @mail($to, $subject, $body, "From: no-reply@example.com");
        }
    }
}
