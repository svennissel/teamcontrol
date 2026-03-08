<?php
require_once 'db.php';

function getMatches(?int $playerId = null): array {
    global $pdo;
    

    $params = [];
    $params[] = $playerId;

    $sql = "SELECT m.* FROM matches m JOIN team_players tp ON m.team_id = tp.team_id WHERE tp.player_id = ? AND tp.isMatchPlayer = 1 AND (STR_TO_DATE(CONCAT(m.match_date, ' ', m.start_time), '%Y-%m-%d %H:%i:%s') >= NOW() - INTERVAL 6 HOUR) ORDER BY match_date ASC, start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $matches = $stmt->fetchAll();

    foreach ($matches as &$match) {
        $match['teams'] = [$match['team_id']];
    }
    return $matches;
}

function getTrainings(?int $playerId): array {
    global $pdo;

    // Einmalige und Override-Trainings laden (nicht-wöchentlich)

    $params = [];
    $params[] = $playerId;

    $sql = "SELECT t.* FROM trainings t WHERE (
        NOT EXISTS (SELECT 1 FROM training_teams tt WHERE tt.training_id = t.id)
        OR t.id IN (
            SELECT tt.training_id FROM training_teams tt
            JOIN team_players tp ON tt.team_id = tp.team_id
            WHERE tp.player_id = ?
        )
    ) AND t.is_weekly = 0 
        AND (
            (STR_TO_DATE(CONCAT(t.training_date, ' ', t.training_time), '%Y-%m-%d %H:%i:%s') >= NOW() - INTERVAL 6 HOUR)
            OR (is_cancelled = 1 AND override_date = CURDATE())
        ) 
        ORDER BY training_date ASC, training_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $singleTrainings = $stmt->fetchAll();

    // Wöchentliche Trainings laden
    $paramsWeekly = [];
    $paramsWeekly[] = $playerId;

    $sqlWeekly = "SELECT t.* FROM trainings t
           WHERE (
        NOT EXISTS (SELECT 1 FROM training_teams tt WHERE tt.training_id = t.id)
        OR t.id IN (
            SELECT tt.training_id FROM training_teams tt
            JOIN team_players tp ON tt.team_id = tp.team_id
            WHERE tp.player_id = ?
        )
    ) AND t.is_weekly = 1";

    $stmtWeekly = $pdo->prepare($sqlWeekly);
    $stmtWeekly->execute($paramsWeekly);
    $weeklyTrainings = $stmtWeekly->fetchAll();

    // Override-Dates sammeln (Daten die durch Einzelbearbeitung ersetzt wurden)
    $overrideDates = [];
    foreach ($singleTrainings as $t) {
        if (!empty($t['parent_training_id']) && !empty($t['override_date'])) {
            $overrideDates[$t['parent_training_id']][] = $t['override_date'];
        }
    }

    // Cancelled Overrides herausfiltern
    $singleTrainings = array_filter($singleTrainings, function($t) {
        return empty($t['is_cancelled']);
    });
    $singleTrainings = array_values($singleTrainings);

    // Wöchentliche Trainings in virtuelle Einzeltermine expandieren (nächste 20 Wochen)
    $expandedTrainings = [];
    $now = new DateTime();
    $now->modify('-6 hours');

    foreach ($weeklyTrainings as $weekly) {
        $start = new DateTime($weekly['training_date']);
        $startWithTime = new DateTime($weekly['training_date'] . ' ' . $weekly['training_time']);
        
        // Wenn das Startdatum in der Vergangenheit liegt, ab heute starten
        if ($startWithTime < $now) {
            $start = new DateTime();
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            if ($start->format('w') != $weekly['day_of_week']) {
                $start->modify('next ' . $days[$weekly['day_of_week']]);
            }
            // Prüfen ob der heutige Termin noch gültig ist (innerhalb 6h Fenster)
            $todayOccurrence = new DateTime($start->format('Y-m-d') . ' ' . $weekly['training_time']);
            if ($todayOccurrence < $now) {
                $start->modify('+1 week');
            }
        }

        $count = 0;
        $current = clone $start;
        while ($count < 20) {
            $dateStr = $current->format('Y-m-d');
            
            // Prüfen ob dieses Datum durch ein Override ersetzt wurde
            if (!isset($overrideDates[$weekly['id']]) || !in_array($dateStr, $overrideDates[$weekly['id']])) {
                $expandedTrainings[] = [
                    'id' => $weekly['id'],
                    'training_date' => $dateStr,
                    'training_time' => $weekly['training_time'],
                    'is_weekly' => 1,
                    'day_of_week' => $weekly['day_of_week'],
                    'parent_training_id' => null,
                    'override_date' => null,
                    'occurrence_date' => $dateStr,
                ];
            }
            $current->modify('+1 week');
            $count++;
        }
    }

    // Alle Trainings zusammenführen
    $allTrainings = array_merge($singleTrainings, $expandedTrainings);

    // Nach Datum und Zeit sortieren
    usort($allTrainings, function($a, $b) {
        $cmp = strcmp($a['training_date'], $b['training_date']);
        if ($cmp !== 0) return $cmp;
        return strcmp($a['training_time'], $b['training_time']);
    });

    // Teams zuordnen
    foreach ($allTrainings as &$training) {
        $training['teams'] = getEventTeams('training', $training['id']);
        if (!isset($training['occurrence_date'])) {
            $training['occurrence_date'] = null;
        }
    }
    return $allTrainings;
}

