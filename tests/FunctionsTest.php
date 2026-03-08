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
        $stmt = self::$pdo->prepare("SELECT * FROM team_players WHERE player_id = ? AND isTeamAdmin = TRUE");
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
        
        $this->assertIsInt(intval($result));
        
        $stmt = self::$pdo->query("SELECT COUNT(*) FROM trainings");
        $count = $stmt->fetchColumn();
        
        // Sollte ca. 52-53 Trainings in einem Jahr sein
        $this->assertGreaterThanOrEqual(1, $count);
        
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
        addPlayerToTeam($teamId, $playerBId, true);

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
        $stmt = self::$pdo->prepare("SELECT team_id FROM team_players WHERE player_id = ? AND isTeamAdmin = TRUE");
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
        
        addPlayerToTeam($teamId, $playerId);
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

    public function testUpdateMatch()
    {
        createTeam("Team 1", null);
        $teamId = self::$pdo->lastInsertId();
        $matchId = createMatch('2026-03-01', '14:00', '13:00', 'Old Opponent', true, 'Old Field', $teamId);

        $result = updateMatch($matchId, '2026-04-01', '16:00', '15:00', 'New Opponent', false, 'New Field', $teamId);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();

        $this->assertEquals('2026-04-01', $match['match_date']);
        $this->assertStringStartsWith('16:00', $match['start_time']);
        $this->assertStringStartsWith('15:00', $match['meeting_time']);
        $this->assertEquals('New Opponent', $match['opponent']);
        $this->assertEquals(0, $match['is_home_game']);
        $this->assertEquals('New Field', $match['location']);
    }

    public function testCreateTraining()
    {
        createTeam("Team T", null);
        $teamId = self::$pdo->lastInsertId();

        $trainingId = createTraining('2026-05-01', '18:00', [$teamId]);
        $this->assertNotFalse($trainingId);

        $stmt = self::$pdo->prepare("SELECT * FROM trainings WHERE id = ?");
        $stmt->execute([$trainingId]);
        $training = $stmt->fetch();

        $this->assertEquals('2026-05-01', $training['training_date']);
        $this->assertStringStartsWith('18:00', $training['training_time']);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM training_teams WHERE training_id = ?");
        $stmt->execute([$trainingId]);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testCreateTrainingWithoutTeams()
    {
        $trainingId = createTraining('2026-05-01', '18:00');
        $this->assertNotFalse($trainingId);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM training_teams WHERE training_id = ?");
        $stmt->execute([$trainingId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testUpdateTraining()
    {
        createTeam("Team U", null);
        $teamId = self::$pdo->lastInsertId();
        $trainingId = createTraining('2026-05-01', '18:00', [$teamId]);

        createTeam("Team U2", null);
        $teamId2 = self::$pdo->lastInsertId();

        $result = updateTraining($trainingId, '2026-06-01', '19:00', [$teamId2]);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT * FROM trainings WHERE id = ?");
        $stmt->execute([$trainingId]);
        $training = $stmt->fetch();

        $this->assertEquals('2026-06-01', $training['training_date']);
        $this->assertStringStartsWith('19:00', $training['training_time']);

        $teams = getEventTeams('training', $trainingId);
        $this->assertCount(1, $teams);
        $this->assertEquals($teamId2, $teams[0]);
    }

    public function testDeleteTraining()
    {
        $trainingId = createTraining('2026-05-01', '18:00');
        $result = deleteTraining($trainingId);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM trainings WHERE id = ?");
        $stmt->execute([$trainingId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testDeleteTeam()
    {
        createTeam("To Delete", null);
        $teamId = self::$pdo->lastInsertId();

        $result = deleteTeam($teamId);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM teams WHERE id = ?");
        $stmt->execute([$teamId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testUpdateTeamWithLogo()
    {
        createTeam("Old Name", "old.png");
        $teamId = self::$pdo->lastInsertId();

        $result = updateTeam($teamId, "New Name", "new.png");
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT * FROM teams WHERE id = ?");
        $stmt->execute([$teamId]);
        $team = $stmt->fetch();

        $this->assertEquals("New Name", $team['name']);
        $this->assertEquals("new.png", $team['logo']);
    }

    public function testUpdateTeamWithoutLogo()
    {
        createTeam("Old Name", "old.png");
        $teamId = self::$pdo->lastInsertId();

        $result = updateTeam($teamId, "New Name", null);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT * FROM teams WHERE id = ?");
        $stmt->execute([$teamId]);
        $team = $stmt->fetch();

        $this->assertEquals("New Name", $team['name']);
        $this->assertEquals("old.png", $team['logo']);
    }

    public function testGetTeamByHash()
    {
        createTeam("Hash Team", null);
        $teamId = self::$pdo->lastInsertId();

        $stmt = self::$pdo->prepare("SELECT hash FROM teams WHERE id = ?");
        $stmt->execute([$teamId]);
        $hash = $stmt->fetchColumn();

        $team = getTeamByHash($hash);
        $this->assertNotFalse($team);
        $this->assertEquals("Hash Team", $team['name']);
    }

    public function testGetTeamByHashNotFound()
    {
        $team = getTeamByHash('nonexistent_hash');
        $this->assertFalse($team);
    }

    public function testGetTeamPlayers()
    {
        createTeam("TP Team", null);
        $teamId = self::$pdo->lastInsertId();

        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('P1', 'tp1')");
        $p1 = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('P2', 'tp2')");
        $p2 = self::$pdo->lastInsertId();

        addPlayerToTeam($teamId, $p1);
        addPlayerToTeam($teamId, $p2, true);

        $players = getTeamPlayers($teamId);
        $this->assertCount(2, $players);
    }

    public function testAddPlayerToTeamDuplicate()
    {
        createTeam("Dup Team", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Dup', 'dup1')");
        $playerId = self::$pdo->lastInsertId();

        $result1 = addPlayerToTeam($teamId, $playerId);
        $this->assertTrue($result1);

        // Zweites Mal hinzufügen sollte true zurückgeben ohne Fehler
        $result2 = addPlayerToTeam($teamId, $playerId);
        $this->assertTrue($result2);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM team_players WHERE team_id = ? AND player_id = ?");
        $stmt->execute([$teamId, $playerId]);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testRemovePlayerFromTeam()
    {
        createTeam("Rem Team", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Rem', 'rem1')");
        $playerId = self::$pdo->lastInsertId();

        addPlayerToTeam($teamId, $playerId);
        $result = removePlayerFromTeam($playerId, $teamId);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM team_players WHERE team_id = ? AND player_id = ?");
        $stmt->execute([$teamId, $playerId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testGetAllPlayers()
    {
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Zara', 'all1')");
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Anna', 'all2')");

        $players = getAllPlayers();
        $this->assertGreaterThanOrEqual(2, count($players));
        // Sortiert nach Name ASC
        $this->assertEquals('Anna', $players[0]['name']);
    }

    public function testGetPlayerByName()
    {
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Test Spieler', 'byname1')");

        $player = getPlayerByName('test spieler');
        $this->assertNotFalse($player);
        $this->assertEquals('Test Spieler', $player['name']);
    }

    public function testGetPlayerByNameNotFound()
    {
        $player = getPlayerByName('Nicht Vorhanden');
        $this->assertFalse($player);
    }

    public function testGetPlayer()
    {
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Get Player', 'gp1')");
        $playerId = self::$pdo->lastInsertId();

        $player = getPlayer($playerId);
        $this->assertNotFalse($player);
        $this->assertEquals('Get Player', $player['name']);
    }

    public function testGetPlayerNotFound()
    {
        $player = getPlayer(99999);
        $this->assertFalse($player);
    }

    public function testDeletePlayer()
    {
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Del Player', 'del1')");
        $playerId = self::$pdo->lastInsertId();

        $result = deletePlayer($playerId);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM players WHERE id = ?");
        $stmt->execute([$playerId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testGetPlayerTeams()
    {
        createTeam("PT Team 1", null);
        $t1 = self::$pdo->lastInsertId();
        createTeam("PT Team 2", null);
        $t2 = self::$pdo->lastInsertId();

        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('PT Player', 'pt1')");
        $playerId = self::$pdo->lastInsertId();

        addPlayerToTeam($t1, $playerId);
        addPlayerToTeam($t2, $playerId);

        $teams = getPlayerTeams($playerId);
        $this->assertCount(2, $teams);
    }

    public function testGetPlayerTeamRoles()
    {
        createTeam("Role Team", null);
        $teamId = self::$pdo->lastInsertId();

        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Role Player', 'role1')");
        $playerId = self::$pdo->lastInsertId();

        addPlayerToTeam($teamId, $playerId, true);
        addTeamAdmin($teamId, $playerId);

        $roles = getPlayerTeamRoles($playerId);
        $this->assertCount(1, $roles);
        $this->assertEquals(1, $roles[0]['isTeamAdmin']);
        $this->assertEquals(1, $roles[0]['isMatchPlayer']);
    }

    public function testGetPlayerAttendance()
    {
        createTeam("Att Team", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Att Player', 'att1')");
        $playerId = self::$pdo->lastInsertId();

        $matchId = createMatch('2026-03-01', '14:00', '13:00', 'Opp', true, '', $teamId);
        updateAttendance($playerId, $playerId, 'match', $matchId, 'yes');

        $attendance = getPlayerAttendance($playerId);
        $this->assertArrayHasKey('match', $attendance);
        $this->assertEquals('yes', $attendance['match'][$matchId]);
    }

    public function testGetEventTeamsForMatch()
    {
        createTeam("ET Team", null);
        $teamId = self::$pdo->lastInsertId();
        $matchId = createMatch('2026-03-01', '14:00', '13:00', 'Opp', true, '', $teamId);

        $teams = getEventTeams('match', $matchId);
        $this->assertCount(1, $teams);
        $this->assertEquals($teamId, $teams[0]);
    }

    public function testGetEventTeamsForTraining()
    {
        createTeam("ET2 Team", null);
        $teamId = self::$pdo->lastInsertId();
        $trainingId = createTraining('2026-05-01', '18:00', [$teamId]);

        $teams = getEventTeams('training', $trainingId);
        $this->assertCount(1, $teams);
        $this->assertEquals($teamId, $teams[0]);
    }

    public function testUpdateTeamPlayerRole()
    {
        createTeam("Role2 Team", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Role2 Player', 'role2')");
        $playerId = self::$pdo->lastInsertId();
        addPlayerToTeam($teamId, $playerId);

        // Standardmäßig kein Admin
        $result = updateTeamPlayerRole($teamId, $playerId, 'isTeamAdmin', true);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT isTeamAdmin FROM team_players WHERE team_id = ? AND player_id = ?");
        $stmt->execute([$teamId, $playerId]);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testUpdateTeamPlayerRoleInvalidRole()
    {
        createTeam("Invalid Role Team", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('IR Player', 'ir1')");
        $playerId = self::$pdo->lastInsertId();
        addPlayerToTeam($teamId, $playerId);

        $result = updateTeamPlayerRole($teamId, $playerId, 'invalidRole', true);
        $this->assertFalse($result);
    }

    public function testSetMatchPlayer()
    {
        createTeam("MP Team", null);
        $teamId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('MP Player', 'mp1')");
        $playerId = self::$pdo->lastInsertId();
        addPlayerToTeam($teamId, $playerId);

        setMatchPlayer($teamId, $playerId);

        $stmt = self::$pdo->prepare("SELECT isMatchPlayer FROM team_players WHERE team_id = ? AND player_id = ?");
        $stmt->execute([$teamId, $playerId]);
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testGetTeamAdmins()
    {
        createTeam("Admin Team", null);
        $teamId = self::$pdo->lastInsertId();

        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Admin1', 'adm1')");
        $p1 = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('NonAdmin', 'adm2')");
        $p2 = self::$pdo->lastInsertId();

        addPlayerToTeam($teamId, $p1);
        addPlayerToTeam($teamId, $p2);
        addTeamAdmin($teamId, $p1);

        $admins = getTeamAdmins($teamId);
        $this->assertCount(1, $admins);
        $this->assertEquals('Admin1', $admins[0]['name']);
    }

    public function testGetTeamsAsClubAdmin()
    {
        createTeam("Visible Team", null);

        $teams = getTeams(null, true);
        $this->assertGreaterThanOrEqual(1, count($teams));
    }

    public function testGetTeamsAsPlayer()
    {
        createTeam("Player Team", null);
        $teamId = self::$pdo->lastInsertId();

        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Team Player', 'gtp1')");
        $playerId = self::$pdo->lastInsertId();
        addPlayerToTeam($teamId, $playerId);

        $teams = getTeams($playerId, false);
        $this->assertCount(1, $teams);
        $this->assertEquals("Player Team", $teams[0]['name']);
    }

    public function testCanVoteForSelf()
    {
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Self Voter', 'sv1', 0)");
        $playerId = self::$pdo->lastInsertId();

        $this->assertTrue(canVoteFor($playerId, $playerId));
    }

    public function testCanVoteForAsClubAdmin()
    {
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Club Admin', 'ca1', 1)");
        $adminId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Other', 'ca2', 0)");
        $otherId = self::$pdo->lastInsertId();

        $this->assertTrue(canVoteFor($adminId, $otherId));
    }

    public function testCanVoteForWithVoterPermission()
    {
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Voter', 'vp1', 0)");
        $voterId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Target', 'vp2', 0)");
        $targetId = self::$pdo->lastInsertId();

        self::$pdo->prepare("INSERT INTO voter_permissions (voter_id, player_id) VALUES (?, ?)")->execute([$voterId, $targetId]);

        $this->assertTrue(canVoteFor($voterId, $targetId));
    }

    public function testCanVoteForAsTeamAdmin()
    {
        createTeam("Vote Team", null);
        $teamId = self::$pdo->lastInsertId();

        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Team Admin Voter', 'tav1', 0)");
        $adminId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Team Member', 'tav2', 0)");
        $memberId = self::$pdo->lastInsertId();

        addPlayerToTeam($teamId, $adminId);
        addPlayerToTeam($teamId, $memberId);
        addTeamAdmin($teamId, $adminId);

        $this->assertTrue(canVoteFor($adminId, $memberId));
    }

    public function testCannotVoteForUnrelated()
    {
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Unrelated1', 'ur1', 0)");
        $p1 = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash, is_club_admin) VALUES ('Unrelated2', 'ur2', 0)");
        $p2 = self::$pdo->lastInsertId();

        $this->assertFalse(canVoteFor($p1, $p2));
    }

    public function testCancelTrainingOccurrence()
    {
        createTeam("Cancel Team", null);
        $teamId = self::$pdo->lastInsertId();
        $parentId = createWeeklyTrainings(1, '18:00', '2026-02-02', [$teamId]);

        $result = cancelTrainingOccurrence($parentId, '2026-02-09');
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT * FROM trainings WHERE parent_training_id = ? AND override_date = ? AND is_cancelled = 1");
        $stmt->execute([$parentId, '2026-02-09']);
        $cancelled = $stmt->fetch();
        $this->assertNotFalse($cancelled);
    }

    public function testCreateTrainingOverride()
    {
        createTeam("Override Team", null);
        $teamId = self::$pdo->lastInsertId();
        $parentId = createWeeklyTrainings(1, '18:00', '2026-02-02', [$teamId]);

        $overrideId = createTrainingOverride($parentId, '2026-02-09', '2026-02-10', '19:00', [$teamId]);
        $this->assertNotFalse($overrideId);

        $stmt = self::$pdo->prepare("SELECT * FROM trainings WHERE id = ?");
        $stmt->execute([$overrideId]);
        $override = $stmt->fetch();

        $this->assertEquals('2026-02-10', $override['training_date']);
        $this->assertStringStartsWith('19:00', $override['training_time']);
        $this->assertEquals($parentId, $override['parent_training_id']);
        $this->assertEquals('2026-02-09', $override['override_date']);
    }

    public function testDeleteWeeklyTraining()
    {
        createTeam("Del Weekly Team", null);
        $teamId = self::$pdo->lastInsertId();
        $parentId = createWeeklyTrainings(1, '18:00', '2026-02-02', [$teamId]);

        $result = deleteWeeklyTraining($parentId);
        $this->assertTrue($result);

        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM trainings WHERE id = ?");
        $stmt->execute([$parentId]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testAssignTeamsToEvent()
    {
        createTeam("Assign1", null);
        $t1 = self::$pdo->lastInsertId();
        createTeam("Assign2", null);
        $t2 = self::$pdo->lastInsertId();

        $trainingId = createTraining('2026-05-01', '18:00');

        assignTeamsToEvent($trainingId, 'training', [$t1, $t2]);
        $teams = getEventTeams('training', $trainingId);
        $this->assertCount(2, $teams);

        // Reassign nur ein Team
        assignTeamsToEvent($trainingId, 'training', [$t1]);
        $teams = getEventTeams('training', $trainingId);
        $this->assertCount(1, $teams);
    }

    public function testGetVoterPermissions()
    {
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Voter GP', 'gpv1')");
        $voterId = self::$pdo->lastInsertId();
        self::$pdo->exec("INSERT INTO players (name, hash) VALUES ('Target GP', 'gpv2')");
        $targetId = self::$pdo->lastInsertId();

        self::$pdo->prepare("INSERT INTO voter_permissions (voter_id, player_id) VALUES (?, ?)")->execute([$voterId, $targetId]);

        $permissions = getVoterPermissions($voterId);
        $this->assertCount(1, $permissions);
        $this->assertEquals($targetId, $permissions[0]);
    }

    public function testCreateHash()
    {
        $hash1 = createHash();
        $hash2 = createHash();

        $this->assertNotEmpty($hash1);
        $this->assertNotEmpty($hash2);
        $this->assertNotEquals($hash1, $hash2);
    }
}
