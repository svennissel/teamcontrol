<?php
$title = "Spieler";
require_once './includes/header.php';



$loggedInPlayer = getLoggedInPlayer();
if (!$loggedInPlayer) {
    header('Location: login.php');
    exit;
}
$player_id = $loggedInPlayer['id'];

if (!isClubAdmin() && !isAnyTeamAdmin($player_id)) {
    header('Location: games.php');
    exit;
}

$all_players = getAllPlayers();
$playerTeams = getPlayerTeams($player_id);
$teams = getTeams($player_id, isClubAdmin());

printHeader($loggedInPlayer, $playerTeams, "players");
?>

<div id="players" class="tab-content active">
    <section>
        <div class="events">
            <?php foreach ($all_players as $player):
                $player_teams = getPlayerTeams($player['id']);
                $player_team_ids = array_column($player_teams, 'id');
                $admin_teams = getAdminTeams($player['id']);
                $admin_team_ids = array_column($admin_teams, 'id');

                // Wenn Team-Admin, nur Spieler der eigenen Teams sehen
                if (!isClubAdmin() && isAnyTeamAdmin($player_id)) {
                    $my_admin_teams = getAdminTeams($player_id);
                    $my_admin_team_ids = array_column($my_admin_teams, 'id');
                    $overlap = array_intersect($player_team_ids, $my_admin_team_ids);
                    if (empty($overlap)) continue;
                }
                ?>
                <div class="event-card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($player['name']); ?></h3>
                        <div class="club-admin-actions">
                            <?php
                            $canEdit = isClubAdmin();
                            if (!$canEdit && isAnyTeamAdmin($player_id)) {
                                foreach ($player_team_ids as $tid) {
                                    if (isTeamAdmin($tid, $player_id)) {
                                        $canEdit = true;
                                        break;
                                    }
                                }
                            }
                            if ($canEdit): ?>
                                <?php $voter_perm_ids = getVoterPermissions($player['id']); ?>
                                <button class="edit-btn" onclick='editPlayer(<?php echo json_encode(array_merge($player, ["team_ids" => $player_team_ids, "admin_team_ids" => $admin_team_ids, "voter_permission_player_ids" => $voter_perm_ids])); ?>)' title="Bearbeiten">✎</button>
                            <?php endif; ?>
                            <?php if (isClubAdmin()): ?>
                                <form action="action.php" method="POST" class="inline-form" onsubmit="return confirm('Soll dieser Spieler wirklich gelöscht werden?');">
                                    <input type="hidden" name="action" value="delete_player">
                                    <input type="hidden" name="player_id" value="<?php echo $player['id']; ?>">
                                    <button type="submit" class="delete-btn" id="delete-player-btn" title="Löschen">🗑</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-details">
                        <p>Vereinsadmin: <?php echo $player['is_club_admin'] ? 'Ja' : 'Nein'; ?></p>
                        <p>Mannschaftsadmin:
                            <?php
                            $admin_names = array_column($admin_teams, 'name');
                            echo !empty($admin_names) ? htmlspecialchars(implode(', ', $admin_names)) : 'Keine';
                            ?>
                        </p>
                        <p>Mannschaften:
                            <?php
                            $names = array_column($player_teams, 'name');
                            echo !empty($names) ? htmlspecialchars(implode(', ', $names)) : 'Keine';
                            ?>
                        </p>
                        <?php if ($player['hash']):
                            $login_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/login.php?hash=" . $player['hash'];
                            ?>
                            <input type="hidden" class="player-hash-input" value="<?php echo $player['hash']; ?>">
                            <?php if (!$player['is_club_admin'] || isClubAdmin()): ?>
                            <p>
                            <div class="copy-link-row">
                                Login-Link:
                                <input type="text" readonly value="<?php echo $login_link; ?>" id="login-link-<?php echo $player['id']; ?>">
                                <button type="button" class="edit-btn" onclick="copyToClipboard('login-link-<?php echo $player['id']; ?>')" title="Link kopieren">📋</button>
                            </div>
                            </p>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include './includes/footer.php'; ?>
