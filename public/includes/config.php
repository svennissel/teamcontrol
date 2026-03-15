<?php

/**
 * Konfigurationsdatei für TeamControl
 *
 * Diese Datei enthält zentrale Konfigurationswerte, die an verschiedenen Stellen
 * der Anwendung verwendet werden. Änderungen an diesen Werten wirken sich
 * global auf das Verhalten der Anwendung aus.
 */

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
