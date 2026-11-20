<?php
declare(strict_types=1);

/**
 * Encodes PlantUML source text for a PlantUML server URL.
 * The text is UTF-8 encoded, deflated, and then encoded with
 * PlantUML's custom base64 alphabet.
 */
class PlantUmlEncoder
{
    public static function encode(string $text): string
    {
        $compressed = gzdeflate($text, 9);
        if ($compressed === false) {
            return "";
        }
        return self::encode64($compressed);
    }

    public static function url(string $text, string $format, string $server = "https://www.plantuml.com/plantuml"): string
    {
        return sprintf("%s/%s/%s", rtrim($server, "/"), $format, self::encode($text));
    }

    private static function encode64(string $bytes): string
    {
        $alpha = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_";
        $out = "";
        $len = strlen($bytes);
        for ($i = 0; $i < $len; $i += 3) {
            $b1 = ord($bytes[$i]);
            $b2 = $i + 1 < $len ? ord($bytes[$i + 1]) : 0;
            $b3 = $i + 2 < $len ? ord($bytes[$i + 2]) : 0;
            $out .= $alpha[$b1 >> 2];
            $out .= $alpha[(($b1 & 0x3) << 4) | ($b2 >> 4)];
            $out .= $alpha[(($b2 & 0xF) << 2) | ($b3 >> 6)];
            $out .= $alpha[$b3 & 0x3F];
        }
        return $out;
    }
}
