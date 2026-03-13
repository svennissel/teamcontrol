<?php
$title = "Spieler";
require_once './includes/header.php';



$loggedInPlayer = getLoggedInPlayer();
if (!$loggedInPlayer) {
    header('Location: login.php');
    exit;
}
$player_id = $loggedInPlayer['id'];
$isClubAdmin = $loggedInPlayer['is_club_admin'];

if (!$isClubAdmin && !isAnyTeamAdmin($player_id)) {
    header('Location: games.php');
    exit;
}

$all_players = getAllPlayers();
$playerTeams = getPlayerTeams($player_id);
$teams = getTeams($player_id, $isClubAdmin);

printHeader($loggedInPlayer, $playerTeams, "players");
?>

<div id="players" class="tab-content active">
    <section>
        <input type="text" id="player-search" class="register-input" placeholder="Spieler suchen..." style="margin-bottom: 10px;">
        <div class="events">
            <?php foreach ($all_players as $player):
                $player_teams = getPlayerTeams($player['id']);
                $player_team_ids = array_column($player_teams, 'id');
                $admin_teams = getAdminTeams($player['id']);
                $admin_team_ids = array_column($admin_teams, 'id');
                $player_team_roles = getPlayerTeamRoles($player['id']);
                $match_player_team_ids = array_column(array_filter($player_team_roles, fn($r) => $r['isMatchPlayer']), 'team_id');

                // Wenn Team-Admin, nur Spieler der eigenen Teams sehen
                if (!$isClubAdmin && isAnyTeamAdmin($player_id)) {
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
                            $canEdit = $isClubAdmin;
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
                                <button class="edit-btn" onclick='editPlayer(<?php echo json_encode(array_merge($player, ["team_ids" => $player_team_ids, "admin_team_ids" => $admin_team_ids, "match_player_team_ids" => $match_player_team_ids, "voter_permission_player_ids" => $voter_perm_ids])); ?>)' title="Bearbeiten">✎</button>
                            <?php endif; ?>
                            <?php if ($isClubAdmin): ?>
                                <form action="action.php" method="POST" class="inline-form" onsubmit="confirmDelete(event, 'Soll dieser Spieler wirklich gelöscht werden?')">
                                    <?php echo csrfField(); ?>
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
                            <?php if (!$player['is_club_admin'] || $isClubAdmin): ?>
                            <p>
                            <div class="copy-link-row">
                                Login-Link:
                                <input type="text" readonly value="<?php echo $login_link; ?>" id="login-link-<?php echo $player['id']; ?>">
                                <button type="button" class="edit-btn" onclick="copyToClipboard('login-link-<?php echo $player['id']; ?>')" title="Link kopieren">📋</button>
                                <button type="button" class="edit-btn" onclick="showQrCode('login-link-<?php echo $player['id']; ?>', '<?php echo $player['name']; ?>')" title="QR-Code anzeigen">
                                    <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16" height="16" viewBox="0 0 122.88 122.7"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><g><path class="st0" d="M0.18,0h44.63v44.45H0.18V0L0.18,0z M111.5,111.5h11.38v11.2H111.5V111.5L111.5,111.5z M89.63,111.48h11.38 v10.67H89.63h-0.01H78.25v-21.82h11.02V89.27h11.21V67.22h11.38v10.84h10.84v11.2h-10.84v11.2h-11.21h-0.17H89.63V111.48 L89.63,111.48z M55.84,89.09h11.02v-11.2H56.2v-11.2h10.66v-11.2H56.02v11.2H44.63v-11.2h11.2V22.23h11.38v33.25h11.02v11.2h10.84 v-11.2h11.38v11.2H89.63v11.2H78.25v22.05H67.22v22.23H55.84V89.09L55.84,89.09z M111.31,55.48h11.38v11.2h-11.38V55.48 L111.31,55.48z M22.41,55.48h11.38v11.2H22.41V55.48L22.41,55.48z M0.18,55.48h11.38v11.2H0.18V55.48L0.18,55.48z M55.84,0h11.38 v11.2H55.84V0L55.84,0z M0,78.06h44.63v44.45H0V78.06L0,78.06z M10.84,88.86h22.95v22.86H10.84V88.86L10.84,88.86z M78.06,0h44.63 v44.45H78.06V0L78.06,0z M88.91,10.8h22.95v22.86H88.91V10.8L88.91,10.8z M11.02,10.8h22.95v22.86H11.02V10.8L11.02,10.8z"/></g></svg>
                                </button>
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

<script>
(function() {
    const searchInput = document.getElementById('player-search');
    const cards = document.querySelectorAll('#players .events .event-card');
    let timer;
    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(function() {
            const query = searchInput.value.toLowerCase();
            cards.forEach(function(card) {
                const name = card.querySelector('.card-header h3').textContent.toLowerCase();
                card.style.display = name.includes(query) ? '' : 'none';
            });
        }, 500);
    });
})();
</script>
<?php include './includes/footer.php'; ?>
