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