function updateAttendance(int $voterId, int $playerId, string $eventType, int $eventId, string $status, ?string $occurrenceDate = null): bool {
    if (!canVoteFor($voterId, $playerId, $eventType, $eventId)) {
        return false;
    }
    
    global $pdo;
    
    // Prüfen ob bereits ein Eintrag existiert
    if ($occurrenceDate) {
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE player_id = ? AND event_type = ? AND event_id = ? AND occurrence_date = ?");
        $stmt->execute([$playerId, $eventType, $eventId, $occurrenceDate]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE player_id = ? AND event_type = ? AND event_id = ? AND occurrence_date IS NULL");
        $stmt->execute([$playerId, $eventType, $eventId]);
    }
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO attendance (player_id, event_type, event_id, status, occurrence_date) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$playerId, $eventType, $eventId, $status, $occurrenceDate]);
    }
}

/**
 * @throws Exception if the event type is invalid
 */
function getAttendance(string $eventType, int $eventId, ?string $occurrenceDate = null): array {
    global $pdo;
    
    if($eventType === 'match') {
        return getAttendanceForMatches($eventId);
    } else if ($eventType === 'training') {
        return getAttendanceForTraining($eventId, $occurrenceDate);
    }
    throw new Exception("Invalid event type: $eventType");
}

