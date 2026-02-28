@echo off
REM call npm install
REM call npx playwright install chromium

set DB_USER=hb02
set DB_PASS=xSbAp5$u4_5Qkfru

REM Test-Datenbank aus Template erstellen
echo Erstelle Test-Datenbank...
mysql -u %DB_USER% -p%DB_PASS% < setup_test_db.sql

REM DB-Override-Datei für Test-Datenbank erstellen
echo teamcontrol_test> .tc_database

REM Playwright Tests ausführen
call npx playwright test %*
set TEST_EXIT_CODE=%ERRORLEVEL%

REM DB-Override-Datei entfernen
del /f .tc_database 2>nul

REM Test-Datenbank löschen
echo Lösche Test-Datenbank...
mysql -u %DB_USER% -p%DB_PASS% -e "DROP DATABASE IF EXISTS teamcontrol_test;"

pause
exit /b %TEST_EXIT_CODE%
