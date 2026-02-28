<?php

namespace Tests;

class FunctionsTest extends DatabaseTestCase
{
    public function testCreateTeam()
    {
        $name = "Test Team";
        $logo = "logo.png";
        
        $result = createTeam($name, $logo);
        
        $this->assertTrue($result);
        
        $stmt = self::$pdo->query("SELECT * FROM teams WHERE name = 'Test Team'");
        $team = $stmt->fetch();
        
        $this->assertNotFalse($team);
        $this->assertEquals($name, $team['name']);
        $this->assertEquals($logo, $team['logo']);
        $this->assertNotEmpty($team['hash']);
    }

    public function testCreatePlayer()
    {
        createTeam("Team 1", null);
        $teamId = self::$pdo->lastInsertId();
        
        $name = "Max Mustermann";
        $isClubAdmin = true;
        $teamIds = [$teamId];
        $adminTeamIds = [$teamId];
        
        $result = createPlayer($name, $isClubAdmin, $teamIds, $adminTeamIds);
        
        $this->assertTrue($result);
        
        $stmt = self::$pdo->prepare("SELECT * FROM players WHERE name = ?");
        $stmt->execute([$name]);
        $player = $stmt->fetch();
        
        $this->assertNotFalse($player);
        $this->assertEquals(1, $player['is_club_admin']);
        
        // Check team assignment
        $stmt = self::$pdo->prepare("SELECT * FROM team_players WHERE player_id = ?");
        $stmt->execute([$player['id']]);
        $this->assertCount(1, $stmt->fetchAll());

        // Check admin assignment
        $stmt = self::$pdo->prepare("SELECT * FROM team_admins WHERE player_id = ?");
        $stmt->execute([$player['id']]);
        $this->assertCount(1, $stmt->fetchAll());
    }