function getAttendanceForMatches(int $matchId): array
{
    global $pdo;
    //First select alls votes
    //Then select all players that not voted for the match and are a match player
    $stmt = $pdo->prepare("SELECT a.player_id, p.name, a.status 
                            FROM attendance a 
                            JOIN players p ON a.player_id = p.id
                            WHERE a.event_id = ?
                            AND a.event_type = 'match'
                            
                            UNION
                            
                            SELECT p.id AS player_id, p.name, 'none' AS status 
                            FROM matches m
                            JOIN team_players tp ON m.team_id = tp.team_id 
                            JOIN players p ON tp.player_id = p.id 
                            LEFT JOIN attendance a ON a.event_id = m.id AND a.event_type = 'match' AND a.player_id = p.id
                            WHERE m.id = ?
                            AND tp.isMatchPlayer = true
                            AND a.player_id IS NULL");
    $stmt->execute([$matchId, $matchId]);
    return $stmt->fetchAll();
}

function getAttendanceForTraining(int $trainingId, ?string $occurrenceDate = null): array {
    global $pdo;
    //First select alls votes
    //Then select all players that not voted for the training and are a team member

    //If the training is a single event, then the occurrence_date is not set.
    $occurrenceCondition = $occurrenceDate != null ? "AND occurrence_date = ?" : "";
    $stmt = $pdo->prepare("SELECT a.player_id, p.name, a.status
                            FROM attendance a 
                            JOIN players p ON a.player_id = p.id
                            WHERE a.event_id = ?
                            $occurrenceCondition
                            AND a.event_type = 'training'
                            
                            UNION
                            
                            SELECT p.id AS player_id, p.name, 'none' AS status
                            FROM trainings t
                            JOIN training_teams tt ON tt.team_id = t.id
                            JOIN team_players tp ON tt.team_id = tp.team_id 
                            JOIN players p ON tp.player_id = p.id 
                            LEFT JOIN attendance a ON a.event_id = t.id AND a.event_type = 'training' AND a.player_id = p.id $occurrenceCondition
                            WHERE t.id = ?
                            AND a.player_id IS NULL");
    if($occurrenceDate != null)
        $stmt->execute([$trainingId, $occurrenceDate, $occurrenceDate, $trainingId]);
    else
        $stmt->execute([$trainingId, $trainingId]);
    return $stmt->fetchAll();
}

function getPlayerAttendance($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE player_id = ?");
    $stmt->execute([$playerId]);
    $results = $stmt->fetchAll();
    
    $attendance = [];
    foreach ($results as $row) {
        $key = $row['event_id'];
        if (!empty($row['occurrence_date'])) {
            $key = $row['event_id'] . '_' . $row['occurrence_date'];
        }
        $attendance[$row['event_type']][$key] = $row['status'];
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
    
    $stmt = $pdo->prepare("INSERT INTO trainings (training_date, training_time, is_weekly, day_of_week) VALUES (?, ?, 1, ?)");
    $stmt->execute([$start->format('Y-m-d'), $time, $dayOfWeek]);
    $trainingId = $pdo->lastInsertId();
    if (!empty($teamIds)) {
        assignTeamsToEvent($trainingId, 'training', $teamIds);
    }
    return $trainingId;
}

function updateTraining($id, $date, $time, $teamIds = []) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE trainings SET training_date = ?, training_time = ? WHERE id = ?");
    $result = $stmt->execute([$date, $time, $id]);
    assignTeamsToEvent($id, 'training', $teamIds);
    return $result;
}

function updateWeeklyTrainingSeries($id, $dayOfWeek, $time, $teamIds = []) {
    global $pdo;
    
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $start = new DateTime();
    if ($start->format('w') != $dayOfWeek) {
        $start->modify('next ' . $days[$dayOfWeek]);
    }
    
    $stmt = $pdo->prepare("UPDATE trainings SET training_date = ?, training_time = ?, day_of_week = ? WHERE id = ?");
    $result = $stmt->execute([$start->format('Y-m-d'), $time, $dayOfWeek, $id]);
    assignTeamsToEvent($id, 'training', $teamIds);
    return $result;
}

function createTrainingOverride($parentId, $overrideDate, $newDate, $time, $teamIds = []) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO trainings (training_date, training_time, is_weekly, parent_training_id, override_date) VALUES (?, ?, 0, ?, ?)");
    $stmt->execute([$newDate, $time, $parentId, $overrideDate]);
    $trainingId = $pdo->lastInsertId();
    if (!empty($teamIds)) {
        assignTeamsToEvent($trainingId, 'training', $teamIds);
    }
    return $trainingId;
}

function deleteWeeklyTraining($id) {
    global $pdo;
    // Löscht das wöchentliche Training und alle Overrides (CASCADE)
    $stmt = $pdo->prepare("DELETE FROM trainings WHERE id = ?");
    return $stmt->execute([$id]);
}

function cancelTrainingOccurrence($parentId, $occurrenceDate) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO trainings (training_date, training_time, is_weekly, parent_training_id, override_date, is_cancelled) VALUES (?, '00:00:00', 0, ?, ?, 1)");
    return $stmt->execute([$occurrenceDate, $parentId, $occurrenceDate]);
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
function getTeams($playerId, $isClubAdmin = false) {
    global $pdo;
    
    if ($isClubAdmin) {
        $stmt = $pdo->query("SELECT * FROM teams ORDER BY name ASC");
        return $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT t.* FROM teams t 
                                JOIN team_players tp ON t.id = tp.team_id 
                                WHERE tp.player_id = ?
                                ORDER BY t.name ASC");
        $stmt->execute([$playerId]);
        return $stmt->fetchAll();
    }
}

function createTeam($name, $logo) {
    global $pdo;
    $teamHash = createHash();
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
    $stmt = $pdo->prepare("SELECT p.*, tp.isTeamAdmin, tp.isMatchPlayer FROM players p JOIN team_players tp ON p.id = tp.player_id WHERE tp.team_id = ?");
    $stmt->execute([$teamId]);
    return $stmt->fetchAll();
}

function updateTeamPlayerRole($teamId, $playerId, $role, $value) {
    global $pdo;
    if ($role === 'isTeamAdmin') {
        $stmt = $pdo->prepare("UPDATE team_players SET isTeamAdmin = ? WHERE team_id = ? AND player_id = ?");
    } elseif ($role === 'isMatchPlayer') {
        $stmt = $pdo->prepare("UPDATE team_players SET isMatchPlayer = ? WHERE team_id = ? AND player_id = ?");
    } else {
        return false;
    }
    return $stmt->execute([$value ? 1 : 0, $teamId, $playerId]);
}

function addPlayerToTeam($teamId, $playerId,  $isMatchPlayer = false) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM team_players WHERE team_id = ? AND player_id = ?");
    $stmt->execute([$teamId, $playerId]);
    if ($stmt->fetch()) return true;

    $stmt = $pdo->prepare("INSERT INTO team_players (team_id, player_id, isTeamAdmin, isMatchPlayer) VALUES (?, ?, FALSE, ?)");
    return $stmt->execute([$teamId, $playerId, $isMatchPlayer ? 1 : 0]);
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

function createPlayer($name, $is_club_admin, $teamIds = [], $adminTeamIds = [], $voterPermissionPlayerIds = [], $matchPlayerTeamIds = []) {
    global $pdo;
    $playerHash = createHash();
    $stmt = $pdo->prepare("INSERT INTO players (name, hash, is_club_admin) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $playerHash, $is_club_admin ? 1 : 0])) {
        $playerId = $pdo->lastInsertId();
        foreach ($teamIds as $teamId) {
            addPlayerToTeam($teamId, $playerId);
        }
        foreach ($adminTeamIds as $teamId) {
            addTeamAdmin($teamId, $playerId);
        }
        foreach ($matchPlayerTeamIds as $teamId) {
            setMatchPlayer($teamId, $playerId);
        }
        foreach ($voterPermissionPlayerIds as $targetPlayerId) {
            addVoterPermission($playerId, $targetPlayerId);
        }
        return true;
    }
    return false;
}

/**
 * Generates a random hash of 16 bytes as a base64 string. This is used for player and team authentication.
 * If the length is changed, it will be only changed for new users, not for existing ones.
 *
 * @return String
 * @throws \Random\RandomException
 */
function createHash() : String {
    return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}

function updatePlayer($id, $name, $is_club_admin, $teamIds = [], $adminTeamIds = [], $voterPermissionPlayerIds = [], $matchPlayerTeamIds = []) {
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
    $stmt = $pdo->prepare("UPDATE team_players SET isTeamAdmin = FALSE WHERE player_id = ?");
    $stmt->execute([$id]);
    foreach ($adminTeamIds as $teamId) {
        addTeamAdmin($teamId, $id);
    }

    // Match-Player-Zuordnungen aktualisieren
    $stmt = $pdo->prepare("UPDATE team_players SET isMatchPlayer = FALSE WHERE player_id = ?");
    $stmt->execute([$id]);
    foreach ($matchPlayerTeamIds as $teamId) {
        setMatchPlayer($teamId, $id);
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
    // Spieler muss bereits im Team sein
    $stmt = $pdo->prepare("UPDATE team_players SET isTeamAdmin = TRUE WHERE team_id = ? AND player_id = ?");
    return $stmt->execute([$teamId, $playerId]);
}

function setMatchPlayer($teamId, $playerId) {
    global $pdo;
    // Spieler muss bereits im Team sein
    $stmt = $pdo->prepare("UPDATE team_players SET isMatchPlayer = TRUE WHERE team_id = ? AND player_id = ?");
    return $stmt->execute([$teamId, $playerId]);
}

function getTeamAdmins($teamId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.* FROM players p JOIN team_players tp ON p.id = tp.player_id WHERE tp.team_id = ? AND tp.isTeamAdmin = TRUE");
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
        setcookie('hash', $player['hash'], [
            "expires" => time() + 31536000,
            "path" => '/',
            "domain" => $_SERVER['SERVER_NAME'],
            "secure" => true,
            "httponly" => true,
            "samesite" => "Strict"
        ]);
        setcookie('hash_lastupdate', (string)time(), [
            "expires" => time() + 31536000,
            "path" => '/',
            "domain" => $_SERVER['SERVER_NAME'],
            "secure" => true,
            "httponly" => true,
            "samesite" => "Strict"
        ]);
    }

    return $player;
}

function getPlayer($id) {
    global $pdo;
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

function getPlayerTeamRoles($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT team_id, isTeamAdmin, isMatchPlayer FROM team_players WHERE player_id = ?");
    $stmt->execute([$playerId]);
    return $stmt->fetchAll();
}

function getAdminTeams($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT t.* FROM teams t JOIN team_players tp ON t.id = tp.team_id WHERE tp.player_id = ? AND tp.isTeamAdmin = TRUE");
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
        SELECT 1 FROM team_players ta
        JOIN team_players tp ON ta.team_id = tp.team_id
        WHERE ta.player_id = ? AND ta.isTeamAdmin = TRUE AND tp.player_id = ?
    ");
    $stmt->execute([$voterId, $playerId]);
    if ($stmt->fetch()) return true;

    return false;
}
?>
