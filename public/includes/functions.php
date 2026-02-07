<?php
require_once 'db.php';

function getMatches(?int $playerId = null, bool $isClubAdmin = false): array {
    global $pdo;
    
    $sql = "SELECT m.* FROM matches m";
    $params = [];

    // Filter: Nur Spiele die noch nicht begonnen haben oder in den letzten 6 Stunden begonnen haben
    $filterSql = " (STR_TO_DATE(CONCAT(m.match_date, ' ', m.start_time), '%Y-%m-%d %H:%i:%s') >= NOW() - INTERVAL 6 HOUR)";

    if (!$isClubAdmin && $playerId !== null) {
        $sql .= " JOIN team_players tp ON m.team_id = tp.team_id WHERE tp.player_id = ? AND" . $filterSql;
        $params[] = $playerId;
    } else {
        $sql .= " WHERE" . $filterSql;
    }
    
    $sql .= " ORDER BY match_date ASC, start_time ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $matches = $stmt->fetchAll();

    foreach ($matches as &$match) {
        $match['teams'] = [$match['team_id']];
    }
    return $matches;
}

function getTrainings(?int $playerId = null, bool $isClubAdmin = false): array {
    global $pdo;

    $sql = "SELECT t.* FROM trainings t";
    $params = [];

    // Filter: Nur Trainings die noch nicht begonnen haben oder in den letzten 6 Stunden begonnen haben
    $filterSql = " (STR_TO_DATE(CONCAT(t.training_date, ' ', t.training_time), '%Y-%m-%d %H:%i:%s') >= NOW() - INTERVAL 6 HOUR)";

    if (!$isClubAdmin && $playerId !== null) {
        $sql .= " WHERE (
            NOT EXISTS (SELECT 1 FROM training_teams tt WHERE tt.training_id = t.id)
            OR t.id IN (
                SELECT tt.training_id FROM training_teams tt
                JOIN team_players tp ON tt.team_id = tp.team_id
                WHERE tp.player_id = ?
            )
        ) AND" . $filterSql;
        $params[] = $playerId;
    } else {
        $sql .= " WHERE" . $filterSql;
    }

    $sql .= " ORDER BY training_date ASC, training_time ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $trainings = $stmt->fetchAll();

    foreach ($trainings as &$training) {
        $training['teams'] = getEventTeams('training', $training['id']);
    }
    return $trainings;
}

function updateAttendance(int $voterId, int $playerId, string $eventType, int $eventId, string $status): bool {
    if (!canVoteFor($voterId, $playerId, $eventType, $eventId)) {
        return false;
    }
    
    global $pdo;
    
    // Prüfen ob bereits ein Eintrag existiert
    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE player_id = ? AND event_type = ? AND event_id = ?");
    $stmt->execute([$playerId, $eventType, $eventId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO attendance (player_id, event_type, event_id, status) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$playerId, $eventType, $eventId, $status]);
    }
}

