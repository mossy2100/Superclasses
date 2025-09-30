<?php

declare(strict_types = 1);

namespace Superclasses\Tools;

require_once __DIR__ . '/../src/Color.php';
use Superclasses\Color;

final class WikiColorImporter
{
    /**
     * @param string[] $urls Wikipedia “List of colors” pages (e.g., A–F, G–M, N–Z)
     * @param array<string,string> $cssColorNames Your CSS_COLOR_NAMES (keys are normalized like 'aliceblue')
     * @param bool $appendAlphaFF Append 'ff' alpha to the 6-digit hex
     * @return array<string,string> non-CSS color names => 8-digit hex (lowercase, no '#')
     */
    public static function nonCssColorsFromWikipedia(
        array $urls,
        array $cssColorNames,
        bool $appendAlphaFF = true
    ): array {
        $out = [];

        foreach ($urls as $url) {
            $html = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'header' => "User-Agent: Superclasses-ColorBot/1.0\r\n"
                ]
            ]));
            if ($html === false) {
                continue; // skip on fetch errors
            }

            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new \DOMXPath($dom);

            // Rows in the wikitable(s)
            foreach ($xp->query("//table[contains(@class,'wikitable')]//tr") as $tr) {
                echo "\n";

                /** @var \DOMElement $tr */
                $tds = $tr->getElementsByTagName('td');
                if ($tds->length === 0) {
                    continue; // header row
                }

                // Name and hex = visible text of first and second <td> elements in the row.
                $name = trim($tds->item(0)->textContent ?? '');
                $hex = trim($tds->item(1)->textContent ?? '');

                // See raw values.
                echo "$name => $hex\n";

                // Normalize name (lowercase; strip non-letters/digits, brackets).
                $norm = self::norm($name);

                // If there's nothing left, continue.
                if ($norm === '') {
                    echo "Reject on name\n";
                    continue;
                }

                // Check hex is valid.
                if (!Color::isValidHexString($hex)) {
                    echo "Reject on hex\n";
                    continue;
                }
                $hex = substr(strtolower($hex), 1);

                echo "Normalised to: $norm => $hex\n";

                // Skip if it's a CSS named color.
                if (isset($cssColorNames[$norm])) {
                    if ($hex === $cssColorNames[$norm]) {
                        echo "Reject - already in the CSS set as $cssColorNames[$norm]\n";
                        continue;
                    }
                    else {
                        $norm .= '2';
                        echo "Name is in the CSS set but hex is different - changing name to '$norm'\n";
                    }
                }

                // Reject duplicate names, only keep first-seen.
                if (isset($out[$norm])) {
                    // Try with number 2.
                    $norm .= '2';
                    echo "Name already found - changing name to '$norm'\n";

                    if (isset($out[$norm])) {
                        echo "Reject - already found one with this name\n";
                        continue;
                    }
                }

                // Keep.
                $out[$norm] = $appendAlphaFF ? ($hex . 'ff') : $hex;
            }
        }

        ksort($out, SORT_STRING);
        return $out;
    }

    private static function norm(string $name): string
    {
        $name = trim($name);

        // Strip off certain suffixes.
        if (str_ends_with($name, '(web)') || str_ends_with($name, '(X11)')) {
            $name = substr($name, 0, -4);
        }

        // Deal with some odd ones.
        if ($name === 'Azure (X11/web color)') {
            return 'azure';
        }
        if ($name === 'Tenné (tawny)') {
            return 'tawny';
        }
        if ($name === 'Safety orange (blaze orange)') {
            return 'blazeorange';
        }
        if ($name === 'Zaffer (Zaffre)') {
            return 'zaffer';
        }

        // Remove anything in [] brackets.
        $name = trim(preg_replace('~\[[^\]]*\]~', '', $name));

        // Transliterate characters with accents.
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);

        // Remove anything that’s not a letter or number.
        $name = trim(preg_replace('/[^a-z0-9]+/i', '', $name));

        // Lower-case.
        return strtolower($name);
    }
}

$urls = [
    'https://en.wikipedia.org/wiki/List_of_colors:_A%E2%80%93F',
    'https://en.wikipedia.org/wiki/List_of_colors:_G%E2%80%93M',
    'https://en.wikipedia.org/wiki/List_of_colors:_N%E2%80%93Z'
];

$nonCss = WikiColorImporter::nonCssColorsFromWikipedia(
    $urls,
    Color::cssColorNames(),
    false
);

// If you want to print a PHP const array:
    //echo "public const array WIKI_NON_CSS_COLOR_NAMES = [\n";
    //foreach ($nonCss as $name => $hex) {
    //    echo "    '$name' => '$hex',\n";
    //}
    //echo "];\n";
