<?php

/**
 * Konfigurationsdatei für TeamControl
 *
 * Diese Datei enthält zentrale Konfigurationswerte, die an verschiedenen Stellen
 * der Anwendung verwendet werden. Änderungen an diesen Werten wirken sich
 * global auf das Verhalten der Anwendung aus.
 */


/**
 * Hostname oder ip Adresse der Datenbank. In den meisten Fällen ist dies localhost.
 */
const DATABASE_HOST = '{DATABASE_HOST}';

/**
 * Name der Datenbank
 *
 * Der Name der MySQL-Datenbank, mit der sich die Anwendung verbindet.
 * Dieser Wert wird in der PDO-Verbindung (db.php) als Teil des DSN verwendet.
 *
 * @var string
 */
const DATABASE = '{DATABASE}';

/**
 * Benutzername für die Datenbankverbindung
 *
 * Der MySQL-Benutzername, der für die Authentifizierung bei der Datenbank
 * verwendet wird. Dieser Benutzer muss über die erforderlichen Berechtigungen
 * (SELECT, INSERT, UPDATE, DELETE) auf die Datenbank verfügen.
 *
 * @var string
 */
const DATABASE_USER = '{DATABASE_USER}';

/**
 * Passwort für die Datenbankverbindung
 *
 * Das Passwort des Datenbankbenutzers für die Authentifizierung.
 * Aus Sicherheitsgründen sollte dieses Passwort stark und einzigartig sein.
 *
 * @var string
 */
const DATABASE_PASSWORD = '{DATABASE_PASSWORD}';

/**
 * Verschlüsselungsschlüssel für CSRF-Tokens
 *
 * Dieser Schlüssel wird zur AES-256-CBC-Verschlüsselung von CSRF-Tokens
 * verwendet. Er stellt sicher, dass Tokens nicht von Dritten gefälscht
 * werden können. Der Schlüssel sollte geheim gehalten und bei Kompromittierung
 * sofort geändert werden.
 *
 * Hinweis: Eine Änderung dieses Schlüssels invalidiert alle bestehenden
 * CSRF-Tokens und kann aktive Formulare ungültig machen.
 *
 * @var string
 */
const CSRF_ENCRYPTION_KEY = '{CSRF_ENCRYPTION_KEY}';

/**
 * Maximale Anzahl der angezeigten Trainings (wöchentliche Wiederholungen)
 *
 * Dieser Wert bestimmt, wie viele zukünftige Trainingstermine für jedes
 * wöchentlich wiederkehrende Training generiert und angezeigt werden.
 *
 * Beispiel: Bei einem Wert von 10 werden die nächsten 10 Wochen an
 * Trainingsterminen im Voraus berechnet und in der Übersicht dargestellt.
 *
 * Ein höherer Wert ermöglicht eine längere Vorausplanung, kann aber die
 * Übersichtlichkeit der Trainingsansicht beeinträchtigen.
 *
 * Standardwert: 10
 *
 * @var int
 */
const TRAINING_DISPLAY_COUNT = 10;

/**
 * Lebensdauer von Cookies und Sessions in Sekunden
 *
 * Dieser Wert wird für die Gültigkeitsdauer aller Cookies (z.B. Login-Hash,
 * zuletzt besuchter Tab) sowie für die Session-Cookie-Lifetime verwendet.
 *
 * Der Wert entspricht der Anzahl der Sekunden in einem Jahr (365 Tage).
 * Nach Ablauf dieser Zeit müssen sich Benutzer erneut anmelden, da sowohl
 * die Session als auch das Login-Cookie ungültig werden.
 *
 * Berechnung: 365 Tage × 24 Stunden × 60 Minuten × 60 Sekunden = 31.536.000 Sekunden
 *
 * Standardwert: 31536000 (1 Jahr)
 *
 * @var int
 */
const COOKIE_LIFETIME = 31536000;


