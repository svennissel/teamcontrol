SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    hash VARCHAR(64) UNIQUE,
    is_club_admin BOOLEAN DEFAULT FALSE
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

CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    hash VARCHAR(64) UNIQUE
);

CREATE TABLE IF NOT EXISTS team_players (
    team_id INT NOT NULL,
    player_id INT NOT NULL,
    isTeamAdmin BOOLEAN DEFAULT FALSE,
    isMatchPlayer BOOLEAN DEFAULT TRUE,
    isMatchViewer BOOLEAN DEFAULT TRUE,
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

CREATE TABLE IF NOT EXISTS meta_info (
    `key` VARCHAR(255) PRIMARY KEY,
    `value` VARCHAR(255)
);

INSERT INTO meta_info (`key`, `value`) VALUES ('version', '1');

SET FOREIGN_KEY_CHECKS=1;
