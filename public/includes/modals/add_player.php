<div id="addPlayerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addPlayerModal').style.display='none'">&times;</span>
        <h2>Neuer Spieler</h2>
        <form action="action.php" method="POST">
            <input type="hidden" name="action" value="add_player">
            <div>
                <label>Name:</label>
                <input type="text" name="name" required>
            </div>
            <?php if (isClubAdmin() || isAnyTeamAdmin($player_id)): ?>
                <div>
                    <label>Vereinsadmin:</label>
                    <input type="checkbox" name="is_club_admin" <?php echo !isClubAdmin() ? 'disabled' : ''; ?>>
                </div>
                <div>
                    <label>Mannschaftsadmin für:</label>
                    <select name="admin_team_ids[]" id="add_player_admin_team_ids" multiple style="height: 100px;">
                        <?php 
                        $admin_selectable_teams = isClubAdmin() ? $teams : getAdminTeams($player_id);
                        foreach ($admin_selectable_teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; grid-column: 2; margin-top: -10px; color: #666;">Strg halten für Mehrfachauswahl.</small>
                </div>
            <?php endif; ?>
            <div>
                <label>Mannschaften:</label>
                <select name="team_ids[]" multiple style="height: 100px;" required>
                    <?php 
                    $selectable_teams = isClubAdmin() ? $teams : getAdminTeams($player_id);
                    foreach ($selectable_teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="display: block; grid-column: 2; margin-top: -10px; color: #666;">Strg halten für Mehrfachauswahl.</small>
            </div>
            <button type="submit">Spieler anlegen</button>
        </form>
    </div>
</div>