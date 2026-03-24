#!/bin/bash

# Release-Script für TeamControl
# Erstellt eine .zip Datei aus dem Inhalt des public Ordners

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PUBLIC_DIR="$SCRIPT_DIR/public"
VERSION="1.0.7"
FOOTER_FILE="$PUBLIC_DIR/includes/footer.php"
RELEASE_NAME="teamcontrol_${VERSION}.zip"
OUTPUT_FILE="$SCRIPT_DIR/$RELEASE_NAME"

if [ ! -d "$PUBLIC_DIR" ]; then
    echo "Fehler: Der Ordner 'public' wurde nicht gefunden unter: $PUBLIC_DIR"
    exit 1
fi

# Version in footer.php aktualisieren
if [ -f "$FOOTER_FILE" ]; then
    sed -i "s/Version [0-9]\+\.[0-9]\+\.[0-9]\+/Version $VERSION/" "$FOOTER_FILE"
    echo "Version in footer.php auf $VERSION aktualisiert."
else
    echo "Fehler: footer.php nicht gefunden unter: $FOOTER_FILE"
    exit 1
fi

cd "$PUBLIC_DIR" && zip -r "$OUTPUT_FILE" . -x "*/.*" -x "config.php"

if [ $? -eq 0 ]; then
    echo "Release erstellt: $OUTPUT_FILE"
else
    echo "Fehler beim Erstellen der Release-Datei."
    exit 1
fi
