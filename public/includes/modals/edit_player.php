<div id="editPlayerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editPlayerModal').style.display='none'">&times;</span>
        <h2>Spieler bearbeiten</h2>
        <form action="action.php" method="POST">
            <input type="hidden" name="action" value="edit_player">
            <input type="hidden" name="player_id" id="edit_player_id">
            <div>
                <label>Name:</label>
                <input type="text" name="name" id="edit_player_name" required>
            </div>
            <?php if (isClubAdmin()): ?>
            <div>
                <label>Vereinsadmin:</label>
                <input type="checkbox" name="is_club_admin" id="edit_player_is_club_admin">
            </div>
            <?php endif; ?>
            <?php if (isClubAdmin() || isAnyTeamAdmin($player_id)): ?>
            <div>
                <label>Mannschaftsadmin für:</label>
                <select name="admin_team_ids[]" id="edit_player_admin_team_ids" multiple style="height: 100px;">
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
                <select name="team_ids[]" id="edit_player_team_ids" multiple style="height: 100px;" required>
                    <?php 
                    $selectable_teams = isClubAdmin() ? $teams : getAdminTeams($player_id);
                    foreach ($selectable_teams as $team): ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="display: block; grid-column: 2; margin-top: -10px; color: #666;">Strg halten für Mehrfachauswahl.</small>
            </div>
            <button type="submit">Änderungen speichern</button>
        </form>
    </div>
</div>