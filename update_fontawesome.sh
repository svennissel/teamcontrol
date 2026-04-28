#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
CSS_DIR="$PROJECT_ROOT/public/css"
WEBFONTS_DIR="$CSS_DIR/webfonts"

if ! command -v curl >/dev/null 2>&1; then
  echo "Fehler: curl ist nicht installiert."
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

FA_VERSION="7.2.0"
BASE_URL="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@${FA_VERSION}"

echo "Lade Font Awesome ${FA_VERSION} Webfonts ..."
curl -fsSL "$BASE_URL/webfonts/fa-solid-900.woff2" -o "$TMP_DIR/fa-solid-900.woff2"
curl -fsSL "$BASE_URL/webfonts/fa-regular-400.woff2" -o "$TMP_DIR/fa-regular-400.woff2"

# Statische Codepoints passend zu public/css/fontawesome.min.css
UNICODES="U+F00C,U+F059,U+F328,U+F56F,U+F3C5,U+F304,U+F165,U+F164,U+F1F8,U+F0C0,U+002B,U+003F,U+0046"

echo "Erzeuge minimierte WOFF2-Dateien ..."
pyftsubset "$TMP_DIR/fa-solid-900.woff2" \
  --output-file="$WEBFONTS_DIR/fa-solid-900.woff2" \
  --flavor=woff2 \
  --unicodes="$UNICODES" \
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

pyftsubset "$TMP_DIR/fa-regular-400.woff2" \
  --output-file="$WEBFONTS_DIR/fa-regular-400.woff2" \
  --flavor=woff2 \
  --unicodes="$UNICODES" \
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

echo "Fertig: Minimierte WOFF2-Dateien in public/css/webfonts aktualisiert."
