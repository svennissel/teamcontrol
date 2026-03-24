# TeamControl

TeamControl ist eine einfache Webanwendung zur Verwaltung von Spielen und Training für Sportmannschaften. Der Fokus liegt auf einer **unkomplizierten Bedienung ohne klassischen Login** – Spieler melden sich über einen persönlichen Hash-Link oder QR-Code an, ganz ohne Benutzername und Passwort.

## Funktionen

### Spiele

Spiele können einfach angelegt, bearbeitet und gelöscht werden. Jeder Spieler sieht die anstehenden Spiele und kann per Klick seine Teilnahme zu- oder absagen. So hat der Trainer jederzeit einen aktuellen Überblick, wer beim nächsten Spiel dabei ist.

![Spiele Übersicht](/docs/images/games-overview.webp)

### Training

Wiederkehrende Trainingstermine werden wöchentlich automatisch generiert. 
Spieler können für jeden einzelnen Termin ihre Teilnahme abstimmen. Die Anzahl der im Voraus angezeigten Termine ist konfigurierbar.

![Traing Übersicht](/docs/images/training-overview.webp)

### Mannschaften

Teams lassen sich anlegen und verwalten. Spieler werden einer oder mehreren Mannschaften zugeordnet. 
Über einen **Mannschafts-Link** können Teammitglieder eingeladen werden – der Link kann geteilt werden, sodass sich Spieler selbstständig bei der Mannschaft anmelden können, ohne dass der Admin alle einzeln hinzufügen muss.

![Mannschaften administrieren](/docs/images/team-admin-overview.webp)

### Spieler

Die Spielerverwaltung bietet eine Suchfunktion zum schnellen Finden von Spielern. 
Für jeden Spieler kann ein QR-Code generiert werden, der den persönlichen Login-Link enthält – ideal zum Ausdrucken oder Weitergeben.

![Spieler verwalten](/docs/images/player-admin-overview.webp)

### Login ohne Passwort

Jeder Spieler erhält einen individuellen Hash-Link, über den er sich direkt und ohne Passwort anmeldet. Der Link kann als QR-Code angezeigt und geteilt werden. Es gibt keinen klassischen Login mit Benutzername und Passwort – das macht die Nutzung besonders einfach und niedrigschwellig.

### Installer

Die Ersteinrichtung erfolgt komfortabel über den Browser. Beim ersten Aufruf startet automatisch ein Installer, der durch die Konfiguration führt: Datenbank-Zugangsdaten eingeben, das Datenbankschema wird automatisch importiert und ein erster Admin-Spieler wird angelegt.

## Technologien

- **PHP** Getestet mit Version 8.5
- **MySQL** als Datenbank
- **Vanilla CSS & JavaScript** (kein Frontend-Framework)
- **PHPUnit** für Unit-Tests
- **PHPStan** für statische Code-Analyse
- **Playwright** für End-to-End-Tests
