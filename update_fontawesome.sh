#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
CSS_DIR="$PROJECT_ROOT/public/css"
WEBFONTS_DIR="$CSS_DIR/webfonts"
TARGET_CSS="$CSS_DIR/fontawesome.min.css"

if ! command -v curl >/dev/null 2>&1; then
  echo "Fehler: curl ist nicht installiert."
  exit 1
fi

if ! command -v php >/dev/null 2>&1; then
  echo "Fehler: php ist nicht installiert."
  exit 1
fi

if ! command -v pyftsubset >/dev/null 2>&1; then
  echo "Fehler: pyftsubset ist nicht installiert."
  echo "Bitte installiere fonttools (z. B. pip install fonttools brotli)."
  exit 1
fi

mkdir -p "$WEBFONTS_DIR"

TMP_DIR="$(mktemp -d)"
cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

FA_VERSION="$(php -r '$json = @file_get_contents("https://registry.npmjs.org/@fortawesome/fontawesome-free"); if ($json === false) { fwrite(STDERR, "Registry konnte nicht gelesen werden.\n"); exit(1);} $data = json_decode($json, true); if (!is_array($data) || empty($data["dist-tags"]["latest"])) { fwrite(STDERR, "Latest-Version nicht gefunden.\n"); exit(1);} echo $data["dist-tags"]["latest"];')"
BASE_URL="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@${FA_VERSION}"

echo "Lade Font Awesome ${FA_VERSION} ..."
curl -fsSL "$BASE_URL/css/all.min.css" -o "$TMP_DIR/all.min.css"
curl -fsSL "$BASE_URL/css/solid.min.css" -o "$TMP_DIR/solid.min.css"
curl -fsSL "$BASE_URL/css/regular.min.css" -o "$TMP_DIR/regular.min.css"
curl -fsSL "$BASE_URL/webfonts/fa-solid-900.woff2" -o "$TMP_DIR/fa-solid-900.woff2"
curl -fsSL "$BASE_URL/webfonts/fa-regular-400.woff2" -o "$TMP_DIR/fa-regular-400.woff2"

echo "Ermittle verwendete Icons im Projekt ..."
ICON_LIST_FILE="$TMP_DIR/used-icons.txt"
php -r '
$root = $argv[1];
$exclude = realpath($argv[2]);
$extensions = ["php", "js", "css", "html", "htm"];
$icons = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    if ($exclude !== false && realpath($path) === $exclude) {
        continue;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $extensions, true)) {
        continue;
    }
    $content = @file_get_contents($path);
    if ($content === false || $content === "") {
        continue;
    }
    if (preg_match_all("/\bfa-([a-z0-9-]+)\b/i", $content, $matches)) {
        foreach ($matches[1] as $name) {
            $name = strtolower($name);
            if (in_array($name, ["solid", "regular", "brands"], true)) {
                continue;
            }
            $icons[$name] = true;
        }
    }
}
$names = array_keys($icons);
sort($names, SORT_STRING);
echo implode(PHP_EOL, $names), PHP_EOL;
' "$PROJECT_ROOT/public" "$TARGET_CSS" > "$ICON_LIST_FILE"

if [ ! -s "$ICON_LIST_FILE" ]; then
  echo "Fehler: Keine verwendeten Icons gefunden."
  exit 1
fi