function getAttendance(string $eventType, int $eventId): array {
    global $pdo;
    
    // 1. Alle Teams des Events abrufen
    $teamIds = getEventTeams($eventType, $eventId);
    
    if (empty($teamIds)) {
        // Wenn keine Teams zugeordnet sind, geben wir nur die ab, die abgestimmt haben (sollte eigentlich nicht vorkommen laut neuer Regeln)
        $stmt = $pdo->prepare("SELECT a.*, p.name FROM attendance a 
                               JOIN players p ON a.player_id = p.id 
                               WHERE a.event_type = ? AND a.event_id = ?");
        $stmt->execute([$eventType, $eventId]);
        return $stmt->fetchAll();
    }

    // 2. Alle Spieler dieser Teams abrufen
    $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
    $stmt = $pdo->prepare("SELECT DISTINCT p.id, p.name FROM players p 
                           JOIN team_players tp ON p.id = tp.player_id 
                           WHERE tp.team_id IN ($placeholders)
                           ORDER BY p.name ASC");
    $stmt->execute($teamIds);
    $allPlayers = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    // 3. Vorhandene Abstimmungen abrufen
    $stmt = $pdo->prepare("SELECT player_id, status FROM attendance 
                           WHERE event_type = ? AND event_id = ?");
    $stmt->execute([$eventType, $eventId]);
    $votes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4. Kombinieren
    $results = [];
    foreach ($allPlayers as $playerId => $playerData) {
        $results[] = [
            'player_id' => $playerId,
            'name' => $playerData['name'],
            'status' => $votes[$playerId] ?? 'none'
        ];
    }

    return $results;
}

function getPlayerAttendance($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $results = $stmt->fetchAll();
    
    $attendance = [];
    foreach ($results as $row) {
        $attendance[$row['event_type']][$row['event_id']] = $row['status'];
    }
    return $attendance;
}

function createMatch($date, $start, $meeting, $opponent, $isHome, $location, $teamId) {
    global $pdo;
    $meeting = !empty($meeting) ? $meeting : null;
    $stmt = $pdo->prepare("INSERT INTO matches (match_date, start_time, meeting_time, opponent, is_home_game, location, team_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$date, $start, $meeting, $opponent, $isHome ? 1 : 0, $location, $teamId]);
    return $pdo->lastInsertId();
}

function updateMatch($id, $date, $start, $meeting, $opponent, $isHome, $location, $teamId) {
    global $pdo;
    $meeting = !empty($meeting) ? $meeting : null;
    $stmt = $pdo->prepare("UPDATE matches SET match_date = ?, start_time = ?, meeting_time = ?, opponent = ?, is_home_game = ?, location = ?, team_id = ? WHERE id = ?");
    return $stmt->execute([$date, $start, $meeting, $opponent, $isHome ? 1 : 0, $location, $teamId, $id]);
}

function createTraining($date, $time, $teamIds = []) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO trainings (training_date, training_time) VALUES (?, ?)");
    $stmt->execute([$date, $time]);
    $trainingId = $pdo->lastInsertId();
    if (!empty($teamIds)) {
        assignTeamsToEvent($trainingId, 'training', $teamIds);
    }
    return $trainingId;
}

function createWeeklyTrainings($dayOfWeek, $time, $startDate, $teamIds = []) {
    global $pdo;
    
    $start = new DateTime($startDate);
    // Wenn das Startdatum nicht der gewünschte Wochentag ist, zum nächsten vorkommen springen
    if ($start->format('w') != $dayOfWeek) {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $start->modify('next ' . $days[$dayOfWeek]);
    }
    
    $end = clone $start;
    $end->modify('+1 year');
    
    $interval = new DateInterval('P1W');
    $period = new DatePeriod($start, $interval, $end);
    
    $stmt = $pdo->prepare("INSERT INTO trainings (training_date, training_time) VALUES (?, ?)");
    
    $nestedTransaction = $pdo->inTransaction();
    if (!$nestedTransaction) {
        $pdo->beginTransaction();
    }
    
    try {
        foreach ($period as $dt) {
            $stmt->execute([$dt->format('Y-m-d'), $time]);
            $trainingId = $pdo->lastInsertId();
            if (!empty($teamIds)) {
                assignTeamsToEvent($trainingId, 'training', $teamIds);
            }
        }
        
        if (!$nestedTransaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if (!$nestedTransaction) {
            $pdo->rollBack();
        }
        return false;
    }
}

function updateTraining($id, $date, $time, $teamIds = []) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE trainings SET training_date = ?, training_time = ? WHERE id = ?");
    $result = $stmt->execute([$date, $time, $id]);
    assignTeamsToEvent($id, 'training', $teamIds);
    return $result;
}

function assignTeamsToEvent($eventId, $eventType, $teamIds) {
    global $pdo;
    $table = ($eventType === 'match') ? 'match_teams' : 'training_teams';
    $idCol = ($eventType === 'match') ? 'match_id' : 'training_id';

    // Bestehende Zuordnungen löschen
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $idCol = ?");
    $stmt->execute([$eventId]);

    // Neue Zuordnungen hinzufügen
    if (!empty($teamIds)) {
        $stmt = $pdo->prepare("INSERT INTO $table ($idCol, team_id) VALUES (?, ?)");
        foreach ($teamIds as $teamId) {
            $stmt->execute([$eventId, $teamId]);
        }
    }
}

function getEventTeams($eventType, $eventId) {
    global $pdo;
    if ($eventType === 'match') {
        $stmt = $pdo->prepare("SELECT team_id FROM matches WHERE id = ?");
        $stmt->execute([$eventId]);
        $teamId = $stmt->fetchColumn();
        return $teamId ? [$teamId] : [];
    }
    
    $table = 'training_teams';
    $idCol = 'training_id';

    $stmt = $pdo->prepare("SELECT team_id FROM $table WHERE $idCol = ?");
    $stmt->execute([$eventId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function deleteMatch($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
    return $stmt->execute([$id]);
}

function deleteTraining($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM trainings WHERE id = ?");
    return $stmt->execute([$id]);
}

// Teams
function getTeams($playerId = null, $isClubAdmin = false) {
    global $pdo;
    
    if ($isClubAdmin || $playerId === null) {
        $stmt = $pdo->query("SELECT * FROM teams ORDER BY name ASC");
        return $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT t.* FROM teams t 
                                JOIN team_admins ta ON t.id = ta.team_id 
                                WHERE ta.player_id = ? 
                                ORDER BY t.name ASC");
        $stmt->execute([$playerId]);
        return $stmt->fetchAll();
    }
}

function createTeam($name, $logo) {
    global $pdo;
    $teamHash = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("INSERT INTO teams (name, logo, hash) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $logo, $teamHash]);
}

function updateTeam($id, $name, $logo) {
    global $pdo;
    if ($logo) {
        $stmt = $pdo->prepare("UPDATE teams SET name = ?, logo = ? WHERE id = ?");
        return $stmt->execute([$name, $logo, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE teams SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }
}

function deleteTeam($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    return $stmt->execute([$id]);
}

function getTeamByHash($hash) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE hash = ?");
    $stmt->execute([$hash]);
    return $stmt->fetch();
}

function getTeamPlayers($teamId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.* FROM players p JOIN team_players tp ON p.id = tp.player_id WHERE tp.team_id = ?");
    $stmt->execute([$teamId]);
    return $stmt->fetchAll();
}

function addPlayerToTeam($teamId, $playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM team_players WHERE team_id = ? AND player_id = ?");
    $stmt->execute([$teamId, $playerId]);
    if ($stmt->fetch()) return true;

    $stmt = $pdo->prepare("INSERT INTO team_players (team_id, player_id) VALUES (?, ?)");
    return $stmt->execute([$teamId, $playerId]);
}

function removePlayerFromTeam($playerId, $teamId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM team_players WHERE player_id = ? AND team_id = ?");
    return $stmt->execute([$playerId, $teamId]);
}

function getAllPlayers() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM players ORDER BY name ASC");
    return $stmt->fetchAll();
}

function createPlayer($name, $is_club_admin, $teamIds = [], $adminTeamIds = [], $voterPermissionPlayerIds = []) {
    global $pdo;
    $playerHash = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    $stmt = $pdo->prepare("INSERT INTO players (name, hash, is_club_admin) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $playerHash, $is_club_admin ? 1 : 0])) {
        $playerId = $pdo->lastInsertId();
        foreach ($teamIds as $teamId) {
            addPlayerToTeam($teamId, $playerId);
        }
        foreach ($adminTeamIds as $teamId) {
            addTeamAdmin($teamId, $playerId);
        }
        foreach ($voterPermissionPlayerIds as $targetPlayerId) {
            addVoterPermission($playerId, $targetPlayerId);
        }
        return true;
    }
    return false;
}

function updatePlayer($id, $name, $is_club_admin, $teamIds = [], $adminTeamIds = [], $voterPermissionPlayerIds = []) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE players SET name = ?, is_club_admin = ? WHERE id = ?");
    $result = $stmt->execute([$name, $is_club_admin ? 1 : 0, $id]);

    // Team-Zuordnungen aktualisieren
    $stmt = $pdo->prepare("DELETE FROM team_players WHERE player_id = ?");
    $stmt->execute([$id]);
    foreach ($teamIds as $teamId) {
        addPlayerToTeam($teamId, $id);
    }

    // Team-Admin-Zuordnungen aktualisieren
    $stmt = $pdo->prepare("DELETE FROM team_admins WHERE player_id = ?");
    $stmt->execute([$id]);
    foreach ($adminTeamIds as $teamId) {
        addTeamAdmin($teamId, $id);
    }

    // Voter Permissions aktualisieren
    $stmt = $pdo->prepare("DELETE FROM voter_permissions WHERE voter_id = ?");
    $stmt->execute([$id]);
    foreach ($voterPermissionPlayerIds as $targetPlayerId) {
        addVoterPermission($id, $targetPlayerId);
    }

    return $result;
}

function addTeamAdmin($teamId, $playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM team_admins WHERE team_id = ? AND player_id = ?");
    $stmt->execute([$teamId, $playerId]);
    if ($stmt->fetch()) return true;

    $stmt = $pdo->prepare("INSERT INTO team_admins (team_id, player_id) VALUES (?, ?)");
    return $stmt->execute([$teamId, $playerId]);
}

function getTeamAdmins($teamId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.* FROM players p JOIN team_admins ta ON p.id = ta.player_id WHERE ta.team_id = ?");
    $stmt->execute([$teamId]);
    return $stmt->fetchAll();
}

function getPlayerByName($name) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM players WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$name]);
    return $stmt->fetch();
}

function getLoggedInPlayer() {
    global $pdo;
    $hash = $_SESSION['hash'];
    $stmt = $pdo->prepare("SELECT * FROM players WHERE hash = ?");
    $stmt->execute([$hash]);
    $player = $stmt->fetch();

    //Cookie einmal am Tag aktualisieren
    $hash_lastupdate = $_COOKIE['hash_lastupdate'] ?? 0;
    $one_day_in_seconds = 86400;
    if($player != null && $hash_lastupdate < time() - $one_day_in_seconds) {
        $next_jear = time() + 31536000;
        setcookie('hash', $player['hash'], $next_jear, '/');
        setcookie('hash_lastupdate', (string)time(), $next_jear, '/');
    }

    return $player;
}

function getPlayer($id) {
    global $pdo;
    $hash = $_SESSION['hash'];
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function deletePlayer($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
    return $stmt->execute([$id]);
}

function getPlayerTeams($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.* FROM teams t JOIN team_players tp ON t.id = tp.team_id WHERE tp.player_id = ?");
    $stmt->execute([$playerId]);
    return $stmt->fetchAll();
}

function getAdminTeams($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.* FROM teams t JOIN team_admins ta ON t.id = ta.team_id WHERE ta.player_id = ?");
    $stmt->execute([$playerId]);
    return $stmt->fetchAll();
}

function addVoterPermission($voterId, $playerId) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT IGNORE INTO voter_permissions (voter_id, player_id) VALUES (?, ?)");
    return $stmt->execute([$voterId, $playerId]);
}

function getVoterPermissions($voterId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT player_id FROM voter_permissions WHERE voter_id = ?");
    $stmt->execute([$voterId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function canVoteFor($voterId, $playerId, $eventType = null, $eventId = null) {
    if ($voterId == $playerId) return true;
    
    global $pdo;
    // Vereinsadmin darf alles
    $stmt = $pdo->prepare("SELECT is_club_admin FROM players WHERE id = ?");
    $stmt->execute([$voterId]);
    if ($stmt->fetchColumn()) return true;

    // Delegierte Berechtigung
    $stmt = $pdo->prepare("SELECT 1 FROM voter_permissions WHERE voter_id = ? AND player_id = ?");
    $stmt->execute([$voterId, $playerId]);
    if ($stmt->fetch()) return true;

    // Mannschaftsadmin darf für seine Mannschaftsmitglieder abstimmen
    // Wir prüfen, ob voterId Admin in einem Team ist, in dem playerId Mitglied ist.
    $stmt = $pdo->prepare("
        SELECT 1 FROM team_admins ta
        JOIN team_players tp ON ta.team_id = tp.team_id
        WHERE ta.player_id = ? AND tp.player_id = ?
    ");
    $stmt->execute([$voterId, $playerId]);
    if ($stmt->fetch()) return true;

    return false;
}
?>
