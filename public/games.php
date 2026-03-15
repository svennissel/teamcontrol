<?php
$title = "Spiele";
require_once './includes/header.php';

$player = getLoggedInPlayer();
if (!$player) {
    header('Location: login.php');
    exit;
}
$player_id = $player['id'];
$isClubAdmin = $player['is_club_admin'];
$matches = getMatches($player_id);
$myAttendance = getPlayerAttendance($player_id);
$playerTeams = getPlayerTeams($player_id);
$teams = getTeams($player_id, $isClubAdmin);

// Batch: Alle Attendance-Daten und Voter-Daten vorladen
$matchIds = array_column($matches, 'id');
$allAttendance = getAttendanceBatchForMatches($matchIds);
$voterData = loadVoterData($player_id);

printHeader($player, $playerTeams, "games");
?>

<div id="player" class="tab-content active">
    <section>
        <div class="events">
            <?php foreach ($matches as $match): ?>
                <div class="event-card">
                    <div class="card-header">
                        <h3>
                            <?php echo htmlspecialchars($match['opponent']); ?>
                        </h3>

                        <?php
                        $canEditMatch = $isClubAdmin || isTeamAdmin($match['team_id'], $player_id);
                        if ($canEditMatch): ?>
                            <div class="club-admin-actions">
                                <button class="edit-btn" onclick='editMatch(<?php echo json_encode($match); ?>)' title="Bearbeiten"><i class="fa-solid fa-pen"></i></button>
                                <form action="action.php" method="POST" class="inline-form" onsubmit="confirmDelete(event, 'Soll dieses Spiel wirklich gelöscht werden?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete_match">
                                    <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                    <button type="submit" class="delete-btn" title="Löschen"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-subtitle">
                        <div class="event-type">
                            <?php echo $match['is_home_game'] ? 'Heim' : 'Auswärts'; ?>
                        </div>
                        <div class="event-teams">
                            <?php
                            foreach ($teams as $t) {
                                if ($t['id'] == $match['team_id']) {
                                    echo htmlspecialchars($t['name']);
                                    break;
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <div class="card-details">
                        <div class="time-tiles">
                         <span class="event-date-inline">
                            <?php
                            $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
                            $timestamp = strtotime($match['match_date']);
                            echo $days[date('w', $timestamp)] . ' ' . date('d.m.y', $timestamp);
                            ?>
                        </span>
                            <?php if (!empty($match['meeting_time'])): ?>
                            <div class="time-tile">
                                <span class="label">Treffen</span>
                                <span class="time"><?php echo substr($match['meeting_time'], 0, 5); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="time-tile">
                                <span class="label">Beginn</span>
                                <span class="time"><?php echo substr($match['start_time'], 0, 5); ?></span>
                            </div>
                        </div>
                        <?php if (!$match['is_home_game'] && !empty($match['location'])): ?>
                        <div class="location-info">
                            <i class="fa-solid fa-location-dot"></i> <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($match['location']); ?>" target="_blank" class="location-link"><?php echo htmlspecialchars($match['location']); ?></a>
                        </div>
                        <?php else: ?>
                            <div class="location-info location-info-empty">&nbsp;</div>
                        <?php endif; ?>


                    </div>

                    <?php
                    $attendance = $allAttendance[$match['id']] ?? [];
                    $counts = ['yes' => 0, 'no' => 0, 'maybe' => 0, 'none' => 0];
                    foreach ($attendance as $a) { $counts[$a['status']]++; }
                    ?>

                    <div class="vote-buttons">
                        <?php
                        $playerInMatchTeam = false;
                        if (!empty($playerTeams)) {
                            $playerTeamIds = array_column($playerTeams, 'id');
                            if (in_array($match['team_id'], $playerTeamIds)) {
                                $playerInMatchTeam = true;
                            }
                        }
                        if ($playerInMatchTeam): ?>
                            <?php 
                            // Erlaubte Zielspieler (inkl. man selbst) für dieses Event ermitteln
                            $voteTargets = [];
                            foreach ($attendance as $a) {
                                if (canVoteForWithData($voterData, $a['player_id'])) {
                                    $voteTargets[] = $a;
                                }
                            }
                            ?>
                            <?php
                            $voteTargetsForJs = array_map(function($t) {
                                return ['id' => $t['player_id'], 'name' => $t['name']];
                            }, $voteTargets);
                            ?>
                            <form action="action.php" method="POST" class="vote-form" data-vote-targets='<?php echo htmlspecialchars(json_encode($voteTargetsForJs), ENT_QUOTES, 'UTF-8'); ?>' data-default-player-id="<?php echo $player_id; ?>" onsubmit="handleVote(event)">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="vote">
                                <input type="hidden" name="event_type" value="match">
                                <input type="hidden" name="event_id" value="<?php echo $match['id']; ?>">
                                <input type="hidden" name="target_player_id" value="<?php echo $player_id; ?>">
                                <button type="submit" name="status" value="yes" title="Zusage" class="<?php echo (isset($myAttendance['match'][$match['id']]) && $myAttendance['match'][$match['id']] === 'yes') ? 'active' : ''; ?>"><i class="fa-solid fa-thumbs-up"></i> <span class="count"><?php echo $counts['yes']; ?></span></button>
                                <button type="submit" name="status" value="maybe" title="Vielleicht" class="<?php echo (isset($myAttendance['match'][$match['id']]) && $myAttendance['match'][$match['id']] === 'maybe') ? 'active' : ''; ?>"><i class="fa-solid fa-question"></i> <span class="count"><?php echo $counts['maybe']; ?></span></button>
                                <button type="submit" name="status" value="no" title="Absage" class="<?php echo (isset($myAttendance['match'][$match['id']]) && $myAttendance['match'][$match['id']] === 'no') ? 'active' : ''; ?>"><i class="fa-solid fa-thumbs-down"></i> <span class="count"><?php echo $counts['no']; ?></span></button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn-attendance" title="Teilnehmerliste" onclick='showAttendance(<?php echo json_encode($attendance); ?>, "<?php echo htmlspecialchars($match['opponent']); ?> (<?php echo $match['is_home_game'] ? 'Heim' : 'Auswärts'; ?>) <?php
                        $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
                        $timestamp = strtotime($match['match_date']);
                        echo $days[date('w', $timestamp)] . ' ' . date('d.m.Y', $timestamp);
                        ?>")'><i class="fa-solid fa-users"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include './includes/footer.php'; ?>
