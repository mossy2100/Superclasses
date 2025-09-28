<?php

declare(strict_types = 1);

namespace Superclasses;

use Stringable;
use InvalidArgumentException;

/**
 * Represents a text string with encoding information.
 * 
 * @requires PHP >= 8.4
 */
class Text implements Stringable
{
    /**
     * The raw string data.
     *
     * @var string
     */
    public private(set) string $bytes;

    /**
     * The character encoding of the string.
     *
     * @var string
     */
    public private(set) string $encoding;

    /**
     * Constructs a new String object.
     *
     * @param string $bytes    The string data.
     * @param string $encoding The character encoding (optional). A default value of 'auto' means auto-detect.
     *
     * @throws InvalidArgumentException If the encoding is unknown or cannot be detected.
     */
    public function __construct(string $bytes = "", string $encoding = 'auto')
    {
        // If encoding is not specified, try to detect it.
        if ($encoding === 'auto') {
            $encoding = mb_detect_encoding($bytes);
            if ($encoding === false) {
                throw new InvalidArgumentException("Unknown encoding.");
            }
        } else {
            // If encoding is specified, check it's supported.
            if (!in_array($encoding, mb_list_encodings())) {
                throw new InvalidArgumentException("Unknown encoding '$encoding'.");
            }

            // Check the string is valid for this encoding.
            if (!mb_check_encoding($bytes, $encoding)) {
                throw new InvalidArgumentException("Provided string is invalid for the encoding '$encoding'.");
            }
        }

        // SetOf properties.
        $this->encoding = $encoding;
        $this->bytes = $bytes;
    }

    public function toLower(): self
    {
        $s = mb_strtolower($this->bytes);
        return new Text($s, $this->encoding);
    }

    public function toUpper(): self
    {
        $s = mb_strtoupper($this->bytes);
        return new Text($s, $this->encoding);
    }

    public function toTitle(): self
    {
        $s = mb_convert_case($this->bytes, MB_CASE_TITLE, $this->encoding);
        return new Text($s, $this->encoding);
    }

    public function caseFold(): self
    {
        $s = mb_convert_case($this->bytes, MB_CASE_FOLD, $this->encoding);
        return new Text($s, $this->encoding);
    }

    public function convertEncoding(string $new_encoding): self
    {
        // Attempt to convert encoding. 
        $s = mb_convert_encoding($this->bytes, $new_encoding, $this->encoding);
        if ($s === false) {
            throw new InvalidArgumentException("Encoding conversion failed.");
        }

        // Create a new Text object.
        return new Text($s, $new_encoding);
    }

    public function equal(Text|string $t2, bool $case_sensitive = true): bool
    {
        // Convert second operand to a Text if needs be.
        if (is_string($t2)) {
            $t2 = new Text($t2);
        }

        // Equalize encodings.
        $t1 = $this->convertEncoding('UTF-8');
        $t2 = $t2->convertEncoding('UTF-8');

        // Case-sensitive comparison.
        if ($case_sensitive) {
            return $t1->bytes === $t2->bytes;
        }

        // Case-insensitive comparison. Case fold both and compare bytes.
        return $t1->caseFold()->bytes === $t2->caseFold()->bytes;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Stringable implementation

    public function __toString(): string
    {
        return $this->bytes;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Static members

    public static function setDefaultDetectedEncodings(): void
    {
        $default_encodings = [
            'UTF-8',        // Most common modern encoding
            'ASCII',        // Subset of UTF-8, but faster to detect
            'UTF-7',        // Less common but distinctive patterns
            'ISO-8859-1',   // Common Western European fallback
            'JIS',          // Most restrictive Japanese encoding (fewest false positives)
            'ISO-2022-JP',  // Also restrictive
            'EUC-JP',       // Broader Japanese encoding
            'eucJP-win',    // Windows variant
            'SJIS',         // Shift-JIS (can have false positives)
            'SJIS-win'      // Windows variant (most permissive)
        ];
        mb_detect_order($default_encodings);
    }

    public static function setDetectedEncodings(array $encodings): void
    {
        mb_detect_order($encodings);
    }
}

echo '<pre>';

$text = new Text("Here is some example text.");
echo $text->toUpper() . PHP_EOL;
echo $text->toLower() . PHP_EOL;

var_dump(mb_detect_order());
