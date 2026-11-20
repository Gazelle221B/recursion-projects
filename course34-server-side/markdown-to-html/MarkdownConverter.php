<?php
declare(strict_types=1);

/**
 * A small Markdown-to-HTML converter covering the common subset:
 * headings, bold, italic, inline code, code blocks, links, lists,
 * and paragraphs. The course suggests using an existing library such
 * as Parsedown (via Composer); this built-in converter keeps the
 * project runnable without external dependencies.
 */
class MarkdownConverter
{
    public static function convert(string $markdown): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $markdown));
        $html = [];
        $inCodeBlock = false;
        $inList = false;

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), "```")) {
                if ($inCodeBlock) {
                    $html[] = "</code></pre>";
                    $inCodeBlock = false;
                } else {
                    if ($inList) {
                        $html[] = "</ul>";
                        $inList = false;
                    }
                    $html[] = "<pre><code>";
                    $inCodeBlock = true;
                }
                continue;
            }
            if ($inCodeBlock) {
                $html[] = htmlspecialchars($line);
                continue;
            }

            if (preg_match("/^(#{1,6})\\s+(.*)$/", $line, $m)) {
                if ($inList) {
                    $html[] = "</ul>";
                    $inList = false;
                }
                $level = strlen($m[1]);
                $html[] = sprintf("<h%d>%s</h%d>", $level, self::inline($m[2]), $level);
                continue;
            }

            if (preg_match("/^\\s*[-*]\\s+(.*)$/", $line, $m)) {
                if (!$inList) {
                    $html[] = "<ul>";
                    $inList = true;
                }
                $html[] = "<li>" . self::inline($m[1]) . "</li>";
                continue;
            }

            if ($inList) {
                $html[] = "</ul>";
                $inList = false;
            }

            if (trim($line) === "") {
                continue;
            }
            $html[] = "<p>" . self::inline($line) . "</p>";
        }

        if ($inCodeBlock) {
            $html[] = "</code></pre>";
        }
        if ($inList) {
            $html[] = "</ul>";
        }
        return implode("\n", $html);
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text);
        $text = preg_replace("/`([^`]+)`/", "<code>$1</code>", $text);
        $text = preg_replace("/\\*\\*([^*]+)\\*\\*/", "<strong>$1</strong>", $text);
        $text = preg_replace("/\\*([^*]+)\\*/", "<em>$1</em>", $text);
        $text = preg_replace("/\\[([^\\]]+)\\]\\(([^)]+)\\)/", '<a href="$2">$1</a>', $text);
        return $text;
    }
}
