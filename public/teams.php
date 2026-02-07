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
                                <img src="uploads/logos/<?php echo htmlspecialchars($team['logo']); ?>" alt="Logo" style="width:30px; height:30px; vertical-align: middle; margin-right: 10px; border-radius: 50%;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($team['name']); ?>
                        </h3>
                        <div class="club-admin-actions">
                            <?php if (isClubAdmin()): ?>
                                <button class="edit-btn" onclick='editTeam(<?php echo json_encode($team); ?>)' title="Bearbeiten">✎</button>
                                <form action="action.php" method="POST" style="display:inline;" onsubmit="return confirm('Soll diese Mannschaft wirklich gelöscht werden?');">
                                    <input type="hidden" name="action" value="delete_team">
                                    <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                    <button type="submit" class="delete-btn" id="delete-team-btn" title="Löschen">🗑</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-details">
                        <h4>Mannschaftsadmin(s)</h4>
                        <ul style="list-style: none; padding: 0;">
                            <?php
                            $teamAdmins = getTeamAdmins($team['id']);
                            if (empty($teamAdmins)): ?>
                                <li style="background: #f9f9f9; padding: 5px; border-radius: 4px; color: #777;">Keine</li>
                            <?php else:
                                foreach ($teamAdmins as $ta): ?>
                                    <li style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; background: #f9f9f9; padding: 5px; border-radius: 4px;">
                                        <?php echo htmlspecialchars($ta['name']); ?>
                                    </li>
                                <?php endforeach;
                            endif; ?>
                        </ul>
                        <h4>Spieler</h4>
                        <ul style="list-style: none; padding: 0;">
                            <?php
                            $teamPlayers = getTeamPlayers($team['id']);
                            $isTeamAdminOfThisTeam = isTeamAdmin($team['id'], $player_id);
                            foreach ($teamPlayers as $tp): ?>
                                <li style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; background: #f9f9f9; padding: 5px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($tp['name']); ?>
                                    <?php if ($isTeamAdminOfThisTeam): ?>
                                        <form action="action.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <input type="hidden" name="player_id" value="<?php echo $tp['id']; ?>">
                                            <button type="submit" class="delete-btn" style="padding: 2px 5px; font-size: 0.8rem;">&times;</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($isTeamAdminOfThisTeam): ?>
                            <form action="action.php" method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="action" value="assign_player">
                                <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                <select name="player_id" required style="padding: 5px; width: 70%;">
                                    <option value="">Spieler wählen...</option>
                                    <?php foreach ($all_players as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-add" style="padding: 5px 10px; float: none;">+</button>
                            </form>
                        <?php endif; ?>
                        <?php if (isTeamAdmin($team['id'], $player_id)):
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                            $registrationUrl = $protocol . "://" . $host . $path . "/register_team.php?hash=" . $team['hash'];
                            ?>
                            <p style="margin-top: 15px;"><strong>Anmeldelink für neue Spieler:</strong>
                            <div style="display: flex; gap: 5px; margin-top: 5px;">
                                <input type="text" readonly value="<?php echo htmlspecialchars($registrationUrl); ?>" style="flex-grow: 1; font-size: 0.8em;" id="reg-link-<?php echo $team['id']; ?>">
                                <button type="button" class="edit-btn" onclick="copyToClipboard('reg-link-<?php echo $team['id']; ?>')" title="Link kopieren" style="padding: 2px 8px;">📋</button>
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
