<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class DatabaseTestCase extends TestCase
{
    protected static $pdo;

    public static function setUpBeforeClass(): void
    {
        // Globale $pdo Variable überschreiben für die Dauer der Tests
        global $pdo;
        
        $pdo = new Pdo\Sqlite('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        
        // SQLite spezifische Funktionen registrieren, um MySQL Syntax zu emulieren
        $pdo->createFunction('VALUES', function($value) {
            return $value;
        }, 1);

        self::$pdo = $pdo;
        
        self::createSchema();
    }

    protected function setUp(): void
    {
        // Transaktion starten, um Tests isoliert zu halten
        if (!self::$pdo->inTransaction()) {
            self::$pdo->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        // Rollback nach jedem Test
        if (self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }

    private static function createSchema()
    {
        $queries = [
            "CREATE TABLE players (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                name TEXT NOT NULL,
                hash TEXT UNIQUE,
                is_club_admin BOOLEAN DEFAULT FALSE
            )",
            "CREATE TABLE matches (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                team_id INTEGER NOT NULL,
                match_date DATE NOT NULL,
                start_time TIME NOT NULL,
                meeting_time TIME,
                opponent TEXT NOT NULL,
                is_home_game BOOLEAN NOT NULL,
                location TEXT,
                FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
            )",
            "CREATE TABLE trainings (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                training_date DATE NOT NULL,
                training_time TIME NOT NULL,
                is_weekly BOOLEAN DEFAULT FALSE,
                day_of_week INTEGER DEFAULT NULL,
                parent_training_id INTEGER DEFAULT NULL,
                override_date DATE DEFAULT NULL,
                is_cancelled BOOLEAN DEFAULT FALSE
            )",
            "CREATE TABLE attendance (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                player_id INTEGER NOT NULL,
                event_type TEXT NOT NULL,
                event_id INTEGER NOT NULL,
                status TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                occurrence_date DATE DEFAULT NULL,
                UNIQUE (player_id, event_type, event_id, occurrence_date)
            )",
            "CREATE TABLE teams (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                name TEXT NOT NULL,
                logo TEXT,
                hash TEXT UNIQUE
            )",
            "CREATE TABLE team_players (
                team_id INTEGER NOT NULL,
                player_id INTEGER NOT NULL,
                isTeamAdmin BOOLEAN DEFAULT FALSE,
                isMatchPlayer BOOLEAN DEFAULT TRUE,
                PRIMARY KEY (team_id, player_id)
            )",
            "CREATE TABLE training_teams (
                training_id INTEGER NOT NULL,
                team_id INTEGER NOT NULL,
                PRIMARY KEY (training_id, team_id)
            )",
            "CREATE TABLE voter_permissions (
                voter_id INTEGER NOT NULL,
                player_id INTEGER NOT NULL,
                PRIMARY KEY (voter_id, player_id),
                FOREIGN KEY (voter_id) REFERENCES players(id) ON DELETE CASCADE,
                FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
            )"
        ];

        self::$pdo->exec("PRAGMA foreign_keys = ON;");

        foreach ($queries as $query) {
            // SQLite hat kein AUTO_INCREMENT Schlüsselwort in dem Sinne bei INTEGER PRIMARY KEY
            // Wir müssen es für SQLite anpassen
            $query = str_replace('AUTO_INCREMENT', '', $query);
            self::$pdo->exec($query);
        }
    }
}