    public function testCreateWeeklyTrainings()
    {
        createTeam("Team 1", null);
        $teamId = self::$pdo->lastInsertId();
        
        // Montag (1), 18:00, Start 2026-02-02 (ein Montag)
        $dayOfWeek = 1; 
        $time = "18:00";
        $startDate = "2026-02-02";
        $teamIds = [$teamId];
        
        $result = createWeeklyTrainings($dayOfWeek, $time, $startDate, $teamIds);
        
        $this->assertTrue($result);
        
        $stmt = self::$pdo->query("SELECT COUNT(*) FROM trainings");
        $count = $stmt->fetchColumn();
        
        // Sollte ca. 52-53 Trainings in einem Jahr sein
        $this->assertGreaterThanOrEqual(52, $count);
        
        $stmt = self::$pdo->query("SELECT * FROM trainings ORDER BY training_date ASC LIMIT 1");
        $training = $stmt->fetch();
        $this->assertEquals("2026-02-02", $training['training_date']);
        
        // Check if teams assigned
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM training_teams WHERE training_id = ?");
        $stmt->execute([$training['id']]);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testUpdateAttendance()
    {
        // Setup player and match
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Player 1', 'hash1')");
        $playerId = self::$pdo->lastInsertId();
        createTeam("Team 1", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO matches (match_date, start_time, meeting_time, opponent, is_home_game, team_id) VALUES ('2026-02-01', '14:00', '13:00', 'Opponent', 1, $teamId)");
        $matchId = self::$pdo->lastInsertId();
        
        // Test voting 'yes'
        updateAttendance($playerId, $playerId, 'match', $matchId, 'yes');
        
        $stmt = self::$pdo->prepare("SELECT status FROM attendance WHERE player_id = ? AND event_type = 'match' AND event_id = ?");
        $stmt->execute([$playerId, $matchId]);
        $this->assertEquals('yes', $stmt->fetchColumn());
        
        // Test updating to 'no'
        updateAttendance($playerId, $playerId, 'match', $matchId, 'no');
        $stmt->execute([$playerId, $matchId]);
        $this->assertEquals('no', $stmt->fetchColumn());
    }

    public function testGetAttendance()
    {
        // Setup team, player and match
        createTeam("Team A", null);
        $teamId = self::$pdo->lastInsertId();
        
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Player A', 'hashA')");
        $playerId = self::$pdo->lastInsertId();
        addPlayerToTeam($teamId, $playerId);
        
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Player B', 'hashB')");
        $playerBId = self::$pdo->lastInsertId();
        addPlayerToTeam($teamId, $playerBId);
        
        $matchId = createMatch('2026-02-01', '14:00', '13:00', 'Opponent', 1, '', $teamId);
        
        // One votes yes
        updateAttendance($playerId, $playerId, 'match', $matchId, 'yes');
        
        $attendance = getAttendance('match', $matchId);
        
        $this->assertCount(2, $attendance);
        
        $playerAVote = array_filter($attendance, fn($a) => $a['player_id'] == $playerId);
        $playerBVote = array_filter($attendance, fn($a) => $a['player_id'] == $playerBId);
        
        $this->assertEquals('yes', reset($playerAVote)['status']);
        $this->assertEquals('none', reset($playerBVote)['status']);
    }

    public function testUpdatePlayer()
    {
        createTeam("Team 1", null);
        $teamId1 = self::$pdo->lastInsertId();
        createTeam("Team 2", null);
        $teamId2 = self::$pdo->lastInsertId();
        
        createPlayer("Original Name", false, [$teamId1]);
        $playerId = self::$pdo->lastInsertId();
        
        // Update name and teams
        updatePlayer($playerId, "New Name", true, [$teamId2], [$teamId2]);
        
        $stmt = self::$pdo->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->execute([$playerId]);
        $player = $stmt->fetch();
        
        $this->assertEquals("New Name", $player['name']);
        $this->assertEquals(1, $player['is_club_admin']);
        
        // Check new team assignment
        $stmt = self::$pdo->prepare("SELECT team_id FROM team_players WHERE player_id = ?");
        $stmt->execute([$playerId]);
        $teams = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains((string)$teamId2, array_map('strval', $teams));
        $this->assertNotContains((string)$teamId1, array_map('strval', $teams));
        
        // Check new admin assignment
        $stmt = self::$pdo->prepare("SELECT team_id FROM team_admins WHERE player_id = ?");
        $stmt->execute([$playerId]);
        $admins = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains((string)$teamId2, array_map('strval', $admins));
    }

    public function testDeleteMatch()
    {
        createTeam("Team 1", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO matches (match_date, start_time, meeting_time, opponent, is_home_game, team_id) VALUES ('2026-02-01', '14:00', '13:00', 'Opponent', 1, $teamId)");
        $matchId = self::$pdo->lastInsertId();
        
        $result = deleteMatch($matchId);
        $this->assertTrue($result);
        
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM matches WHERE id = ?");
        $stmt->execute([$matchId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testTeamDeletionCascadesToMatches()
    {
        createTeam("Team to Delete", null);
        $teamId = self::$pdo->lastInsertId();
        
        createMatch('2026-02-01', '14:00', '13:00', 'Opponent', 1, '', $teamId);
        $matchId = self::$pdo->lastInsertId();
        
        // Verify match exists
        $this->assertEquals(1, self::$pdo->query("SELECT COUNT(*) FROM matches WHERE id = $matchId")->fetchColumn());
        
        // Delete team
        deleteTeam($teamId);
        
        // Verify match is gone
        $this->assertEquals(0, self::$pdo->query("SELECT COUNT(*) FROM matches WHERE id = $matchId")->fetchColumn());
    }

    public function testGetAdminTeams()
    {
        createTeam("Team Admin", null);
        $teamId = self::$pdo->lastInsertId();
        
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Admin Player', 'hashAdmin')");
        $playerId = self::$pdo->lastInsertId();
        
        addTeamAdmin($teamId, $playerId);
        
        $teams = getAdminTeams($playerId);
        $this->assertCount(1, $teams);
        $this->assertEquals("Team Admin", $teams[0]['name']);
    }

    public function testCreateMatchWithoutMeetingTime()
    {
        createTeam("Team Match", null);
        $teamId = self::$pdo->lastInsertId();
        
        $matchId = createMatch('2026-03-01', '18:00', '', 'No Meeting Opponent', 1, 'Local Field', $teamId);
        $this->assertNotFalse($matchId);
        
        $stmt = self::$pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();
        
        $this->assertNull($match['meeting_time']);
        $this->assertStringStartsWith('18:00', $match['start_time']);
    }
}
