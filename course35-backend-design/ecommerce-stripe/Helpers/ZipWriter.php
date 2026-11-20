<?php
declare(strict_types=1);

namespace EcommerceStripe\Helpers;

/**
 * Minimal ZIP writer (stored / no compression) so digital products
 * can be delivered as .zip downloads without depending on the
 * zip extension. Produces a standard ZIP archive readable by any
 * unzip tool.
 */
class ZipWriter
{
    /** @param array<string, string> $files file name => file contents */
    public static function build(array $files): string
    {
        $data = "";
        $central = "";
        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $offset = strlen($data);

            // Local file header + contents.
            $data .= pack("V", 0x04034b50)
                . pack("v", 20)      // version needed
                . pack("v", 0)       // flags
                . pack("v", 0)       // method: stored
                . pack("v", 0)       // mod time
                . pack("v", 0)       // mod date
                . pack("V", $crc)
                . pack("V", $size)   // compressed size
                . pack("V", $size)   // uncompressed size
                . pack("v", strlen($name))
                . pack("v", 0)       // extra length
                . $name . $content;

            // Central directory entry.
            $central .= pack("V", 0x02014b50)
                . pack("v", 20)      // version made by
                . pack("v", 20)      // version needed
                . pack("v", 0)       // flags
                . pack("v", 0)       // method
                . pack("v", 0)       // mod time
                . pack("v", 0)       // mod date
                . pack("V", $crc)
                . pack("V", $size)
                . pack("V", $size)
                . pack("v", strlen($name))
                . pack("v", 0)       // extra length
                . pack("v", 0)       // comment length
                . pack("v", 0)       // disk number
                . pack("v", 0)       // internal attributes
                . pack("V", 0)       // external attributes
                . pack("V", $offset)
                . $name;
        }
        $centralOffset = strlen($data);
        $count = count($files);
        return $data . $central
            . pack("V", 0x06054b50)  // end of central directory
            . pack("v", 0)
            . pack("v", 0)
            . pack("v", $count)
            . pack("v", $count)
            . pack("V", strlen($central))
            . pack("V", $centralOffset)
            . pack("v", 0);
    }
}
