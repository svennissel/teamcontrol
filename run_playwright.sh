#!/bin/bash
#npm install
#npx playwright install chromium
#npx playwright install firefox

DB_USER="hb02"
DB_PASS='xSbAp5$u4_5Qkfru'

# Test-Datenbank aus Template erstellen
echo "Erstelle Test-Datenbank..."
mysql -u "$DB_USER" -p"$DB_PASS" < setup_test_db.sql

# DB-Override-Datei für Test-Datenbank erstellen
echo "teamcontrol_test" > .tc_database

# Playwright Tests ausführen
npx playwright test "$@"
TEST_EXIT_CODE=$?

# DB-Override-Datei entfernen
rm -f .tc_database

# Test-Datenbank löschen
echo "Lösche Test-Datenbank..."
mysql -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS teamcontrol_test;"

exit $TEST_EXIT_CODE
