<?php
require_once './includes/auth.php';
require_once './includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $player = getLoggedInPlayer();
    if (!$player) {
        exit;
    }
    $player_id = $player['id'];
    
    if ($_POST['action'] === 'vote') {
        $eventType = $_POST['event_type'];
        $eventId = (int)$_POST['event_id'];
        $status = $_POST['status'];
        
        if (in_array($status, ['yes', 'no', 'maybe']) && in_array($eventType, ['match', 'training'])) {
            updateAttendance($player_id, $eventType, $eventId, $status);
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            if (in_array($status, ['yes', 'no', 'maybe']) && in_array($eventType, ['match', 'training'])) {
                $attendance = getAttendance($eventType, $eventId);
                $counts = ['yes' => 0, 'no' => 0, 'maybe' => 0, 'none' => 0];
                foreach ($attendance as $a) { $counts[$a['status']]++; }
                echo json_encode(['success' => true, 'counts' => $counts, 'attendance' => $attendance]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            }
            exit;
        }
    } elseif (isClubAdmin() || isAnyTeamAdmin($player_id)) {
        if ($_POST['action'] === 'add_match') {
            $team_id = (int)($_POST['team_id'] ?? 0);
            if (!isClubAdmin()) {
                $my_admin_teams = getAdminTeams($player_id);
                $my_admin_team_ids = array_column($my_admin_teams, 'id');
                if (!in_array($team_id, $my_admin_team_ids)) {
                    $team_id = 0;
                }
            }
            if ($team_id > 0) {
                createMatch($_POST['match_date'], $_POST['start_time'], $_POST['meeting_time'], $_POST['opponent'], isset($_POST['is_home_game']), $_POST['location'], $team_id);
            }
        } elseif ($_POST['action'] === 'edit_match') {
            $matchId = (int)$_POST['match_id'];
            $canEdit = isClubAdmin();
            if (!$canEdit) {
                $eventTeams = getEventTeams('match', $matchId);
                foreach ($eventTeams as $tId) {
                    if (isTeamAdmin($tId, $player_id)) {
                        $canEdit = true;
                        break;
                    }
                }
            }

            if ($canEdit) {
                $team_id = (int)($_POST['team_id'] ?? 0);
                if (!isClubAdmin()) {
                    $my_admin_teams = getAdminTeams($player_id);
                    $my_admin_team_ids = array_column($my_admin_teams, 'id');
                    if (!in_array($team_id, $my_admin_team_ids)) {
                        // Wenn der User kein Admin für das neu gewählte Team ist, behalten wir das alte Team bei (oder verweigern das Update)
                        // Hier nehmen wir zur Sicherheit das aktuell zugeordnete Team wenn ungültig.
                        $currentTeams = getEventTeams('match', $matchId);
                        $team_id = !empty($currentTeams) ? $currentTeams[0] : 0;
                    }
                }
                if ($team_id > 0) {
                    updateMatch($matchId, $_POST['match_date'], $_POST['start_time'], $_POST['meeting_time'], $_POST['opponent'], isset($_POST['is_home_game']), $_POST['location'], $team_id);
                }
            }
        } elseif ($_POST['action'] === 'add_training') {
            $team_ids = $_POST['team_ids'] ?? [];
            if (!isClubAdmin()) {
                $my_admin_teams = getAdminTeams($player_id);
                $my_admin_team_ids = array_column($my_admin_teams, 'id');
                $team_ids = array_intersect($team_ids, $my_admin_team_ids);
            }
            if (!empty($team_ids)) {
                if (isset($_POST['training_type']) && $_POST['training_type'] === 'weekly') {
                    $dayOfWeek = (int)$_POST['day_of_week'];
                    $time = $_POST['training_time'];
                    $startDate = $_POST['start_date'] ?: date('Y-m-d');
                    createWeeklyTrainings($dayOfWeek, $time, $startDate, $team_ids);
                } else {
                    createTraining($_POST['training_date'], $_POST['training_time'], $team_ids);
                }
            }
        } elseif ($_POST['action'] === 'edit_training') {
            $trainingId = (int)$_POST['training_id'];
            $canEdit = isClubAdmin();
            if (!$canEdit) {
                $eventTeams = getEventTeams('training', $trainingId);
                foreach ($eventTeams as $tId) {
                    if (isTeamAdmin($tId, $player_id)) {
                        $canEdit = true;
                        break;
                    }
                }
            }
            if ($canEdit) {
                $team_ids = $_POST['team_ids'] ?? [];
                if (!isClubAdmin()) {
                    $my_admin_teams = getAdminTeams($player_id);
                    $my_admin_team_ids = array_column($my_admin_teams, 'id');
                    $eventTeams = getEventTeams('training', $trainingId);
                    $other_teams = array_diff($eventTeams, $my_admin_team_ids);
                    $validated_requested_teams = array_intersect($team_ids, $my_admin_team_ids);
                    $team_ids = array_unique(array_merge($validated_requested_teams, $other_teams));
                }
                updateTraining($trainingId, $_POST['training_date'], $_POST['training_time'], $team_ids);
            }
        } elseif ($_POST['action'] === 'delete_match') {
            $matchId = (int)$_POST['match_id'];
            $canDelete = isClubAdmin();
            if (!$canDelete) {
                $eventTeams = getEventTeams('match', $matchId);
                foreach ($eventTeams as $tId) {
                    if (isTeamAdmin($tId, $player_id)) {
                        $canDelete = true;
                        break;
                    }
                }
            }
            if ($canDelete) deleteMatch($matchId);
        } elseif ($_POST['action'] === 'delete_training') {
            $trainingId = (int)$_POST['training_id'];
            $canDelete = isClubAdmin();
            if (!$canDelete) {
                $eventTeams = getEventTeams('training', $trainingId);
                foreach ($eventTeams as $tId) {
                    if (isTeamAdmin($tId, $player_id)) {
                        $canDelete = true;
                        break;
                    }
                }
            }
            if ($canDelete) deleteTraining($trainingId);
        } elseif ($_POST['action'] === 'add_team' && isClubAdmin()) {
            $logo = '';
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logo = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], 'uploads/logos/' . $logo);
            }
            createTeam($_POST['name'], $logo);
        } elseif ($_POST['action'] === 'edit_team' && isClubAdmin()) {
            $logo = '';
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logo = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], 'uploads/logos/' . $logo);
            }
            updateTeam((int)$_POST['team_id'], $_POST['name'], $logo);
        } elseif ($_POST['action'] === 'delete_team' && isClubAdmin()) {
            deleteTeam((int)$_POST['team_id']);
        } elseif ($_POST['action'] === 'add_player') {
            if ((isClubAdmin() || isAnyTeamAdmin($player_id)) && !empty($_POST['team_ids'])) {
                $is_club_admin = isset($_POST['is_club_admin']) && isClubAdmin();
                if (isClubAdmin()) {
                    $admin_team_ids = $_POST['admin_team_ids'] ?? [];
                } else {
                    $my_admin_teams = getAdminTeams($player_id);
                    $my_admin_team_ids = array_column($my_admin_teams, 'id');
                    $requested_admin_team_ids = $_POST['admin_team_ids'] ?? [];
                    $admin_team_ids = array_intersect($requested_admin_team_ids, $my_admin_team_ids);
                }
                createPlayer($_POST['name'], $is_club_admin, $_POST['team_ids'], $admin_team_ids);
            }
        } elseif ($_POST['action'] === 'edit_player') {

            $canEdit = isClubAdmin();

            if ($player) {
                if (!$canEdit && isAnyTeamAdmin($player_id)) {
                    $pTeams = getPlayerTeams($player_id);
                    foreach ($pTeams as $pt) {
                        if (isTeamAdmin($pt['id'], $player_id)) {
                            $canEdit = true;
                            break;
                        }
                    }
                }
                if ($canEdit && !empty($_POST['team_ids'])) {
                    $id = (int)$_POST['player_id'];
                    $name = $_POST['name'];
                    if (isClubAdmin()) {
                        $is_club_admin = isset($_POST['is_club_admin']) && $_POST['is_club_admin'];
                    } else {
                        $is_club_admin = false;
                    }
                    $is_club_admin = $is_club_admin ? 1 : 0;
                    $team_ids = $_POST['team_ids'] ?? [];
                    $admin_team_ids = $_POST['admin_team_ids'] ?? [];

                    updatePlayer($id, $name, $is_club_admin, $team_ids, $admin_team_ids);
                }
            }
        } elseif ($_POST['action'] === 'remove_player') {
            $teamId = (int)$_POST['team_id'];
            $playerId = (int)$_POST['player_id'];
            if (isClubAdmin() || isTeamAdmin($teamId, $player_id)) {
                removePlayerFromTeam($playerId, $teamId);
            }
        } elseif ($_POST['action'] === 'assign_player') {
            $teamId = (int)$_POST['team_id'];
            $playerId = (int)$_POST['player_id'];
            if (isClubAdmin() || isTeamAdmin($teamId, $player_id)) {
                addPlayerToTeam($teamId, $playerId);
            }
        } elseif ($_POST['action'] === 'delete_player' && isClubAdmin()) {
            deletePlayer((int)$_POST['player_id']);
        }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
