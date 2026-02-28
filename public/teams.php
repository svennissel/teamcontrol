<?php
$title = "Mannschaften";
require_once './includes/header.php';



$player = getLoggedInPlayer();
if (!$player) {
    header('Location: login.php');
    exit;
}
$player_id = $player['id'];

if (!isClubAdmin() && !isAnyTeamAdmin($player_id)) {
    header('Location: games.php');
    exit;
}


$all_players = getAllPlayers();
$playerTeams = getPlayerTeams($player_id);
$teams = getTeams($player_id, isClubAdmin());


printHeader($player, $playerTeams, "teams");
?>

<div id="teams" class="tab-content active">
    <section>
        <div class="events">
            <?php foreach ($teams as $team): ?>
                <div class="event-card">
                    <div class="card-header">
                        <h3>
                            <?php if ($team['logo']): ?>
                                <img src="uploads/logos/<?php echo htmlspecialchars($team['logo']); ?>" alt="Logo" class="team-logo-inline">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($team['name']); ?>
                        </h3>
                        <div class="club-admin-actions">
                            <?php if (isClubAdmin()): ?>
                                <button class="edit-btn" onclick='editTeam(<?php echo json_encode($team); ?>)' title="Bearbeiten">✎</button>
                                <form action="action.php" method="POST" class="inline-form" onsubmit="confirmDelete(event, 'Soll diese Mannschaft wirklich gelöscht werden?')">
                                    <input type="hidden" name="action" value="delete_team">
                                    <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                    <button type="submit" class="delete-btn" id="delete-team-btn" title="Löschen">🗑</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-details">
                        <h4>Mannschaftsadmin(s)</h4>
                        <ul class="team-list">
                            <?php
                            $teamAdmins = getTeamAdmins($team['id']);
                            if (empty($teamAdmins)): ?>
                                <li class="team-list-item empty">Keine</li>
                            <?php else:
                                foreach ($teamAdmins as $ta): ?>
                                    <li class="team-list-item">
                                        <?php echo htmlspecialchars($ta['name']); ?>
                                    </li>
                                <?php endforeach;
                            endif; ?>
                        </ul>
                        <h4>Spieler</h4>
                        <ul class="team-list">
                            <?php
                            $teamPlayers = getTeamPlayers($team['id']);
                            $isTeamAdminOfThisTeam = isTeamAdmin($team['id'], $player_id);
                            foreach ($teamPlayers as $tp): ?>
                                <li class="team-list-item">
                                    <?php echo htmlspecialchars($tp['name']); ?>
                                    <?php if ($isTeamAdminOfThisTeam): ?>
                                        <form action="action.php" method="POST" class="inline-form">
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <input type="hidden" name="player_id" value="<?php echo $tp['id']; ?>">
                                            <button type="submit" class="delete-btn">&times;</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($isTeamAdminOfThisTeam): ?>
                            <form action="action.php" method="POST" class="team-assign-form">
                                <input type="hidden" name="action" value="assign_player">
                                <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                <select name="player_id" required>
                                    <option value="">Spieler wählen...</option>
                                    <?php foreach ($all_players as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-add">+</button>
                            </form>
                        <?php endif; ?>
                        <?php if (isTeamAdmin($team['id'], $player_id)):
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                            $registrationUrl = $protocol . "://" . $host . $path . "/register_team.php?hash=" . $team['hash'];
                            ?>
                            <p class="reg-link-label"><strong>Anmeldelink für neue Spieler:</strong>
                            <div class="reg-link-row">
                                <input type="text" readonly value="<?php echo htmlspecialchars($registrationUrl); ?>" id="reg-link-<?php echo $team['id']; ?>">
                                <button type="button" class="edit-btn" onclick="copyToClipboard('reg-link-<?php echo $team['id']; ?>')" title="Link kopieren">📋</button>
                            </div>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include './includes/footer.php'; ?>
