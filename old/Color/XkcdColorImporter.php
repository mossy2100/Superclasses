<?php
// tools/XkcdColorImporter.php
declare(strict_types=1);

const SRC_URL = 'https://xkcd.com/color/rgb.txt';
const OUT_FILE = __DIR__ . '/../src/inc/ColorNames.php';
const NAMESPACE_NAME = 'Superclasses';
const CLASS_NAME = 'XkcdColorNames';
const CONST_NAME = 'XKCD_COLOR_NAMES';

$txt = @file_get_contents(SRC_URL);
if ($txt === false) {
    fwrite(STDERR, "Failed to fetch " . SRC_URL . PHP_EOL);
    exit(1);
}

$map = []; // name => 8-hex rgba (no '#', alpha 'ff')
foreach (preg_split('/\R/u', $txt) as $line) {
    $line = trim($line);
    if ($line === '') continue;
    // Lines look like: "cloudy blue #acc2d9"
    if (!preg_match('/^(.*?)\s+#([0-9a-fA-F]{6})$/u', $line, $m)) continue;

    $name = mb_strtolower(trim($m[1]), 'UTF-8');
    $hex6 = strtolower($m[2]);
    $map[$name] = $hex6 . 'ff'; // add opaque alpha to match your format
}

// stable alphabetical order
ksort($map, SORT_NATURAL | SORT_FLAG_CASE);

// emit class with constant
$out  = "<?php\n";
$out .= "declare(strict_types = 1);\n\n";
$out .= "namespace " . NAMESPACE_NAME . ";\n\n";
$out .= "/**\n";
$out .= " * Auto-generated from " . SRC_URL . " (CC0). Do not edit by hand.\n";
$out .= " * Values are lowercase 8-hex RGBA (no '#'), alpha fixed to 'ff'.\n";
$out .= " */\n";
$out .= "final class " . CLASS_NAME . " {\n";
$out .= "    /** @var array<string,string> name => 8-hex rgba */\n";
$out .= "    public const " . CONST_NAME . " = [\n";

foreach ($map as $name => $hex8) {
    // Escape single quotes safely
    $ename = str_replace("'", "\\'", $name);
    $out .= "        '{$ename}' => '{$hex8}',\n";
}

$out .= "    ];\n";
$out .= "}\n";

if (@file_put_contents(OUT_FILE, $out) === false) {
    fwrite(STDERR, "Failed to write " . OUT_FILE . PHP_EOL);
    exit(1);
}

echo "Wrote " . OUT_FILE . " (" . count($map) . " colors)\n";
