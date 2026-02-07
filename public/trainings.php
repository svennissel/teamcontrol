<?php
$title = "Training";
require_once './includes/header.php';

$player = getLoggedInPlayer();
if (!$player) {
    header('Location: login.php');
    exit;
}
$player_id = $player['id'];

$playerTeams = getPlayerTeams($player_id);
$teams = getTeams($player_id, isClubAdmin());
$showTeamInCards = count($playerTeams) !== 1;
$trainings = getTrainings($player_id, isClubAdmin());
$myAttendance = getPlayerAttendance($player_id);

// Teams sammeln, die in den Trainings vorkommen
$displayedTeamIds = [];
foreach ($trainings as $t) {
    if (!empty($t['teams'])) {
        foreach ($t['teams'] as $tid) {
            $displayedTeamIds[] = $tid;
        }
    }
}
$displayedTeamIds = array_unique($displayedTeamIds);

$displayedTeams = [];
if (count($displayedTeamIds) > 1) {
    foreach ($displayedTeamIds as $tid) {
        foreach ($teams as $t) {
            if ($t['id'] == $tid) {
                $displayedTeams[] = $t;
                break;
            }
        }
    }
}

printHeader($player, $playerTeams, "trainings");
?>

<div id="training" class="tab-content active">
    <section>
        <?php if (!empty($displayedTeams)): ?>
            <div class="filter-container">
                <button class="filter-btn active" onclick="filterEvents('all', this)">Alle</button>
                <?php foreach ($displayedTeams as $t): ?>
                    <button class="filter-btn" onclick="filterEvents('team-<?php echo $t['id']; ?>', this)"><?php echo htmlspecialchars($t['name']); ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="events">
            <?php foreach ($trainings as $training): ?>
                <?php
                $teamClasses = '';
                if (!empty($training['teams'])) {
                    foreach ($training['teams'] as $tid) {
                        $teamClasses .= ' team-' . $tid;
                    }
                }
                ?>
                <div class="event-card<?php echo $teamClasses; ?>">
                    <div class="card-header">

                        <?php if ($showTeamInCards && !empty($training['teams'])): ?>
                            <div class="event-teams" style="font-size: 0.8rem; color: #777; margin-bottom: 5px;">
                                <?php
                                $teamNames = [];
                                foreach ($training['teams'] as $tid) {
                                    foreach ($teams as $t) {
                                        if ($t['id'] == $tid) $teamNames[] = htmlspecialchars($t['name']);
                                    }
                                }
                                echo implode(', ', $teamNames);
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php
                        $canEditTraining = isClubAdmin();
                        if (!$canEditTraining && isAnyTeamAdmin($player_id)) {
                            foreach ($training['teams'] as $tid) {
                                if (isTeamAdmin($tid, $player_id)) {
                                    $canEditTraining = true;
                                    break;
                                }
                            }
                        }
                        if ($canEditTraining): ?>
                            <div class="club-admin-actions">
                                <button class="edit-btn" onclick='editTraining(<?php echo json_encode($training); ?>)' title="Bearbeiten">✎</button>
                                <form action="action.php" method="POST" style="display:inline;" onsubmit="return confirm('Soll dieses Training wirklich gelöscht werden?');">
                                    <input type="hidden" name="action" value="delete_training">
                                    <input type="hidden" name="training_id" value="<?php echo $training['id']; ?>">
                                    <button type="submit" class="delete-btn" title="Löschen">🗑</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-details">

                        <div class="time-tiles">
                        <span class="event-date-inline">
                            <?php
                            $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
                            $timestamp = strtotime($training['training_date']);
                            echo $days[date('w', $timestamp)] . ' ' . date('d.m.Y', $timestamp);
                            ?>
                        </span>
                            <div class="time-tile">
                                <span class="label">Zeit</span>
                                <span class="time"><?php echo substr($training['training_time'], 0, 5); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php
                    $attendance = getAttendance('training', $training['id']);
                    $counts = ['yes' => 0, 'no' => 0, 'maybe' => 0, 'none' => 0];
                    foreach ($attendance as $a) { $counts[$a['status']]++; }
                    ?>

                    <div class="vote-buttons">
                        <?php
                        $playerInTrainingTeam = false;
                        if (!empty($playerTeams) && !empty($training['teams'])) {
                            $playerTeamIds = array_column($playerTeams, 'id');
                            foreach ($training['teams'] as $ttid) {
                                if (in_array($ttid, $playerTeamIds)) {
                                    $playerInTrainingTeam = true;
                                    break;
                                }
                            }
                        }
                        if ($playerInTrainingTeam): ?>
                            <?php 
                            // Erlaubte Zielspieler (inkl. man selbst) für dieses Event ermitteln
                            $voteTargets = [];
                            foreach ($attendance as $a) {
                                if (canVoteFor($player_id, $a['player_id'])) {
                                    $voteTargets[] = $a;
                                }
                            }
                            ?>
                            <?php
                            $voteTargetsForJs = array_map(function($t) {
                                return ['id' => $t['player_id'], 'name' => $t['name']];
                            }, $voteTargets);
                            ?>
                            <form action="action.php" method="POST" class="vote-form" data-vote-targets='<?php echo htmlspecialchars(json_encode($voteTargetsForJs), ENT_QUOTES, 'UTF-8'); ?>' onsubmit="handleVote(event)">
                                <input type="hidden" name="action" value="vote">
                                <input type="hidden" name="event_type" value="training">
                                <input type="hidden" name="event_id" value="<?php echo $training['id']; ?>">
                                <input type="hidden" name="target_player_id" value="<?php echo $player_id; ?>">
                                <button type="submit" name="status" value="yes" title="Zusage" class="<?php echo (isset($myAttendance['training'][$training['id']]) && $myAttendance['training'][$training['id']] === 'yes') ? 'active' : ''; ?>">👍 <span class="count"><?php echo $counts['yes']; ?></span></button>
                                <button type="submit" name="status" value="maybe" title="Vielleicht" class="<?php echo (isset($myAttendance['training'][$training['id']]) && $myAttendance['training'][$training['id']] === 'maybe') ? 'active' : ''; ?>">❓ <span class="count"><?php echo $counts['maybe']; ?></span></button>
                                <button type="submit" name="status" value="no" title="Absage" class="<?php echo (isset($myAttendance['training'][$training['id']]) && $myAttendance['training'][$training['id']] === 'no') ? 'active' : ''; ?>">👎 <span class="count"><?php echo $counts['no']; ?></span></button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn-attendance" title="Teilnehmerliste" onclick='showAttendance(<?php echo json_encode($attendance); ?>, "Training <?php
                        $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
                        $timestamp = strtotime($training['training_date']);
                        echo $days[date('w', $timestamp)] . ' ' . date('d.m.Y', $timestamp);
                        ?>")'>👥</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include './includes/footer.php'; ?>
