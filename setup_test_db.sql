DROP DATABASE IF EXISTS teamcontrol_test;
CREATE DATABASE teamcontrol_test;
USE teamcontrol_test;

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    hash VARCHAR(64) UNIQUE,
    is_club_admin BOOLEAN DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    hash VARCHAR(64) UNIQUE
);

CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    match_date DATE NOT NULL,
    start_time TIME NOT NULL,
    meeting_time TIME,
    opponent VARCHAR(255) NOT NULL,
    is_home_game BOOLEAN NOT NULL,
    location VARCHAR(255),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS trainings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_date DATE NOT NULL,
    training_time TIME NOT NULL,
    is_weekly BOOLEAN DEFAULT FALSE,
    day_of_week TINYINT DEFAULT NULL,
    parent_training_id INT DEFAULT NULL,
    override_date DATE DEFAULT NULL,
    is_cancelled BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (parent_training_id) REFERENCES trainings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    event_type ENUM('match', 'training') NOT NULL,
    event_id INT NOT NULL,
    status ENUM('yes', 'no', 'maybe') NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    occurrence_date DATE DEFAULT NULL,
    UNIQUE KEY unique_attendance (player_id, event_type, event_id, occurrence_date),
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS team_players (
    team_id INT NOT NULL,
    player_id INT NOT NULL,
    PRIMARY KEY (team_id, player_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS team_admins (
    team_id INT NOT NULL,
    player_id INT NOT NULL,
    PRIMARY KEY (team_id, player_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS training_teams (
    training_id INT NOT NULL,
    team_id INT NOT NULL,
    PRIMARY KEY (training_id, team_id),
    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS voter_permissions (
    voter_id INT NOT NULL,
    player_id INT NOT NULL,
    PRIMARY KEY (voter_id, player_id),
    FOREIGN KEY (voter_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
);

-- Initialer Admin-Benutzer
INSERT INTO players (id, name, hash, is_club_admin) VALUES (1, 'Admin', 'testHash', TRUE);

-- Teams für games.spec.ts und trainings.spec.ts
INSERT INTO teams (id, name, hash) VALUES (1, 'E2E Test Team Game', 'teamHashGame');
INSERT INTO teams (id, name, hash) VALUES (2, 'E2E Test Team Training', 'teamHashTraining');

-- Teams für vote_visibility.spec.ts
INSERT INTO teams (id, name, hash) VALUES (3, 'Sichtbarkeit Test Team', 'teamHashSichtbarkeit');
INSERT INTO teams (id, name, hash) VALUES (4, 'Anderes Team', 'teamHashAnderes');

-- Teams für trainings_filter.spec.ts
INSERT INTO teams (id, name, hash) VALUES (5, 'Filter Team 1', 'teamHashFilter1');
INSERT INTO teams (id, name, hash) VALUES (6, 'Filter Team 2', 'teamHashFilter2');

-- Spieler für games.spec.ts
INSERT INTO players (id, name, hash, is_club_admin) VALUES (2, 'E2E Test Spieler Game', 'playerHashGame', FALSE);
INSERT INTO team_players (team_id, player_id) VALUES (1, 2);

-- Spieler für trainings.spec.ts
INSERT INTO players (id, name, hash, is_club_admin) VALUES (3, 'E2E Test Spieler Training', 'playerHashTraining', FALSE);
INSERT INTO team_players (team_id, player_id) VALUES (2, 3);

-- Spieler für vote_visibility.spec.ts
INSERT INTO players (id, name, hash, is_club_admin) VALUES (4, 'Spieler Ohne Team', 'playerHashOhneTeam', FALSE);
INSERT INTO team_players (team_id, player_id) VALUES (4, 4);

INSERT INTO players (id, name, hash, is_club_admin) VALUES (5, 'Spieler Mit Team', 'playerHashMitTeam', FALSE);
INSERT INTO team_players (team_id, player_id) VALUES (3, 5);

INSERT INTO players (id, name, hash, is_club_admin) VALUES (6, 'Spieler Mit Anderem Team', 'playerHashAnderesTeam', FALSE);
INSERT INTO team_players (team_id, player_id) VALUES (4, 6);

-- Training für vote_visibility.spec.ts (Datum weit in der Zukunft)
INSERT INTO trainings (id, training_date, training_time) VALUES (1, '2027-01-01', '18:18');
INSERT INTO training_teams (training_id, team_id) VALUES (1, 3);

-- Spiel für vote_visibility.spec.ts
INSERT INTO matches (id, team_id, match_date, start_time, opponent, is_home_game) VALUES (1, 3, '2027-01-01', '19:19', 'Test Gegner', FALSE);

-- Trainings für trainings_filter.spec.ts
INSERT INTO trainings (id, training_date, training_time) VALUES (2, '2027-01-02', '10:00');
INSERT INTO training_teams (training_id, team_id) VALUES (2, 5);

INSERT INTO trainings (id, training_date, training_time) VALUES (3, '2027-01-02', '11:00');
INSERT INTO training_teams (training_id, team_id) VALUES (3, 6);
