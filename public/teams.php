<?php
$title = "Mannschaften";
require_once './includes/header.php';



$player = getLoggedInPlayer();
if (!$player) {
    header('Location: login.php?redirect=' . urlencode(basename($_SERVER['REQUEST_URI'])));
    exit;
}
$player_id = $player['id'];
$isClubAdmin = $player['is_club_admin'];

if (!$isClubAdmin && !isAnyTeamAdmin($player_id)) {
    header('Location: games.php');
    exit;
}


$all_players = getAllPlayers();
$playerTeams = getPlayerTeams($player_id);
$teams = getTeams($player_id, $isClubAdmin);


printHeader($player, $playerTeams, "teams");
?>

<div id="teams" class="tab-content active">
    <section>
        <div class="events">
            <?php foreach ($teams as $team):
                $isTeamAdminOfThisTeam = isTeamAdmin($team['id'], $player_id); ?>
                <div class="event-card">
                    <div class="card-header">
                        <h3>
                            <?php if ($team['logo']): ?>
                                <img src="uploads/logos/<?php echo htmlspecialchars($team['logo']); ?>_30.webp" alt="Logo" class="team-logo-inline">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($team['name']); ?>
                        </h3>
                        <div class="club-admin-actions">
                            <?php if ($isClubAdmin): ?>
                                <button class="edit-btn" onclick='editTeam(<?php echo json_encode($team); ?>)' title="Bearbeiten"><i class="fa-solid fa-pen"></i></button>
                                <form action="action.php" method="POST" class="inline-form" onsubmit="confirmDelete(event, 'Soll diese Mannschaft wirklich gelöscht werden?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete_team">
                                    <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                    <button type="submit" class="delete-btn" id="delete-team-btn" title="Mannschaft löschen"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-details">
                        <?php if ($isTeamAdminOfThisTeam): ?>
                            <form action="action.php" method="POST" class="team-assign-form">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="assign_player">
                                <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                <select name="player_id" required>
                                    <option value="">Spieler hinzufügen...</option>
                                    <?php
                                    $teamPlayerIds = array_column(getTeamPlayers($team['id']), 'id');
                                    foreach ($all_players as $p):
                                        if (in_array($p['id'], $teamPlayerIds)) continue;
                                    ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-add"><i class="fa-solid fa-plus"></i></button>
                            </form>
                        <?php endif; ?>
                        <?php if (isTeamAdmin($team['id'], $player_id)):
                            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                            $registrationUrl = $protocol . "://" . $host . $path . "/register_team.php?hash=" . $team['hash'];
                            ?>
                            <p class="reg-link-label"><strong>Mannschafts-Anmeldelink:</strong>
                            <div class="reg-link-row">
                                <input type="text" readonly value="<?php echo htmlspecialchars($registrationUrl); ?>" id="reg-link-<?php echo $team['id']; ?>">
                                <button type="button" class="edit-btn" onclick="copyToClipboard('reg-link-<?php echo $team['id']; ?>')" title="Link kopieren"><i class="fa-regular fa-clipboard"></i></button>
                                <button type="button" class="edit-btn" onclick="showQrCode('reg-link-<?php echo $team['id']; ?>', '<?php echo htmlspecialchars($team['name']); ?>')" title="QR-Code anzeigen">
                                    <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16" height="16" viewBox="0 0 122.88 122.7"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><g><path class="st0" d="M0.18,0h44.63v44.45H0.18V0L0.18,0z M111.5,111.5h11.38v11.2H111.5V111.5L111.5,111.5z M89.63,111.48h11.38 v10.67H89.63h-0.01H78.25v-21.82h11.02V89.27h11.21V67.22h11.38v10.84h10.84v11.2h-10.84v11.2h-11.21h-0.17H89.63V111.48 L89.63,111.48z M55.84,89.09h11.02v-11.2H56.2v-11.2h10.66v-11.2H56.02v11.2H44.63v-11.2h11.2V22.23h11.38v33.25h11.02v11.2h10.84 v-11.2h11.38v11.2H89.63v11.2H78.25v22.05H67.22v22.23H55.84V89.09L55.84,89.09z M111.31,55.48h11.38v11.2h-11.38V55.48 L111.31,55.48z M22.41,55.48h11.38v11.2H22.41V55.48L22.41,55.48z M0.18,55.48h11.38v11.2H0.18V55.48L0.18,55.48z M55.84,0h11.38 v11.2H55.84V0L55.84,0z M0,78.06h44.63v44.45H0V78.06L0,78.06z M10.84,88.86h22.95v22.86H10.84V88.86L10.84,88.86z M78.06,0h44.63 v44.45H78.06V0L78.06,0z M88.91,10.8h22.95v22.86H88.91V10.8L88.91,10.8z M11.02,10.8h22.95v22.86H11.02V10.8L11.02,10.8z"/></g></svg>
                                </button>
                            </div>
                            </p>
                        <?php endif; ?>

                        <h4>Spieler</h4>
                        <ul class="team-list">
                            <?php
                            $teamPlayers = getTeamPlayers($team['id']);
                            foreach ($teamPlayers as $tp): ?>
                                <li class="team-list-item team-player-role-item">
                                    <span class="team-player-name"><?php echo htmlspecialchars($tp['name']); ?></span>
                                    <?php if ($isTeamAdminOfThisTeam || $isClubAdmin): ?>
                                        <form action="action.php" method="POST" class="inline-form">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <input type="hidden" name="player_id" value="<?php echo $tp['id']; ?>">
                                            <button type="submit" class="delete-btn" title="Spieler aus Mannschaft entfernen"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                        <span class="team-player-roles">
                                            <label><input type="checkbox" checked disabled> Training</label>
                                            <label><input type="checkbox" class="role-checkbox" data-team="<?php echo $team['id']; ?>" data-player="<?php echo $tp['id']; ?>" data-role="isMatchViewer" <?php echo $tp['isMatchViewer'] ? 'checked' : ''; ?>> Spiele anzeigen</label>
                                            <label><input type="checkbox" class="role-checkbox" data-team="<?php echo $team['id']; ?>" data-player="<?php echo $tp['id']; ?>" data-role="isMatchPlayer" <?php echo $tp['isMatchPlayer'] ? 'checked' : ''; ?>> Spiele abstimmen</label>
                                            <label><input type="checkbox" class="role-checkbox" data-team="<?php echo $team['id']; ?>" data-player="<?php echo $tp['id']; ?>" data-role="isTeamAdmin" <?php echo $tp['isTeamAdmin'] ? 'checked' : ''; ?>> Admin</label>
                                        </span>
                                    <?php else: ?>
                                        <span class="team-player-roles">
                                            <label><input type="checkbox" checked disabled> Training</label>
                                            <label><input type="checkbox" disabled <?php echo $tp['isMatchViewer'] ? 'checked' : ''; ?>> Spiele anzeigen</label>
                                            <label><input type="checkbox" disabled <?php echo $tp['isMatchPlayer'] ? 'checked' : ''; ?>> Spiele abstimmen</label>
                                            <label><input type="checkbox" disabled <?php echo $tp['isTeamAdmin'] ? 'checked' : ''; ?>> Admin</label>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include './includes/footer.php'; ?>