echo "Erzeuge minimales fontawesome.min.css ..."
php -r '
$allCss = @file_get_contents($argv[1]);
$solidCss = @file_get_contents($argv[2]);
$regularCss = @file_get_contents($argv[3]);
$icons = @file($argv[4], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($allCss === false || $solidCss === false || $regularCss === false || $icons === false) {
    fwrite(STDERR, "Fehler beim Lesen temporärer Dateien.\n");
    exit(1);
}

$license = [];
if (preg_match("/\/\*!.*?\*\//s", $allCss, $m)) {
    $license[] = trim($m[0]);
}
$license[] = "/* Generiert durch update_fontawesome.sh - verwendete Icons: " . count($icons) . " */";

$fontFamily = "Font Awesome 6 Free";
if (preg_match("/--fa-style-family,\"([^\"]+)\"\)/", $allCss, $m)) {
    $fontFamily = $m[1];
}

$baseRules = [
    "@font-face{font-family:\"" . $fontFamily . "\";font-style:normal;font-weight:900;font-display:block;src:url(\"webfonts/fa-solid-900.woff2\") format(\"woff2\")}",
    "@font-face{font-family:\"" . $fontFamily . "\";font-style:normal;font-weight:400;font-display:block;src:url(\"webfonts/fa-regular-400.woff2\") format(\"woff2\")}",
    ".fa-solid,.fa-regular,.fas,.far{-moz-osx-font-smoothing:grayscale;-webkit-font-smoothing:antialiased;display:inline-block;font-style:normal;font-variant:normal;line-height:1;text-rendering:auto;font-family:\"" . $fontFamily . "\"}",
    ".fa-solid,.fas{font-weight:900}",
    ".fa-regular,.far{font-weight:400}"
];

$iconRules = [];
$solidUnicodes = [];
$regularUnicodes = [];
foreach ($icons as $icon) {
    $icon = trim($icon);
    if ($icon === "") {
        continue;
    }
    $pattern = "/\\.fa-" . preg_quote($icon, "/") . ":before\\{content:\"\\\\+([a-f0-9]+)\"\\}/i";
    if (preg_match($pattern, $solidCss, $m)) {
        $unicode = strtolower($m[1]);
        $iconRules[] = ".fa-" . $icon . ":before{content:\"\\" . $unicode . "\"}";
        $solidUnicodes[$unicode] = true;
        continue;
    }
    if (preg_match($pattern, $regularCss, $m)) {
        $unicode = strtolower($m[1]);
        $iconRules[] = ".fa-" . $icon . ":before{content:\"\\" . $unicode . "\"}";
        $regularUnicodes[$unicode] = true;
        continue;
    }

    $patternFa7 = "/\\.fa-" . preg_quote($icon, "/") . "(?:,[^{]+)?\\{--fa:\"([^\"]+)\"\\}/i";
    if (preg_match($patternFa7, $allCss, $m)) {
        $rawUnicode = strtolower($m[1]);
        if (preg_match("/^[a-f0-9]+$/", $rawUnicode)) {
            $unicode = $rawUnicode;
        } elseif (preg_match("/^\\\\([a-f0-9]+)$/", $rawUnicode, $esc)) {
            $unicode = strtolower($esc[1]);
        } elseif (preg_match("/^\\\\(.)$/", $rawUnicode, $esc)) {
            $unicode = strtolower(dechex(ord($esc[1])));
        } else {
            continue;
        }
        $iconRules[] = ".fa-" . $icon . ":before{content:\"\\" . $unicode . "\"}";
        if (preg_match($pattern, $regularCss)) {
            $regularUnicodes[$unicode] = true;
        } else {
            $solidUnicodes[$unicode] = true;
        }
    }
}

if (empty($iconRules)) {
    fwrite(STDERR, "Fehler: Keine Icon-Regeln gefunden.\n");
    exit(1);
}

$output = implode(PHP_EOL, $license) . PHP_EOL;
$output .= implode(PHP_EOL, $baseRules) . PHP_EOL;
$output .= implode(PHP_EOL, array_unique($iconRules)) . PHP_EOL;

if (@file_put_contents($argv[5], $output) === false) {
    fwrite(STDERR, "Fehler beim Schreiben der CSS-Datei.\n");
    exit(1);
}

$solidCodepoints = array_keys($solidUnicodes);
sort($solidCodepoints, SORT_STRING);
$regularCodepoints = array_keys($regularUnicodes);
sort($regularCodepoints, SORT_STRING);

$toCodepointString = static function(array $codepoints): string {
    if (empty($codepoints)) {
        return "";
    }
    $prefixed = array_map(static fn(string $cp): string => "U+" . strtoupper($cp), $codepoints);
    return implode(",", $prefixed);
};

if (@file_put_contents($argv[6], $toCodepointString($solidCodepoints)) === false) {
    fwrite(STDERR, "Fehler beim Schreiben der Solid-Codepoints.\n");
    exit(1);
}
if (@file_put_contents($argv[7], $toCodepointString($regularCodepoints)) === false) {
    fwrite(STDERR, "Fehler beim Schreiben der Regular-Codepoints.\n");
    exit(1);
}
 ' "$TMP_DIR/all.min.css" "$TMP_DIR/solid.min.css" "$TMP_DIR/regular.min.css" "$ICON_LIST_FILE" "$TARGET_CSS" "$TMP_DIR/solid-unicodes.txt" "$TMP_DIR/regular-unicodes.txt"

SOLID_CODEPOINTS="$(<"$TMP_DIR/solid-unicodes.txt")"
REGULAR_CODEPOINTS="$(<"$TMP_DIR/regular-unicodes.txt")"

if [ -z "$SOLID_CODEPOINTS" ]; then
  echo "Fehler: Keine Solid-Codepoints für Subsetting gefunden."
  exit 1
fi

echo "Erzeuge minimierte WOFF2-Dateien ..."
pyftsubset "$TMP_DIR/fa-solid-900.woff2" \
  --output-file="$WEBFONTS_DIR/fa-solid-900.woff2" \
  --flavor=woff2 \
  --unicodes="$SOLID_CODEPOINTS" \
  --layout-features='*' \
  --glyph-names \
  --symbol-cmap \
  --legacy-cmap \
  --notdef-glyph \
  --notdef-outline \
  --recommended-glyphs \
  --name-IDs='*' \
  --name-legacy \
  --name-languages='*'

if [ -n "$REGULAR_CODEPOINTS" ]; then
  pyftsubset "$TMP_DIR/fa-regular-400.woff2" \
    --output-file="$WEBFONTS_DIR/fa-regular-400.woff2" \
    --flavor=woff2 \
    --unicodes="$REGULAR_CODEPOINTS" \
    --layout-features='*' \
    --glyph-names \
    --symbol-cmap \
    --legacy-cmap \
    --notdef-glyph \
    --notdef-outline \
    --recommended-glyphs \
    --name-IDs='*' \
    --name-legacy \
    --name-languages='*'
else
  cp "$TMP_DIR/fa-regular-400.woff2" "$WEBFONTS_DIR/fa-regular-400.woff2"
fi

echo "Fertig: $TARGET_CSS aktualisiert (Font Awesome ${FA_VERSION})."
